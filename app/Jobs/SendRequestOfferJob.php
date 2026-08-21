<?php

namespace App\Jobs;

use App\Models\RequestOffer;
use App\Support\Formatter;
use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends a hand-picked assessor the link to one specific request.
 *
 * Deliberately says nothing that identifies the customer: the recipient has no
 * account and no agreement with DKGZ yet, so they see the job — type, region,
 * vehicle — and nothing that would let them approach the customer directly.
 */
class SendRequestOfferJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly int $offerId) {}

    public function handle(): void
    {
        $offer = RequestOffer::with('serviceRequest.serviceType')->find($this->offerId);
        $request = $offer?->serviceRequest;

        if ($offer === null || $request === null) {
            return;
        }

        Mailer::send($offer->email, 'anfrage-angebot', [
            'eyebrow' => 'Anfrage',
            'headline' => 'Ein Auftrag in Ihrer Region.',
            'salutation' => filled($offer->name) ? 'Guten Tag '.$offer->name.',' : 'Guten Tag,',
            'intro' => 'die Deutsche KFZ-Gutachterzentrale vermittelt Aufträge an geprüfte '
                .'Sachverständige. Für den folgenden Auftrag suchen wir jemanden in Ihrer Region '
                .'und möchten ihn Ihnen anbieten.',
            'nachricht' => $offer->message,
            'dataTitle' => 'Der Auftrag',
            'rows' => array_values(array_filter([
                ['k' => 'Vorgang', 'v' => $request->reference, 'mono' => true],
                ['k' => 'Leistung', 'v' => $request->serviceType?->name_de],
                ['k' => 'Ort', 'v' => $request->locationLabel()],
                ['k' => 'Fahrzeug', 'v' => $request->vehicleLabel()],
                ['k' => 'Eingegangen', 'v' => Formatter::dateTime($request->created_at)],
            ], fn (array $row) => filled($row['v']))),
            'outro' => 'Sie können den Auftrag direkt annehmen. Ihre Registrierung als Partner '
                .'schließen Sie danach an — der Auftrag bleibt '.RequestOffer::HOLD_HOURS
                .' Stunden für Sie reserviert.',
            'cta' => 'Auftrag ansehen',
            'cta_url' => route('offer.show', $offer->token),
            'footnote' => 'Dieser Link ist '.RequestOffer::VALID_DAYS.' Tage gültig.',
        ], related: $offer);
    }
}
