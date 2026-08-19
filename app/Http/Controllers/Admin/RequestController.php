<?php

namespace App\Http\Controllers\Admin;

use App\Actions\MatchRequestAction;
use App\Http\Controllers\Controller;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Support\Formatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServiceRequest::class);

        $requests = ServiceRequest::query()
            ->with('serviceType')
            ->withCount('matches')
            ->when($request->filled('suche'), fn ($q) => $q->where(function ($inner) use ($request) {
                $term = '%'.$request->string('suche')->toString().'%';
                $inner->where('reference', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('postal_code', 'like', $term);
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('leistungsart'), fn ($q) => $q->where('service_type_id', $request->integer('leistungsart')))
            ->when($request->boolean('nicht_vermittelt'), fn ($q) => $q->needsAttention())
            ->orderBy(
                $request->string('sort')->toString() ?: 'created_at',
                $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc'
            )
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Anfragen', [
            'requests' => $requests->through(fn (ServiceRequest $r) => [
                'id' => $r->id,
                'reference' => $r->reference,
                'status' => $r->status,
                'service_type' => $r->serviceType?->name_de,
                'location' => $r->locationLabel(),
                'customer_name' => $r->customer_name,
                'matched_count' => $r->matched_count,
                'created_at' => $r->created_at,
                'created_at_label' => Formatter::dateTime($r->created_at),
                'needs_attention' => $r->isUnmatched() || $r->isFullyDeclined(),
            ]),
            'filters' => $request->only(['suche', 'status', 'leistungsart', 'nicht_vermittelt', 'sort', 'direction']),
            'serviceTypes' => ServiceType::ordered()->get(['id', 'name_de']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function show(Request $request, ServiceRequest $serviceRequest): Response
    {
        $this->authorize('view', $serviceRequest);

        $serviceRequest->load([
            'serviceType',
            'images',
            'assignment.assessor.user',
            'matches.assessor.user',
        ]);

        return Inertia::render('Admin/Anfrage', [
            'request' => [
                'id' => $serviceRequest->id,
                'reference' => $serviceRequest->reference,
                'status' => $serviceRequest->status,
                'service_type' => $serviceRequest->serviceType?->name_de,
                'postal_code' => $serviceRequest->postal_code,
                'city' => $serviceRequest->city,
                'customer_name' => $serviceRequest->customer_name,
                'customer_phone' => Formatter::phone($serviceRequest->customer_phone),
                'customer_email' => $serviceRequest->customer_email,
                'vehicle' => $serviceRequest->vehicleLabel(),
                'vehicle_plate' => $serviceRequest->vehicle_plate,
                'vehicle_vin' => $serviceRequest->vehicle_vin,
                'description' => $serviceRequest->description,
                'urgency' => $serviceRequest->urgency,
                'preferred_date' => $serviceRequest->preferred_date,
                'matched_count' => $serviceRequest->matched_count,
                'created_at' => $serviceRequest->created_at,
                'assigned_at' => $serviceRequest->assigned_at,
                'consent_at' => $serviceRequest->consent_at,
                'ip_address' => $serviceRequest->ip_address,
                'image_count' => $serviceRequest->images->count(),
                'can_rematch' => $request->user()->can('reassign', $serviceRequest),
            ],
            // The forensic trail: who was notified, when they looked, how they
            // answered. This is what the admin screen exists to show.
            'trail' => $serviceRequest->matches
                ->sortBy('notified_at')
                ->values()
                ->map(fn (RequestMatch $match) => [
                    'id' => $match->id,
                    'assessor' => [
                        'id' => $match->assessor?->id,
                        'company_name' => $match->assessor?->company_name,
                        'city' => $match->assessor?->city,
                        'contact' => $match->assessor?->user?->fullName(),
                    ],
                    'outcome' => $match->outcome,
                    'outcome_label' => $match->outcomeLabel(),
                    'notified_at' => $match->notified_at,
                    'notified_at_label' => Formatter::dateTime($match->notified_at),
                    'viewed_at' => $match->viewed_at,
                    'viewed_at_label' => $match->viewed_at ? Formatter::dateTime($match->viewed_at) : null,
                    'responded_at' => $match->responded_at,
                    'responded_at_label' => $match->responded_at ? Formatter::dateTime($match->responded_at) : null,
                    'decline_reason' => $match->decline_reason,
                ]),
            'assignment' => $serviceRequest->assignment === null ? null : [
                'id' => $serviceRequest->assignment->id,
                'status' => $serviceRequest->assignment->status,
                'status_label' => $serviceRequest->assignment->statusLabel(),
                'assessor' => $serviceRequest->assignment->assessor?->company_name,
                'accepted_at' => $serviceRequest->assignment->accepted_at,
                'fee_cents' => $serviceRequest->assignment->fee_cents,
            ],
        ]);
    }

    /** Re-runs the matching engine, e.g. after a new partner covers the area. */
    public function rematch(Request $request, ServiceRequest $serviceRequest, MatchRequestAction $match): RedirectResponse
    {
        $this->authorize('reassign', $serviceRequest);

        // Only partners who have not already answered are re-notified.
        DB::transaction(function () use ($serviceRequest) {
            RequestMatch::where('service_request_id', $serviceRequest->id)
                ->where('outcome', RequestMatch::OUTCOME_PENDING)
                ->delete();
        });

        $count = $match->execute($serviceRequest->fresh());

        return back()->with(
            $count > 0 ? 'success' : 'warning',
            $count > 0
                ? "Die Anfrage wurde an {$count} Sachverständige vermittelt."
                : 'Es konnte weiterhin kein passender Sachverständiger ermittelt werden.'
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', ServiceRequest::class);

        return response()->streamDownload(function () {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));
            $csv->setDelimiter(';');
            $csv->insertOne([
                'Referenz', 'Status', 'Art des Gutachtens', 'PLZ', 'Ort',
                'Name', 'Telefon', 'E-Mail', 'Fahrzeug', 'Vermittelt an', 'Eingegangen am',
            ]);

            // Chunked: the host may only allow 128M and this export grows
            // without bound as the platform is used.
            ServiceRequest::with('serviceType')->chunk(200, function ($rows) use ($csv) {
                foreach ($rows as $r) {
                    $csv->insertOne([
                        $r->reference,
                        $r->status,
                        $r->serviceType?->name_de,
                        $r->postal_code,
                        $r->city,
                        $r->customer_name,
                        $r->customer_phone,
                        $r->customer_email,
                        $r->vehicleLabel(),
                        $r->matched_count,
                        Formatter::dateTime($r->created_at),
                    ]);
                }
            });
        }, 'anfragen-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** @return array<string, string> */
    private function statusOptions(): array
    {
        return [
            ServiceRequest::STATUS_NEW => 'Neu',
            ServiceRequest::STATUS_MATCHED => 'Vermittelt',
            ServiceRequest::STATUS_ASSIGNED => 'Vergeben',
            ServiceRequest::STATUS_COMPLETED => 'Abgeschlossen',
            ServiceRequest::STATUS_CANCELLED => 'Storniert',
            ServiceRequest::STATUS_EXPIRED => 'Abgelaufen',
        ];
    }
}
