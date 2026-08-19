<?php

namespace App\Jobs;

use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendContactEnquiryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly array $data) {}

    public function handle(): void
    {
        Mailer::send(Mailer::adminRecipient(), 'kontaktanfrage', [
            'eyebrow' => 'Kontaktanfrage',
            'headline' => 'Neue Nachricht über das Kontaktformular',
            'salutation' => 'Guten Tag,',
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'telefon' => $this->data['phone'] ?? '',
            'betreff' => $this->data['subject'],
            'nachricht' => $this->data['message'],
            'dataTitle' => 'Absender',
            'rows' => array_values(array_filter([
                ['k' => 'Name', 'v' => $this->data['name']],
                ['k' => 'E-Mail', 'v' => $this->data['email']],
                $this->data['phone'] ?? null ? ['k' => 'Telefon', 'v' => $this->data['phone'], 'mono' => true] : null,
                $this->data['reference'] ?? null ? ['k' => 'Vorgangsnummer', 'v' => $this->data['reference'], 'mono' => true] : null,
                ['k' => 'Betreff', 'v' => $this->data['subject']],
            ])),
            'note' => $this->data['message'],
        ]);
    }
}
