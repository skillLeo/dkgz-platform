<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestClosedNotification extends Notification
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
            'title' => 'Anfrage bereits vergeben',
            'body' => $this->request->reference.' wurde von einem anderen Partner übernommen.',
            'reference' => $this->request->reference,
            'url' => route('portal.requests'),
        ];
    }
}
