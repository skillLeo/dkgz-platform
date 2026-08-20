<?php

namespace App\Jobs;

use App\Models\ServiceRequest;
use App\Support\Mailer;
use App\Support\Settings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Tells the customer, in their own words, that nothing came of their request.
 *
 * A request that expires or that every partner declines used to end in silence.
 * The customer had given their details and heard nothing back, ever — which is
 * the single worst thing this platform could do to someone, because they are
 * left believing help is on the way.
 */
class NotifyCustomerNoResponseJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly int $serviceRequestId) {}

    public function handle(): void
    {
        $request = ServiceRequest::with('serviceType')->find($this->serviceRequestId);

        if ($request === null || blank($request->customer_email)) {
            return;
        }

        // Never twice for the same request, however it got here.
        if ($request->customer_notified_at !== null) {
            return;
        }

        // Three ways a request ends without a partner: every matched partner
        // declined, an administrator closed it by hand, or nobody covered the
        // area at all. The last keeps status 'new' with a zero match count.
        $unplaced = in_array($request->status, [
            ServiceRequest::STATUS_UNANSWERED,
            ServiceRequest::STATUS_CANCELLED,
        ], true) || ($request->status === ServiceRequest::STATUS_NEW && $request->matched_count === 0);

        if (! $unplaced) {
            return;
        }

        Mailer::send($request->customer_email, 'anfrage-keine-rueckmeldung', [
            'eyebrow' => 'Ihre Anfrage',
            'headline' => 'Wir haben Ihre Anfrage noch nicht vermitteln können.',
            'salutation' => 'Guten Tag '.$request->customer_name.',',
            'referenz' => $request->reference,
            'gutachtenart' => $request->serviceType?->name_de,
            'plz' => $request->postal_code,
            'ort' => $request->city,
            'telefon' => Settings::get('contact.phone'),
            'email' => Settings::get('contact.support_email'),
            'oeffnungszeiten' => Settings::get('contact.office_hours'),
            'dataTitle' => 'Ihre Anfrage',
            'rows' => array_values(array_filter([
                ['k' => 'Referenz', 'v' => $request->reference, 'mono' => true],
                ['k' => 'Art des Gutachtens', 'v' => $request->serviceType?->name_de],
                ['k' => 'Ort', 'v' => $request->locationLabel()],
            ], fn (array $row) => filled($row['v']))),
        ]);

        $request->forceFill(['customer_notified_at' => now()])->save();
    }
}
