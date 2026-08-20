<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\ServiceRequest;
use App\Support\AttentionQueue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /** Nine calendar weeks including the current one, oldest first. */
    private const CHART_WEEKS = 9;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServiceRequest::class);

        $attention = AttentionQueue::items();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'open_requests' => ServiceRequest::whereIn('status', [
                    ServiceRequest::STATUS_NEW, ServiceRequest::STATUS_MATCHED,
                ])->count(),
                'matched_today' => ServiceRequest::whereDate('created_at', today())
                    ->where('matched_count', '>', 0)->count(),
                'open_assignments' => Assignment::open()->count(),
                'assessors' => Assessor::whereNull('deleted_at')->count(),
                'pending_assessors' => Assessor::pending()->count(),
                'open_commission_cents' => (int) Commission::open()->sum('commission_cents'),
            ],
            'attention' => $attention,
            'attentionCount' => count($attention),
            'weekly' => $this->requestsPerWeek(),
        ]);
    }

    /**
     * Request volume per ISO week. Counted in PHP from one grouped query rather
     * than with a database week function, because WEEK() numbering differs
     * between MySQL and SQLite and the chart must read the same in tests as in
     * production.
     *
     * @return array<int, array<string, mixed>>
     */
    private function requestsPerWeek(): array
    {
        $start = now()->startOfWeek()->subWeeks(self::CHART_WEEKS - 1);

        $counts = ServiceRequest::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn (ServiceRequest $request) => $request->created_at->startOfWeek()->toDateString())
            ->map->count();

        return collect(range(0, self::CHART_WEEKS - 1))
            ->map(function (int $offset) use ($start, $counts) {
                $week = $start->copy()->addWeeks($offset);

                return [
                    'week' => $week->isoWeek(),
                    'label' => 'KW '.$week->isoWeek(),
                    'total' => (int) ($counts[$week->toDateString()] ?? 0),
                ];
            })
            ->all();
    }
}
