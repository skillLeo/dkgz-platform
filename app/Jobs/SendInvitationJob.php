<?php

namespace App\Jobs;

use App\Models\Invitation;
use App\Support\Formatter;
use App\Support\Mailer;
use App\Support\Settings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendInvitationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly int $invitationId) {}

    public function handle(): void
    {
        $invitation = Invitation::with('invitedBy')->find($this->invitationId);

        if ($invitation === null || $invitation->isAccepted()) {
            return;
        }

        $rate = Settings::commissionRate();

        Mailer::send($invitation->email, 'einladung-partnerschaft', [
            'eyebrow' => 'Einladung',
            'headline' => 'Sie wurden in das DKGZ-Partnernetz eingeladen.',
            'salutation' => 'Guten Tag,',
            'admin_nachricht' => $invitation->message ?? '',
            'admin_name' => $invitation->invitedBy?->fullName() ?? 'DKGZ Administration',
            'admin_datum' => Formatter::date($invitation->created_at),
            'provisionssatz' => Formatter::percent($rate),
            'ablauf_datum' => Formatter::date($invitation->expires_at),
            'gueltigkeit_tage' => (string) now()->diffInDays($invitation->expires_at),
            'quote' => $invitation->message,
            'quoteBy' => trim(($invitation->invitedBy?->fullName() ?? 'DKGZ Administration')
                .' · DKGZ Administration · '.Formatter::date($invitation->created_at)),
            'dataTitle' => 'Konditionen',
            'rows' => [
                ['k' => 'Vermittlungsprovision', 'v' => Formatter::percent($rate).' auf abgeschlossene Aufträge'],
                ['k' => 'Grundgebühr', 'v' => 'keine'],
                ['k' => 'Kosten pro Anfrage', 'v' => 'keine'],
                ['k' => 'Vertragslaufzeit', 'v' => 'keine'],
                ['k' => 'Abrechnung', 'v' => 'monatlich, Sammelrechnung'],
            ],
            'cta' => 'Zugang einrichten',
            'cta_url' => route('invitation.show', $invitation->token),
            'note' => 'Diese Einladung ist bis zum '.Formatter::date($invitation->expires_at)
                .' gültig. Danach fordern Sie eine neue Einladung bei der Administration an.',
        ], related: $invitation);
    }
}
