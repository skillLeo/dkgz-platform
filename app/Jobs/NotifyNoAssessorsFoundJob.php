<?php

namespace App\Jobs;

use App\Models\ServiceRequest;
use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * A request nobody covers is never silently dropped: the admin is told, and it
 * surfaces on the dashboard under "Nicht vermittelt".
 */
class NotifyNoAssessorsFoundJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private readonly int $serviceRequestId) {}

    public function handle(): void
    {
        $request = ServiceRequest::with('serviceType')->find($this->serviceRequestId);

        if ($request === null) {
            return;
        }

        Mailer::send(Mailer::adminRecipient(), 'keine-sachverstaendigen-gefunden', [
            'eyebrow' => 'Nicht vermittelt',
            'headline' => 'Für diese Anfrage gibt es keinen passenden Partner.',
            'salutation' => 'Guten Tag,',
            'referenz' => $request->reference,
            'gutachtenart' => $request->serviceType?->name_de,
            'plz' => $request->postal_code,
            'ort' => $request->city,
            'dataTitle' => 'Anfrage',
            'rows' => [
                ['k' => 'Referenz', 'v' => $request->reference, 'mono' => true],
                ['k' => 'Art des Gutachtens', 'v' => $request->serviceType?->name_de],
                ['k' => 'Standort', 'v' => $request->locationLabel()],
                ['k' => 'Fahrzeug', 'v' => $request->vehicleLabel()],
            ],
            'cta' => 'Anfrage in der Administration öffnen',
            'cta_url' => route('admin.dashboard'),
            'note' => 'Die Anfrage steht in der Administration unter „Nicht vermittelt“ und wartet auf eine manuelle Zuordnung.',
        ], related: $request);
    }
}
