<?php

namespace App\Console\Commands;

use App\Models\AssessorDocument;
use App\Models\LiabilityReminder;
use App\Support\Formatter;
use App\Support\Mailer;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

/**
 * Warns partners before their liability cover runs out, and records the moment
 * one drops out of matching because it did.
 *
 * A partner who simply stops receiving work, with no explanation, has no way to
 * tell a quiet week from being switched off. The reminders exist so that never
 * happens by accident.
 */
class CheckLiabilityCoverCommand extends Command
{
    protected $signature = 'dkgz:check-liability-cover';

    protected $description = 'Sends liability-cover reminders and logs partners dropping out of matching.';

    /** Days before expiry at which a reminder goes out; 0 is the day it lapses. */
    private const MILESTONES = [30, 14, 3, 0];

    public function handle(): int
    {
        $sent = 0;

        AssessorDocument::query()
            ->where('type', AssessorDocument::TYPE_LIABILITY)
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '>=', now()->subDay())
            ->whereDate('valid_until', '<=', now()->addDays(max(self::MILESTONES)))
            ->with('assessor.user')
            ->chunkById(100, function ($documents) use (&$sent) {
                foreach ($documents as $document) {
                    $sent += $this->remindFor($document) ? 1 : 0;
                }
            });

        $this->info("{$sent} Erinnerung(en) versendet.");

        return self::SUCCESS;
    }

    private function remindFor(AssessorDocument $document): bool
    {
        $assessor = $document->assessor;

        if ($assessor?->user === null || ! $assessor->isApproved()) {
            return false;
        }

        // A partner who already has newer cover on file needs no warning about
        // the older one.
        if (! $assessor->liabilityCoverExpiresOn($document->valid_until)) {
            return false;
        }

        $daysLeft = (int) round(now()->startOfDay()->diffInDays($document->valid_until->startOfDay(), false));
        $milestone = collect(self::MILESTONES)->first(fn (int $m) => $daysLeft <= $m && $daysLeft > ($m === 0 ? -1 : self::nextLower($m)));

        if ($milestone === null) {
            return false;
        }

        try {
            LiabilityReminder::create([
                'assessor_id' => $assessor->id,
                'valid_until' => $document->valid_until,
                'days_before' => $milestone,
                'sent_at' => now(),
            ]);
        } catch (QueryException) {
            // Already sent for this partner, date and milestone.
            return false;
        }

        Mailer::send($assessor->user->email, 'haftpflicht-laeuft-ab', [
            'eyebrow' => 'Nachweis',
            'headline' => $daysLeft <= 0
                ? 'Ihr Haftpflichtnachweis ist abgelaufen.'
                : 'Ihr Haftpflichtnachweis läuft bald ab.',
            'salutation' => 'Guten Tag '.$assessor->user->last_name.',',
            'sv_nachname' => $assessor->user->last_name,
            'ablaufdatum' => Formatter::date($document->valid_until),
            'resttage' => max(0, $daysLeft),
            'partner_id' => $assessor->partnerId(),
            'cta' => 'Nachweis hinterlegen',
            'ctaUrl' => route('portal.profile'),
        ]);

        if ($daysLeft <= 0 && Settings::bool('business.require_valid_liability_cover', true)) {
            activity()
                ->performedOn($assessor)
                ->withProperties(['valid_until' => $document->valid_until->toDateString()])
                ->log('Haftpflichtnachweis abgelaufen — Partner erhält keine neuen Anfragen mehr.');
        }

        return true;
    }

    /** The next milestone below this one, so each band is claimed exactly once. */
    private static function nextLower(int $milestone): int
    {
        $lower = collect(self::MILESTONES)->filter(fn (int $m) => $m < $milestone);

        return $lower->isEmpty() ? -1 : $lower->max();
    }
}
