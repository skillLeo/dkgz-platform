<?php

namespace App\Jobs;

use App\Models\Assignment;
use App\Models\RequestMatch;
use App\Notifications\RequestClosedNotification;
use App\Support\Formatter;
use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Three audiences on acceptance: the winning partner gets the customer's
 * details, the customer learns who is coming, and the partners who lost the
 * race are told so they stop looking at it.
 */
class NotifyAssignmentAcceptedJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly int $assignmentId) {}

    public function handle(): void
    {
        $assignment = Assignment::with([
            'serviceRequest.serviceType',
            'assessor.user',
        ])->find($this->assignmentId);

        if ($assignment === null) {
            return;
        }

        $this->notifyWinner($assignment);
        $this->notifyCustomer($assignment);
        $this->notifyLosers($assignment);
    }

    private function notifyWinner(Assignment $assignment): void
    {
        $user = $assignment->assessor?->user;
        $request = $assignment->serviceRequest;

        if ($user === null || $request === null) {
            return;
        }

        Mailer::send($user->email, 'auftrag-bestaetigt', [
            'eyebrow' => 'Auftrag angenommen',
            'headline' => 'Sie haben den Auftrag übernommen.',
            'salutation' => 'Guten Tag '.$user->last_name.',',
            'sv_nachname' => $user->last_name,
            'referenz' => $request->reference,
            'refLabel' => 'Referenz',
            'gutachtenart' => $request->serviceType?->name_de,
            'kunde_name' => $request->customer_name,
            'kunde_telefon' => Formatter::phone($request->customer_phone),
            'kunde_email' => $request->customer_email,
            'plz' => $request->postal_code,
            'ort' => $request->city,
            'fahrzeug' => $request->vehicleLabel(),
            'dataTitle' => 'Kontaktdaten der anfragenden Person',
            'rows' => [
                ['k' => 'Name', 'v' => $request->customer_name],
                ['k' => 'Telefon', 'v' => Formatter::phone($request->customer_phone), 'mono' => true],
                ['k' => 'E-Mail', 'v' => $request->customer_email],
                ['k' => 'Standort', 'v' => $request->locationLabel()],
                ['k' => 'Fahrzeug', 'v' => $request->vehicleLabel()],
            ],
            'cta' => 'Auftrag im Portal öffnen',
            'cta_url' => route('portal.assignments.show', $assignment),
            'note' => 'Nach Abschluss hinterlegen Sie Gutachten und Rechnung im Portal und erfassen das berechnete Honorar.',
        ], related: $assignment);
    }

    private function notifyCustomer(Assignment $assignment): void
    {
        $request = $assignment->serviceRequest;
        $assessor = $assignment->assessor;

        if ($request === null || $assessor === null) {
            return;
        }

        Mailer::send($request->customer_email, 'auftrag-vergeben', [
            'eyebrow' => 'Auftrag angenommen',
            'headline' => 'Ihr Sachverständiger steht fest.',
            'salutation' => 'Guten Tag '.$request->customer_name.',',
            'nachname' => $request->customer_name,
            'referenz' => $request->reference,
            'refLabel' => 'Ihre Vorgangsnummer',
            'sv_firma' => $assessor->company_name,
            'dataTitle' => 'Ihr Sachverständiger',
            'rows' => array_values(array_filter([
                ['k' => 'Büro', 'v' => $assessor->company_name],
                ['k' => 'Ansprechpartner', 'v' => $assessor->user?->fullName()],
                $assessor->user?->phone ? ['k' => 'Telefon', 'v' => Formatter::phone($assessor->user->phone), 'mono' => true] : null,
                ['k' => 'E-Mail', 'v' => $assessor->user?->email],
                ['k' => 'Angenommen am', 'v' => Formatter::dateTime($assignment->accepted_at), 'mono' => true],
            ])),
            'cta' => 'Vorgang ansehen',
            'cta_url' => route('request.confirmation', $request->reference),
            'note' => 'Der Sachverständige erbringt die Begutachtung in eigener Verantwortung. DKGZ hat ausschließlich vermittelt.',
        ], related: $assignment);
    }

    private function notifyLosers(Assignment $assignment): void
    {
        $request = $assignment->serviceRequest;

        if ($request === null) {
            return;
        }

        RequestMatch::where('service_request_id', $request->id)
            ->where('outcome', RequestMatch::OUTCOME_CLOSED)
            ->with('assessor.user')
            ->chunkById(50, function ($matches) use ($request) {
                foreach ($matches as $match) {
                    $user = $match->assessor?->user;

                    if ($user === null) {
                        continue;
                    }

                    $user->notify(new RequestClosedNotification($request));

                    Mailer::send($user->email, 'auftrag-vergeben', [
                        'eyebrow' => 'Anfrage vergeben',
                        'headline' => 'Diese Anfrage wurde bereits vergeben.',
                        'salutation' => 'Guten Tag '.$user->last_name.',',
                        'sv_nachname' => $user->last_name,
                        'referenz' => $request->reference,
                        'refLabel' => 'Referenz',
                        'gutachtenart' => $request->serviceType?->name_de,
                        'plz' => $request->postal_code,
                        'ort' => $request->city,
                        'cta' => 'Offene Anfragen ansehen',
                        'cta_url' => route('portal.requests'),
                        'note' => 'Ihre Verfügbarkeit bleibt unverändert. Eine Ablehnung oder ein verpasster Auftrag wirkt sich nicht auf die weitere Verteilung aus.',
                    ], related: $request);
                }
            });
    }
}
