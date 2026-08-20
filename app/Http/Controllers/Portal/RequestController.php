<?php

namespace App\Http\Controllers\Portal;

use App\Actions\AcceptAssignmentAction;
use App\Actions\DeclineRequestAction;
use App\Exceptions\RequestAlreadyAssignedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequestController extends Controller
{
    public function index(Request $request): Response
    {
        $assessor = $request->user()->assessor;

        $filters = $request->validate([
            'suche' => ['nullable', 'string', 'max:100'],
            'art' => ['nullable', 'integer', 'exists:service_types,id'],
        ]);

        $matches = RequestMatch::query()
            ->where('assessor_id', $assessor->id)
            ->pending()
            ->whereHas('serviceRequest', function ($query) use ($filters) {
                $query->where('status', ServiceRequest::STATUS_MATCHED);

                // Reference or place — the two things a partner has at hand
                // when someone rings about a request.
                if (filled($filters['suche'] ?? null)) {
                    $term = '%'.$filters['suche'].'%';
                    $query->where(fn ($q) => $q
                        ->where('reference', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('postal_code', 'like', $term));
                }

                if (filled($filters['art'] ?? null)) {
                    $query->where('service_type_id', $filters['art']);
                }

            })
            ->with(['serviceRequest.serviceType', 'serviceRequest' => fn ($q) => $q->withCount('images')])
            ->latest('notified_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Portal/Anfragen', [
            'requests' => $matches->through(fn (RequestMatch $match) => array_merge(
                (new ServiceRequestResource($match->serviceRequest))->toArray($request),
                ['notified_at' => $match->notified_at, 'match_id' => $match->id],
            )),
            'serviceTypes' => ServiceType::orderBy('sort_order')->get(['id', 'name_de']),
            'areaLabel' => $assessor->serviceAreaLabel(),
            'filters' => [
                'suche' => $filters['suche'] ?? '',
                'art' => $filters['art'] ?? null,
            ],
        ]);
    }

    public function show(Request $request, ServiceRequest $serviceRequest): Response
    {
        $assessor = $request->user()->assessor;

        $match = RequestMatch::where('service_request_id', $serviceRequest->id)
            ->where('assessor_id', $assessor->id)
            ->firstOrFail();

        // Record that the partner opened it — the admin matching trail shows
        // who looked and when, not just who answered.
        if ($match->viewed_at === null) {
            $match->update(['viewed_at' => now()]);
        }

        $serviceRequest->load('serviceType')->loadCount('images');

        return Inertia::render('Portal/Anfrage', [
            'request' => (new ServiceRequestResource($serviceRequest))->toArray($request),
            'match' => [
                'outcome' => $match->outcome,
                'outcome_label' => $match->outcomeLabel(),
                'notified_at' => $match->notified_at,
                'is_open' => $match->isPending() && $serviceRequest->status === ServiceRequest::STATUS_MATCHED,
            ],
        ]);
    }

    public function accept(
        Request $request,
        ServiceRequest $serviceRequest,
        AcceptAssignmentAction $accept,
    ): RedirectResponse {
        $assessor = $request->user()->assessor;

        RequestMatch::where('service_request_id', $serviceRequest->id)
            ->where('assessor_id', $assessor->id)
            ->firstOrFail();

        try {
            $assignment = $accept->execute($serviceRequest, $assessor);
        } catch (RequestAlreadyAssignedException $e) {
            // Not an error page: the partner simply lost the race.
            return redirect()
                ->route('portal.requests')
                ->with('info', $e->getMessage());
        }

        return redirect()
            ->route('portal.assignments.show', $assignment)
            ->with('success', 'Sie haben den Auftrag übernommen. Die Kontaktdaten sind jetzt sichtbar.');
    }

    public function decline(
        Request $request,
        ServiceRequest $serviceRequest,
        DeclineRequestAction $decline,
    ): RedirectResponse {
        $data = $request->validate(
            ['reason' => ['nullable', 'string', 'max:200']],
            [],
            ['reason' => 'die Begründung']
        );

        $decline->execute($serviceRequest, $request->user()->assessor, $data['reason'] ?? null);

        return redirect()
            ->route('portal.requests')
            ->with('success', 'Die Anfrage wurde abgelehnt. Das wirkt sich nicht auf die weitere Verteilung aus.');
    }

    /**
     * Every way a request left this partner's queue without becoming an
     * assignment: they declined it, someone else took it, or the acceptance
     * window ran out. Grouping the three is what makes the screen honest —
     * a partner who only ever sees their own declines cannot tell whether
     * they are being outpaced or simply not matched.
     */
    public function declined(Request $request): Response
    {
        $visibleDays = 90;

        $matches = RequestMatch::query()
            ->where('assessor_id', $request->user()->assessor->id)
            ->whereIn('outcome', [
                RequestMatch::OUTCOME_DECLINED,
                RequestMatch::OUTCOME_CLOSED,
                RequestMatch::OUTCOME_EXPIRED,
            ])
            ->where('updated_at', '>=', now()->subDays($visibleDays))
            ->with('serviceRequest.serviceType')
            ->latest('responded_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Portal/Abgelehnt', [
            'requests' => $matches->through(fn (RequestMatch $match) => array_merge(
                (new ServiceRequestResource($match->serviceRequest))->toArray($request),
                [
                    'outcome' => $match->outcome,
                    'outcome_label' => $match->outcomeLabel(),
                    'responded_at' => $match->responded_at ?? $match->updated_at,
                    'decline_reason' => $match->decline_reason,
                ],
            )),
            'visibleDays' => $visibleDays,
        ]);
    }
}
