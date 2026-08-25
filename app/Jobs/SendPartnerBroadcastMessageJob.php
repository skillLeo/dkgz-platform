<?php

namespace App\Jobs;

use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One message of a partner broadcast.
 *
 * Its own job so a single bad address retries and fails alone, rather than
 * taking the rest of the list down with it.
 */
class SendPartnerBroadcastMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        private readonly string $email,
        private readonly string $subject,
        private readonly string $body,
        private readonly ?string $senderName = null,
    ) {}

    public function handle(): void
    {
        Mailer::send($this->email, 'partner-rundmail', [
            'eyebrow' => 'Mitteilung',
            'headline' => $this->subject,
            'betreff' => $this->subject,
            'nachricht' => $this->body,
            'absender' => $this->senderName ?? 'DKGZ Administration',
        ]);
    }
}
