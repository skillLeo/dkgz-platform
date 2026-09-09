<?php

namespace App\Support;

use App\Models\AssessorDocument;
use App\Models\Commission;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The admin dashboard's "Erfordert Aufmerksamkeit" list.
 *
 * Everything here is a case the platform cannot resolve on its own and that
 * will quietly rot if nobody looks: a request no partner took, an area with no
 * cover at all, a partner whose liability cover is about to lapse, a commission
 * nobody has invoiced. Each row says what happened and how long it has been
 * true, because "how long" is what decides which one to open first.
 *
 * Nothing here may warn about something the platform no longer asks for. A row
 * nobody can act on teaches the reader to skim the list, and a list that is
 * skimmed is the same as no list.
 */
class AttentionQueue
{
    public const STALE_COMMISSION_DAYS = 30;

    public const COVER_EXPIRY_DAYS = 30;

    /** @return array<int, array<string, mixed>> */
    public static function items(int $limit = 25): array
    {
        $items = array_merge(
            self::customerUninformed(),
            self::declinedByEveryone(),
            self::withoutCover(),
            self::lapsingCover(),
            self::staleCommissions(),
            self::undeliveredMail(),
        );

        // Oldest first: the longest-standing problem is the most overdue.
        usort($items, fn (array $a, array $b) => $b['days'] <=> $a['days']);

        return array_slice($items, 0, $limit);
    }

    public static function count(): int
    {
        return count(self::items(PHP_INT_MAX));
    }

    /**
     * Requests that ended without an assignment and whose customer has still not
     * been told. The mail is queued automatically, so a row here means the queue
     * did not run — which is exactly what nobody notices until someone complains.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function customerUninformed(): array
    {
        return ServiceRequest::query()
            ->where('is_test', false)
            ->whereIn('status', [ServiceRequest::STATUS_UNANSWERED, ServiceRequest::STATUS_CANCELLED])
            ->whereNull('customer_notified_at')
            ->whereNotNull('customer_email')
            ->get()
            ->map(fn (ServiceRequest $request) => self::row(
                $request->reference,
                'Kunde wurde noch nicht benachrichtigt',
                $request->updated_at,
                route('admin.requests.show', $request),
            ))
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private static function declinedByEveryone(): array
    {
        return ServiceRequest::query()
            ->where('is_test', false)
            ->whereIn('status', [ServiceRequest::STATUS_MATCHED, ServiceRequest::STATUS_UNANSWERED])
            ->where('matched_count', '>', 0)
            ->whereDoesntHave('matches', fn ($query) => $query
                ->where('outcome', RequestMatch::OUTCOME_PENDING))
            ->get()
            ->map(fn (ServiceRequest $request) => self::row(
                $request->reference,
                "Von allen {$request->matched_count} Partnern abgelehnt",
                $request->created_at,
                route('admin.requests.show', $request),
            ))
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private static function withoutCover(): array
    {
        // A test is never matched on purpose, so it would otherwise raise this
        // row the moment it was submitted and never stop.
        return ServiceRequest::query()
            ->where('is_test', false)
            ->where('status', ServiceRequest::STATUS_NEW)
            ->where('matched_count', 0)
            ->get()
            ->map(fn (ServiceRequest $request) => self::row(
                $request->reference,
                "Kein Partner im PLZ-Gebiet {$request->postal_code}",
                $request->created_at,
                route('admin.requests.show', $request),
            ))
            ->all();
    }

    /*
     * There was a "Gutachten seit N Tagen nicht hochgeladen" row here.
     *
     * Completion stopped requiring the report and the customer's invoice — see
     * CompleteAssignmentAction — but this kept counting the days since a file
     * nobody is asked for. Every accepted job therefore turned into a warning a
     * week later, and the one list that is supposed to mean "somebody must act"
     * filled up with rows nobody could act on. Uploading is still offered in the
     * portal for partners who want to attach the report; it is simply not
     * something to be chased.
     */

    /** @return array<int, array<string, mixed>> */
    private static function lapsingCover(): array
    {
        return AssessorDocument::query()
            ->where('type', AssessorDocument::TYPE_LIABILITY)
            ->whereNotNull('valid_until')
            ->where('valid_until', '<=', now()->addDays(self::COVER_EXPIRY_DAYS))
            ->with('assessor')
            ->get()
            ->filter(fn (AssessorDocument $document) => $document->assessor !== null)
            ->map(fn (AssessorDocument $document) => self::row(
                $document->assessor->partnerId(),
                $document->valid_until->isPast()
                    ? 'Haftpflichtnachweis ist abgelaufen'
                    : 'Haftpflichtnachweis läuft am '.$document->valid_until->format('d.m.').' ab',
                $document->valid_until,
                route('admin.assessors.show', $document->assessor),
            ))
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    /**
     * Mail that could not be sent.
     *
     * A changed mailbox password once stopped every e-mail for a day without
     * anybody noticing: the application reported "gesendet", the jobs quietly
     * moved to the failed table, and the first sign of trouble was a customer
     * who never heard back. It belongs at the top of the same list as every
     * other thing somebody has to act on.
     */
    private static function undeliveredMail(): array
    {
        try {
            $failed = DB::table('failed_jobs')->count();

            if ($failed === 0) {
                return [];
            }

            $oldest = DB::table('failed_jobs')->orderBy('failed_at')->value('failed_at');
            $since = $oldest ? Carbon::parse($oldest) : now();
        } catch (Throwable) {
            // The dashboard must not fall over because a diagnostic could not
            // read a table.
            return [];
        }

        $paused = QueueHealth::isPaused()
            ? ' — die Warteschlange pausiert, bis die Ursache behoben ist'
            : '';

        return [self::row(
            'E-Mail',
            "{$failed} E-Mails konnten nicht versendet werden{$paused}",
            $since,
            route('admin.system'),
        )];
    }

    private static function staleCommissions(): array
    {
        return Commission::query()
            ->where('status', Commission::STATUS_OPEN)
            ->where('created_at', '<=', now()->subDays(self::STALE_COMMISSION_DAYS))
            ->with('assignment.serviceRequest')
            ->get()
            ->map(function (Commission $commission) {
                $days = (int) $commission->created_at->diffInDays(now());

                return self::row(
                    $commission->assignment?->serviceRequest?->reference ?? "PRO-{$commission->id}",
                    "Provision seit {$days} Tagen offen",
                    $commission->created_at,
                    route('admin.commissions.show', $commission),
                );
            })
            ->all();
    }

    /** @return array<string, mixed> */
    private static function row(string $reference, string $matter, Carbon $since, string $href): array
    {
        $days = (int) abs($since->diffInDays(now()));

        return [
            'reference' => $reference,
            'matter' => $matter,
            'days' => $days,
            'since_label' => $days === 1 ? '1 Tag' : "{$days} Tage",
            'href' => $href,
        ];
    }
}
