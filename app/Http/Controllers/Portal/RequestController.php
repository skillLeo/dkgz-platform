<?php

namespace App\Http\Controllers\Portal;

use App\Actions\AcceptAssignmentAction;
use App\Actions\DeclineRequestAction;
use App\Exceptions\RequestAlreadyAssignedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequestController extends Controller
{
    public function index(Request $request): Response
    {
        $assessor = $request->user()->assessor;

        $matches = RequestMatch::query()
            ->where('assessor_id', $assessor->id)
            ->pending()
            ->whereHas('serviceRequest', fn ($q) => $q->where('status', ServiceRequest::STATUS_MATCHED))
            ->with(['serviceRequest.serviceType', 'serviceRequest' => fn ($q) => $q->withCount('images')])
            ->latest('notified_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Portal/Anfragen', [
            'requests' => $matches->through(fn (RequestMatch $match) => array_merge(
                (new ServiceRequestResource($match->serviceRequest))->toArray($request),
                ['notified_at' => $match->notified_at, 'match_id' => $match->id],
            )),
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

    public function declined(Request $request): Response
    {
        $matches = RequestMatch::query()
            ->where('assessor_id', $request->user()->assessor->id)
            ->where('outcome', RequestMatch::OUTCOME_DECLINED)
            ->with('serviceRequest.serviceType')
            ->latest('responded_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Portal/Abgelehnt', [
            'requests' => $matches->through(fn (RequestMatch $match) => array_merge(
                (new ServiceRequestResource($match->serviceRequest))->toArray($request),
                ['responded_at' => $match->responded_at, 'decline_reason' => $match->decline_reason],
            )),
        ]);
    }
}
