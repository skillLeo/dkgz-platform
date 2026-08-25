<?php

namespace App\Jobs;

use App\Models\ServiceRequest;
use App\Support\Formatter;
use App\Support\Mailer;
use App\Support\Settings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Tells the office that a request has arrived.
 *
 * The office previously heard only about the failures — a request nobody could
 * be found for. Everything that worked passed silently, which means nobody knew
 * how busy the platform was without opening it, and a request that went to
 * partners who then all declined was noticed late. This is the one that says
 * a customer is waiting, whatever happens next.
 */
class NotifyOfficeOfRequestJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly int $serviceRequestId) {}

    public function handle(): void
    {
        $request = ServiceRequest::with('serviceType')->find($this->serviceRequestId);

        if ($request === null) {
            return;
        }

        $recipient = Settings::get('contact.support_email') ?: Settings::get('contact.email');

        if (blank($recipient)) {
            return;
        }

        Mailer::send($recipient, 'neue-anfrage-intern', [
            'eyebrow' => 'Neue Anfrage',
            'headline' => 'Eine neue Anfrage ist eingegangen.',
            'referenz' => $request->reference,
            'refLabel' => 'Referenz',
            'gutachtenart' => $request->serviceType?->name_de,
            'plz' => $request->postal_code,
            'ort' => $request->city,
            'kunde_name' => $request->customer_name,
            'kunde_telefon' => Formatter::phone($request->customer_phone),
            'kunde_email' => $request->customer_email,
            'fahrzeug' => $request->vehicleLabel(),
            'eingang_datum' => Formatter::dateTime($request->created_at),
            'dataTitle' => 'Anfrage',
            // The office has already seen this customer's details by virtue of
            // running the platform, so there is nothing to withhold here.
            'rows' => array_values(array_filter([
                ['k' => 'Referenz', 'v' => $request->reference, 'mono' => true],
                ['k' => 'Art des Gutachtens', 'v' => $request->serviceType?->name_de],
                ['k' => 'Standort', 'v' => $request->locationLabel()],
                ['k' => 'Kunde', 'v' => $request->customer_name],
                ['k' => 'Telefon', 'v' => Formatter::phone($request->customer_phone), 'mono' => true],
                ['k' => 'E-Mail', 'v' => $request->customer_email],
                ['k' => 'Fahrzeug', 'v' => $request->vehicleLabel()],
                ['k' => 'Eingegangen am', 'v' => Formatter::dateTime($request->created_at), 'mono' => true],
            ])),
            'cta' => 'Anfrage in der Administration öffnen',
            'cta_url' => route('admin.requests.show', $request),
        ], related: $request);
    }
}
