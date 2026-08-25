<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends one message to many partners, slowly.
 *
 * A hundred messages pushed at a shared host in one second is how a domain gets
 * its reputation ruined, so each is queued with its own small delay: roughly
 * twenty a minute, fast enough to finish a large list within the hour and
 * gentle enough that nothing throttles.
 *
 * Each partner is addressed individually rather than by blind copy — a mail
 * with a hundred hidden recipients is what spam filters are built to catch.
 */
class SendPartnerBroadcastJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    /** @param  array<int, string>  $recipients */
    public function __construct(
        private readonly string $subject,
        private readonly string $body,
        private readonly array $recipients,
        private readonly ?int $senderId = null,
    ) {}

    public function handle(): void
    {
        $sender = $this->senderId === null ? null : User::find($this->senderId);

        foreach (array_values($this->recipients) as $position => $email) {
            SendPartnerBroadcastMessageJob::dispatch(
                $email,
                $this->subject,
                $this->body,
                $sender?->fullName(),
            )->delay(now()->addSeconds((int) floor($position * 3)));
        }
    }
}
