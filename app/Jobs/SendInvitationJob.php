<?php

namespace App\Jobs;

use App\Models\Invitation;
use App\Support\Formatter;
use App\Support\Mailer;
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

        // Somebody who already works with Carspector is not being asked to
        // trust a stranger; they are being told the firm they know has opened a
        // second line of business. Same invitation, different first sentence.
        $template = $invitation->known_partner
            ? 'einladung-carspector'
            : 'einladung-partnerschaft';

        Mailer::send($invitation->email, $template, [
            'eyebrow' => 'Einladung',
            'headline' => $invitation->known_partner
                ? 'Ihr Zugang zur Deutschen KFZ-Gutachterzentrale.'
                : 'Sie wurden in das DKGZ-Partnernetz eingeladen.',
            'salutation' => 'Guten Tag,',
            'admin_nachricht' => $invitation->message ?? '',
            'admin_name' => $invitation->invitedBy?->fullName() ?? 'DKGZ Administration',
            'admin_datum' => Formatter::date($invitation->created_at),
            'provisionssatz' => 'einem festen Betrag je Gutachtenart',
            'ablauf_datum' => Formatter::date($invitation->expires_at),
            'gueltigkeit_tage' => (string) now()->diffInDays($invitation->expires_at),
            'quote' => $invitation->message,
            'quoteBy' => trim(($invitation->invitedBy?->fullName() ?? 'DKGZ Administration')
                .' · DKGZ Administration · '.Formatter::date($invitation->created_at)),
            'dataTitle' => 'Konditionen',
            'rows' => [
                ['k' => 'Vermittlungsprovision', 'v' => 'einem festen Betrag je Gutachtenart'.' auf abgeschlossene Aufträge'],
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
