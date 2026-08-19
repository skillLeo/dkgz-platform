<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Database channel only. There are no websockets on shared hosting, so the
 * portal reads these back through a 45-second poll.
 */
class NewRequestNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ServiceRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Neue Anfrage in Ihrem Gebiet',
            'body' => trim(($this->request->serviceType?->name_de ?? 'Gutachten').' · '.$this->request->locationLabel()),
            'reference' => $this->request->reference,
            'url' => route('portal.requests.show', $this->request),
        ];
    }
}
