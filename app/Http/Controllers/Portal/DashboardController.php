<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentStatusEvent;
use App\Models\Commission;
use App\Models\RequestMatch;
use App\Support\Formatter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $assessor = $request->user()->assessor;

        $monthStart = now()->startOfMonth();
        $previousMonth = $monthStart->copy()->subMonth();

        $openRequests = RequestMatch::where('assessor_id', $assessor->id)->pending();

        $commissionThisMonth = Commission::where('assessor_id', $assessor->id)
            ->where('created_at', '>=', $monthStart)
            ->selectRaw('COALESCE(SUM(commission_cents), 0) AS commission, COALESCE(SUM(fee_cents), 0) AS fee')
            ->first();

        return Inertia::render('Portal/Dashboard', [
            'stats' => [
                'open_requests' => (clone $openRequests)->count(),
                'due_today' => (clone $openRequests)
                    ->whereHas('serviceRequest', fn ($query) => $query
                        ->whereNotNull('accept_deadline_at')
                        ->whereDate('accept_deadline_at', today()))
                    ->count(),
                'open_assignments' => Assignment::where('assessor_id', $assessor->id)->open()->count(),
                'in_progress' => Assignment::where('assessor_id', $assessor->id)
                    ->whereIn('status', [Assignment::STATUS_IN_PROGRESS, Assignment::STATUS_DOCUMENTS_UPLOADED])
                    ->count(),
                'completed_this_month' => Assignment::where('assessor_id', $assessor->id)
                    ->where('status', Assignment::STATUS_COMPLETED)
                    ->where('completed_at', '>=', $monthStart)->count(),
                'completed_previous_month' => Assignment::where('assessor_id', $assessor->id)
                    ->where('status', Assignment::STATUS_COMPLETED)
                    ->whereBetween('completed_at', [$previousMonth, $monthStart])->count(),
                'commission_this_month_cents' => (int) $commissionThisMonth->commission,
                'fee_this_month_cents' => (int) $commissionThisMonth->fee,
                'open_commission_cents' => (int) Commission::where('assessor_id', $assessor->id)
                    ->open()->sum('commission_cents'),
                'month_label' => Formatter::monthName($monthStart),
                'previous_month_label' => Formatter::monthName($previousMonth),
            ],
            'latestMatches' => RequestMatch::where('assessor_id', $assessor->id)
                ->pending()
                ->with('serviceRequest.serviceType')
                ->latest('notified_at')
                ->take(4)
                ->get()
                ->map(fn (RequestMatch $match) => [
                    'id' => $match->id,
                    'reference' => $match->serviceRequest->reference,
                    'location' => $match->serviceRequest->locationLabel(),
                    'service_type' => $match->serviceRequest->serviceType?->name_de,
                    'notified_at' => $match->notified_at,
                    'href' => route('portal.requests.show', $match->serviceRequest),
                ])
                ->all(),
            'activity' => $this->activityTrail($assessor->id),
            'availability' => $assessor->is_available,
        ]);
    }

    /**
     * The "Verlauf" panel: the partner's own recent movements, newest first.
     * Drawn from assignment status events rather than the activity log, so it
     * shows what the partner did and not what an administrator did to them.
     *
     * @return array<int, array<string, mixed>>
     */
    private function activityTrail(int $assessorId): array
    {
        return AssignmentStatusEvent::query()
            ->whereHas('assignment', fn ($query) => $query->where('assessor_id', $assessorId))
            ->with('assignment.serviceRequest')
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn (AssignmentStatusEvent $event) => [
                'id' => $event->id,
                'label' => $event->label(),
                'reference' => $event->assignment?->serviceRequest?->shortReference(),
                'at' => $event->created_at,
            ])
            ->all();
    }
}
