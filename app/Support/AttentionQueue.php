<?php

namespace App\Support;

use App\Models\AssessorDocument;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use Illuminate\Support\Carbon;

/**
 * The admin dashboard's "Erfordert Aufmerksamkeit" list.
 *
 * Everything here is a case the platform cannot resolve on its own and that
 * will quietly rot if nobody looks: a request no partner took, an area with no
 * cover at all, an order whose report never arrived, a partner whose liability
 * cover is about to lapse, a commission nobody has invoiced. Each row says what
 * happened and how long it has been true, because "how long" is what decides
 * which one to open first.
 */
class AttentionQueue
{
    public const LATE_REPORT_DAYS = 7;

    public const STALE_COMMISSION_DAYS = 30;

    public const COVER_EXPIRY_DAYS = 30;

    /** @return array<int, array<string, mixed>> */
    public static function items(int $limit = 25): array
    {
        $items = array_merge(
            self::customerUninformed(),
            self::declinedByEveryone(),
            self::withoutCover(),
            self::missingReports(),
            self::lapsingCover(),
            self::staleCommissions(),
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
            ->whereIn('status', [ServiceRequest::STATUS_EXPIRED, ServiceRequest::STATUS_UNANSWERED])
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
        return ServiceRequest::query()
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

    /** @return array<int, array<string, mixed>> */
    private static function missingReports(): array
    {
        return Assignment::query()
            ->open()
            ->where('accepted_at', '<=', now()->subDays(self::LATE_REPORT_DAYS))
            ->whereDoesntHave('documents', fn ($query) => $query->where('type', 'report'))
            ->with('serviceRequest')
            ->get()
            ->map(function (Assignment $assignment) {
                $days = (int) $assignment->accepted_at->diffInDays(now());

                return self::row(
                    $assignment->serviceRequest?->reference ?? "AUF-{$assignment->id}",
                    "Gutachten seit {$days} Tagen nicht hochgeladen",
                    $assignment->accepted_at,
                    route('admin.assignments.show', $assignment),
                );
            })
            ->all();
    }

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
