<?php

namespace App\Jobs;

use App\Models\ServiceRequest;
use App\Support\Formatter;
use App\Support\Mailer;
use App\Support\Settings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Tells the customer their assessment is not going ahead.
 *
 * A customer who handed over their details and then hears nothing assumes the
 * appointment is still coming. Saying plainly that it fell through, and how to
 * start again, is the least the platform owes them — and it is the difference
 * between a bad day and a complaint.
 */
class NotifyRequestCancelledJob implements ShouldQueue
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

        Mailer::send($request->customer_email, 'anfrage-storniert', [
            'eyebrow' => 'Ihre Anfrage',
            'headline' => 'Ihre Begutachtung findet nicht statt.',
            'kunde' => $request->customer_name,
            'referenz' => $request->reference,
            'refLabel' => 'Ihre Vorgangsnummer',
            'gutachtenart' => $request->serviceType?->name_de,
            'telefon' => Settings::get('contact.phone'),
            'email' => Settings::get('contact.support_email'),
            'oeffnungszeiten' => Settings::get('contact.office_hours'),
            'dataTitle' => 'Ihre Anfrage',
            'rows' => array_values(array_filter([
                ['k' => 'Referenz', 'v' => $request->reference, 'mono' => true],
                ['k' => 'Art des Gutachtens', 'v' => $request->serviceType?->name_de],
                ['k' => 'Standort', 'v' => $request->locationLabel()],
                ['k' => 'Eingegangen am', 'v' => Formatter::dateTime($request->created_at), 'mono' => true],
            ])),
            'cta' => 'Neue Anfrage stellen',
            'cta_url' => route('request.create'),
        ], related: $request);
    }
}
