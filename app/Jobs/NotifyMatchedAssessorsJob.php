<?php

namespace App\Jobs;

use App\Models\Assessor;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Notifications\NewRequestNotification;
use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

/**
 * Notifies every matched partner. Chunked, because a broad postal range can
 * match a great many assessors and the host may only allow 128M.
 */
class NotifyMatchedAssessorsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly int $serviceRequestId) {}

    public function handle(): void
    {
        $request = ServiceRequest::with('serviceType')->find($this->serviceRequestId);

        if ($request === null || $request->status !== ServiceRequest::STATUS_MATCHED) {
            return;
        }

        RequestMatch::where('service_request_id', $request->id)
            ->pending()
            ->with('assessor.user')
            ->chunkById(50, function ($matches) use ($request) {
                foreach ($matches as $match) {
                    $this->notifyOne($match->assessor, $request);
                }
            });
    }

    private function notifyOne(?Assessor $assessor, ServiceRequest $request): void
    {
        if ($assessor?->user === null) {
            return;
        }

        // In-portal notification, read back by the 45-second poll. This one is
        // not opt-out: the portal must show the partner what they were matched
        // to, or the match is invisible to them.
        $assessor->user->notify(new NewRequestNotification($request));

        // The e-mail is opt-out, from the Benachrichtigungen panel in settings.
        if (! $assessor->notify_new_request) {
            return;
        }

        Mailer::send($assessor->user->email, 'neue-anfrage-im-gebiet', [
            'eyebrow' => 'Neue Anfrage',
            'headline' => 'Neue Anfrage in Ihrem Einsatzgebiet.',
            'salutation' => 'Guten Tag '.$assessor->user->last_name.',',
            'sv_nachname' => $assessor->user->last_name,
            'referenz' => $request->reference,
            'gutachtenart' => $request->serviceType?->name_de,
            'plz' => $request->postal_code,
            'ort' => $request->city,
            'fahrzeug' => $request->vehicleLabel(),
            'schadenart' => $request->description,
            // Contact details are deliberately absent until acceptance.
            'mask' => true,
            'dataTitle' => 'Anfragedaten',
            'rows' => array_values(array_filter([
                ['k' => 'Art des Gutachtens', 'v' => $request->serviceType?->name_de],
                ['k' => 'Standort', 'v' => $request->locationLabel()],
                ['k' => 'Fahrzeug', 'v' => $request->vehicleLabel()],
                $request->description ? ['k' => 'Schadenart', 'v' => Str::limit($request->description, 120)] : null,
                $request->urgency ? ['k' => 'Dringlichkeit', 'v' => $this->urgencyLabel($request->urgency)] : null,
            ])),
            'cta' => 'Anfrage im Portal ansehen',
            'cta_url' => route('portal.requests.show', $request),
            'note' => 'Nach Ablauf der Frist wird die Anfrage an weitere Partner im Gebiet weitergegeben.',
        ], related: $request);
    }

    private function urgencyLabel(string $urgency): string
    {
        return match ($urgency) {
            'normal' => 'Keine besondere Eile',
            'soon' => 'Innerhalb von zwei Werktagen',
            'urgent' => 'So schnell wie möglich',
            default => $urgency,
        };
    }
}
