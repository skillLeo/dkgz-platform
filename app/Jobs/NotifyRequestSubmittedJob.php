<?php

namespace App\Jobs;

use App\Models\ServiceRequest;
use App\Support\Formatter;
use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyRequestSubmittedJob implements ShouldQueue
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

        Mailer::send($request->customer_email, 'anfrage-eingegangen', [
            'eyebrow' => 'Anfrage eingegangen',
            'headline' => 'Ihre Anfrage ist bei uns eingegangen.',
            'salutation' => 'Guten Tag '.$request->customer_name.',',
            'nachname' => $request->customer_name,
            'referenz' => $request->reference,
            'refLabel' => 'Ihre Vorgangsnummer',
            'gutachtenart' => $request->serviceType?->name_de,
            'plz' => $request->postal_code,
            'ort' => $request->city,
            'fahrzeug' => $request->vehicleLabel(),
            'kennzeichen' => $request->vehicle_plate,
            'eingang_datum' => Formatter::dateTime($request->created_at),
            'dataTitle' => 'Ihre Angaben',
            'rows' => array_values(array_filter([
                ['k' => 'Art des Gutachtens', 'v' => $request->serviceType?->name_de],
                ['k' => 'Standort des Fahrzeugs', 'v' => $request->locationLabel()],
                ['k' => 'Fahrzeug', 'v' => $request->vehicleLabel()],
                $request->vehicle_plate ? ['k' => 'Kennzeichen', 'v' => $request->vehicle_plate, 'mono' => true] : null,
                ['k' => 'Eingegangen am', 'v' => Formatter::dateTime($request->created_at), 'mono' => true],
            ])),
            // No button: the page behind it tells the customer nothing they
            // are not already reading in this message.
            'note' => 'Die Vermittlung ist für Sie kostenfrei. Die Kosten des Gutachtens rechnet der Sachverständige direkt mit Ihnen oder Ihrer Versicherung ab.',
        ], related: $request);
    }
}
