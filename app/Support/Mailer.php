<?php

namespace App\Support;

use App\Mail\TemplateMail;
use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The single send path. Every outgoing message is written to email_logs first,
 * so a failure is visible in the admin system page rather than only in a file
 * the client will never open.
 */
class Mailer
{
    public static function send(
        string $recipient,
        string $templateKey,
        array $data = [],
        array $attachments = [],
        ?Model $related = null,
    ): EmailLog {
        $mailable = new TemplateMail($templateKey, $data, $attachments);

        $log = EmailLog::create([
            'template_key' => $templateKey,
            'recipient' => $recipient,
            'subject' => $mailable->envelope()->subject ?? $templateKey,
            'status' => EmailLog::STATUS_QUEUED,
            'related_type' => $related === null ? null : $related::class,
            'related_id' => $related?->getKey(),
        ]);

        try {
            Mail::to($recipient)->queue($mailable);
        } catch (Throwable $e) {
            $log->markFailed($e->getMessage());
            Log::error("E-Mail [{$templateKey}] an {$recipient} konnte nicht in die Warteschlange gestellt werden.", [
                'exception' => $e,
            ]);
        }

        return $log;
    }

    /**
     * Sends immediately and surfaces the real driver error, which is what the
     * "Testmail senden" button in the admin needs — a generic failure message
     * would leave the client unable to fix their own SMTP settings.
     *
     * @return array{ok: bool, error: ?string}
     */
    public static function sendTest(string $recipient): array
    {
        $log = EmailLog::create([
            'template_key' => 'testmail',
            'recipient' => $recipient,
            'subject' => 'Testmail der DKGZ-Plattform',
            'status' => EmailLog::STATUS_QUEUED,
        ]);

        try {
            Mail::to($recipient)->sendNow(new TemplateMail('testmail', [
                'headline' => 'Der Mailversand funktioniert.',
                'salutation' => 'Guten Tag,',
                'zeitpunkt' => Formatter::dateTime(now()),
                'server' => Settings::get('integrations.smtp_host', config('mail.mailers.smtp.host', 'lokaler Mailer')),
                'eyebrow' => 'Testmail',
            ]));

            $log->markSent();

            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            $log->markFailed($e->getMessage());

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** Where internal notifications go. */
    public static function adminRecipient(): string
    {
        return (string) Settings::get(
            'email.admin_recipient',
            Settings::get('contact.support_email', config('mail.from.address'))
        );
    }
}
