<?php

namespace App\Listeners;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;

/**
 * Marks the log row sent once the message actually leaves.
 *
 * Queued mail is written to the log as "queued" and, until this listener
 * existed, stayed that way — so the System panel reported nothing sent even
 * while mail was arriving, which is exactly the wrong thing for a panel whose
 * job is to tell an operator whether delivery works.
 */
class RecordMailDelivery
{
    public function handle(MessageSent $event): void
    {
        $header = $event->message->getHeaders()->get('X-DKGZ-Log');

        if ($header === null) {
            return;
        }

        EmailLog::whereKey((int) $header->getBodyAsString())->first()?->markSent();
    }
}
