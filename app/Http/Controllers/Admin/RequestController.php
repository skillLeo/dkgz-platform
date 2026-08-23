<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateServiceRequestAction;
use App\Actions\MatchRequestAction;
use App\Actions\OfferRequestExternallyAction;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyCustomerNoResponseJob;
use App\Jobs\NotifyMatchedAssessorsJob;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\Assessor;
use App\Models\RequestMatch;
use App\Models\RequestOffer;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Support\Formatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Inertia\Inertia;
use Inertia\Response;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestController extends Controller
{
    /** Filter value covering every request no assessor has accepted yet. */
    private const FILTER_IN_PLACEMENT = 'in_vermittlung';

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
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->string('status')->toString();

                // "In Vermittlung" is one thing to an operator and two rows in
                // the table — a request nobody has been sent yet and one sent
                // but unanswered. The filter follows the operator's meaning.
                return $status === self::FILTER_IN_PLACEMENT
                    ? $q->whereIn('status', [ServiceRequest::STATUS_NEW, ServiceRequest::STATUS_MATCHED])
                    : $q->where('status', $status);
            })
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
            'offers',
        ]);

        return Inertia::render('Admin/Anfrage', [
            'customerNotifiedAt' => $serviceRequest->customer_notified_at,
            'canClose' => $request->user()->can('close', $serviceRequest)
                && ! in_array($serviceRequest->status, [
                    ServiceRequest::STATUS_ASSIGNED,
                    ServiceRequest::STATUS_COMPLETED,
                    ServiceRequest::STATUS_CANCELLED,
                ], true),
            'canNotifyCustomer' => in_array($serviceRequest->status, [
                ServiceRequest::STATUS_UNANSWERED,
                ServiceRequest::STATUS_CANCELLED,
            ], true) && filled($serviceRequest->customer_email),
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
            'matching' => $this->matchingDiagnosis($serviceRequest),
            // Every partner the office could send this to by hand, whether or
            // not their postal range covers it — a request nobody covers is
            // exactly the one that needs placing by a person.
            'assessorOptions' => $serviceRequest->assignment !== null ? [] : Assessor::query()
                ->approved()
                ->with('user:id,is_active')
                ->orderBy('company_name')
                ->get(['id', 'company_name', 'city', 'postal_code', 'is_available'])
                ->map(fn (Assessor $a) => [
                    'value' => $a->id,
                    'label' => $a->company_name.($a->city ? ' · '.$a->city : ''),
                    'available' => (bool) $a->is_available,
                    'notified' => $serviceRequest->matches->contains('assessor_id', $a->id),
                ])
                ->values()
                ->all(),
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
            // Hand-sent offers to people who are not partners yet, shown
            // alongside the matching trail because to an operator they are the
            // same question: who has this request reached?
            'offers' => $serviceRequest->offers
                ->sortByDesc('sent_at')
                ->values()
                ->map(fn (RequestOffer $offer) => [
                    'id' => $offer->id,
                    'email' => $offer->email,
                    'name' => $offer->name,
                    'status' => $offer->statusTone(),
                    'status_label' => $offer->statusLabel(),
                    'sent_at_label' => Formatter::dateTime($offer->sent_at),
                    'viewed_at_label' => $offer->viewed_at ? Formatter::dateTime($offer->viewed_at) : null,
                    'answered_at_label' => $offer->accepted_at || $offer->declined_at
                        ? Formatter::dateTime($offer->accepted_at ?? $offer->declined_at)
                        : null,
                    'decline_reason' => $offer->decline_reason,
                    'link' => route('offer.show', $offer->token),
                ]),
            'canOffer' => $serviceRequest->isOpen(),
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
    /**
     * Sends this request to an assessor who has no account yet.
     *
     * The route exists because matching can only reach people who registered,
     * and a request in an uncovered region otherwise has nowhere to go.
     */
    public function offerExternally(
        Request $request,
        ServiceRequest $serviceRequest,
        OfferRequestExternallyAction $offer,
    ): RedirectResponse {
        $this->authorize('reassign', $serviceRequest);

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'email' => 'die E-Mail-Adresse',
            'name' => 'der Name',
            'message' => 'die Nachricht',
        ]);

        try {
            $offer->execute(
                $serviceRequest,
                $data['email'],
                $data['name'] ?? null,
                $data['message'] ?? null,
                $request->user(),
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['email' => $e->getMessage()]);
        }

        return back()->with('success', 'Die Anfrage wurde an '.$data['email'].' gesendet.');
    }

    /**
     * Sends the request to one assessor the matching engine passed over.
     *
     * The engine is deliberately strict — unavailable partners and lapsed cover
     * are excluded for good reasons — but the office knows things it does not:
     * that somebody is free again, or will take this one as a favour. This is
     * the manual override, and it is recorded like any other notification so
     * the trail stays complete.
     */
    public function notifyAssessor(
        Request $request,
        ServiceRequest $serviceRequest,
        Assessor $assessor,
    ): RedirectResponse {
        $this->authorize('reassign', $serviceRequest);

        abort_if($serviceRequest->assignment !== null, 409);

        $existing = RequestMatch::where('service_request_id', $serviceRequest->id)
            ->where('assessor_id', $assessor->id)
            ->first();

        if ($existing !== null && $existing->outcome === RequestMatch::OUTCOME_PENDING) {
            return back()->with('info', 'Dieser Sachverständige hat die Anfrage bereits offen.');
        }

        DB::transaction(function () use ($serviceRequest, $assessor, $existing) {
            $existing === null
                ? RequestMatch::create([
                    'service_request_id' => $serviceRequest->id,
                    'assessor_id' => $assessor->id,
                    'outcome' => RequestMatch::OUTCOME_PENDING,
                    'notified_at' => now(),
                ])
                // Somebody who declined can be asked again; the earlier answer
                // is superseded rather than duplicated.
                : $existing->update([
                    'outcome' => RequestMatch::OUTCOME_PENDING,
                    'notified_at' => now(),
                    'responded_at' => null,
                    'decline_reason' => null,
                ]);

            $serviceRequest->update([
                'status' => ServiceRequest::STATUS_MATCHED,
                'matched_count' => $serviceRequest->matches()->count(),
            ]);
        });

        NotifyMatchedAssessorsJob::dispatch($serviceRequest->id, [$assessor->id]);

        return back()->with('success', "Die Anfrage wurde an {$assessor->company_name} gesendet.");
    }

    /**
     * Why the assessors who cover this postal code did not all receive it.
     *
     * "The postal code matching is unreliable" is almost always this: the range
     * is right and something else disqualified the partner — unavailable, the
     * assessment type not offered, cover lapsed. Without this the office can
     * only see who was contacted, never who nearly was and why not.
     *
     * @return array<string, mixed>
     */
    private function matchingDiagnosis(ServiceRequest $serviceRequest): array
    {
        $covering = Assessor::covering($serviceRequest->postal_code)
            ->with(['user:id,is_active', 'serviceTypes:id'])
            ->get();

        $eligible = app(MatchRequestAction::class)->matchingAssessorIds($serviceRequest);

        return [
            'postal_code' => $serviceRequest->postal_code,
            'covering_count' => $covering->count(),
            'eligible_count' => $eligible->count(),
            'excluded' => $covering
                ->reject(fn (Assessor $a) => $eligible->contains($a->id))
                ->map(fn (Assessor $a) => [
                    'id' => $a->id,
                    'company_name' => $a->company_name,
                    'already_notified' => $serviceRequest->matches
                        ->contains('assessor_id', $a->id),
                    'reasons' => array_values(array_filter([
                        $a->approval_status !== Assessor::STATUS_APPROVED ? 'Nicht freigegeben' : null,
                        ! $a->is_available ? 'Als nicht verfügbar markiert' : null,
                        ! ($a->user?->is_active) ? 'Zugang gesperrt' : null,
                        ! $a->serviceTypes->contains('id', $serviceRequest->service_type_id)
                            ? 'Bietet diese Leistungsart nicht an' : null,
                    ])) ?: ['Nachweis der Haftpflicht fehlt oder ist abgelaufen'],
                ])
                ->values()
                ->all(),
        ];
    }

    /** The form for a request taken over the telephone. */
    public function create(Request $request): Response
    {
        $this->authorize('create', ServiceRequest::class);

        return Inertia::render('Admin/AnfrageAnlegen', [
            'serviceTypes' => ServiceType::active()->ordered()->get(['id', 'name_de', 'description_de']),
        ]);
    }

    /**
     * Creates a request by hand, exactly as the public form does.
     *
     * The office takes requests by telephone, and a customer who rings rather
     * than types must end up in the same place: same confirmation e-mail, same
     * matching run, same partners notified. That is why this calls the same
     * action rather than writing its own row — the two cannot drift apart.
     *
     * The honeypot and the timing check do not apply here: an operator typing
     * into the admin panel is already authenticated, and a three-second floor
     * would only punish somebody who types quickly.
     */
    public function store(
        Request $request,
        CreateServiceRequestAction $create,
    ): RedirectResponse {
        $this->authorize('create', ServiceRequest::class);

        $data = $request->validate(
            (new StoreServiceRequestRequest)->rulesForOperator(),
            [],
            [
                'service_type_id' => 'die Leistungsart',
                'postal_code' => 'die Postleitzahl',
                'city' => 'der Standort',
                'customer_name' => 'der Name',
                'customer_phone' => 'die Telefonnummer',
                'customer_email' => 'die E-Mail-Adresse',
                'vehicle_make' => 'die Marke',
                'vehicle_model' => 'das Modell',
            ]
        );

        // No IP and no user agent: the request came down a telephone line, and
        // recording the office's own address as the customer's would be a lie
        // in the one field that exists to answer where this came from.
        $serviceRequest = $create->execute($data, $request->file('images'));

        activity()->performedOn($serviceRequest)
            ->log('Anfrage telefonisch aufgenommen.');

        return redirect()
            ->route('admin.requests.show', $serviceRequest)
            ->with('success', "Die Anfrage {$serviceRequest->reference} wurde angelegt und vermittelt.");
    }

    /**
     * Everything still waiting for an assessor to say yes.
     *
     * These are the requests the office has to push: they were sent out and
     * nobody has taken them, or nobody could be found to send them to. They
     * were previously mixed into the full list, where a request quietly going
     * nowhere looked exactly like one that had already been placed.
     */
    public function inPlacement(Request $request): Response
    {
        $this->authorize('viewAny', ServiceRequest::class);

        $requests = ServiceRequest::query()
            ->with('serviceType')
            ->whereIn('status', [ServiceRequest::STATUS_NEW, ServiceRequest::STATUS_MATCHED])
            ->when($request->filled('suche'), function ($q) use ($request) {
                $term = '%'.$request->string('suche')->toString().'%';
                $q->where(fn ($inner) => $inner
                    ->where('reference', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('postal_code', 'like', $term));
            })
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/InVermittlung', [
            'requests' => $requests->through(fn (ServiceRequest $r) => [
                'id' => $r->id,
                'reference' => $r->reference,
                'service_type' => $r->serviceType?->name_de,
                'location' => $r->locationLabel(),
                'customer_name' => $r->customer_name,
                'matched_count' => $r->matched_count,
                // Nobody has answered yet; how long that has been true is the
                // one number that decides whether this needs a person.
                'waiting_since' => $r->created_at,
                'waiting_label' => Formatter::dateTime($r->created_at),
                'open_matches' => $r->matches()->where('outcome', RequestMatch::OUTCOME_PENDING)->count(),
                'needs_attention' => $r->isUnmatched() || $r->isFullyDeclined(),
            ]),
            'filters' => ['suche' => $request->string('suche')->toString()],
            'counts' => [
                'total' => ServiceRequest::whereIn('status', [ServiceRequest::STATUS_NEW, ServiceRequest::STATUS_MATCHED])->count(),
                'unmatched' => ServiceRequest::where('status', ServiceRequest::STATUS_NEW)
                    ->where('matched_count', 0)->count(),
            ],
        ]);
    }

    private function statusOptions(): array
    {
        return [
            self::FILTER_IN_PLACEMENT => 'In Vermittlung',
            ServiceRequest::STATUS_ASSIGNED => 'Vergeben',
            ServiceRequest::STATUS_COMPLETED => 'Abgeschlossen',
            ServiceRequest::STATUS_CANCELLED => 'Storniert',
            ServiceRequest::STATUS_UNANSWERED => 'Ohne Rückmeldung',
        ];
    }

    /**
     * Sends the "we could not place this" mail again, by hand.
     *
     * The job refuses to send twice on its own, so the stamp is cleared first —
     * a person choosing to resend has decided the customer needs telling again,
     * usually because the first attempt bounced.
     */
    public function notifyCustomer(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('close', $serviceRequest);

        abort_unless(in_array($serviceRequest->status, [
            ServiceRequest::STATUS_UNANSWERED,
            ServiceRequest::STATUS_CANCELLED,
        ], true), 422);

        $serviceRequest->forceFill(['customer_notified_at' => null])->save();

        NotifyCustomerNoResponseJob::dispatch($serviceRequest->id);

        return back()->with('success', 'Die Nachricht an den Kunden wurde erneut in die Warteschlange gestellt.');
    }

    /**
     * Closes a request by hand.
     *
     * With the acceptance deadline gone, nothing closes a request on a timer —
     * so an administrator needs a way to end one that has run its course, and
     * to say why. Closing tells the customer, which is the point: the
     * alternative is a request that sits open forever with nobody informed.
     */
    public function close(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('close', $serviceRequest);

        abort_if(in_array($serviceRequest->status, [
            ServiceRequest::STATUS_ASSIGNED,
            ServiceRequest::STATUS_COMPLETED,
            ServiceRequest::STATUS_CANCELLED,
        ], true), 422, 'Dieser Vorgang kann nicht mehr geschlossen werden.');

        $data = $request->validate(
            ['reason' => ['required', 'string', 'min:5', 'max:500']],
            ['reason.required' => 'Bitte geben Sie an, warum der Vorgang geschlossen wird.'],
            ['reason' => 'der Grund']
        );

        DB::transaction(function () use ($serviceRequest, $data) {
            RequestMatch::where('service_request_id', $serviceRequest->id)
                ->pending()
                ->update([
                    'outcome' => RequestMatch::OUTCOME_CLOSED,
                    'responded_at' => now(),
                    'updated_at' => now(),
                ]);

            $serviceRequest->update([
                'status' => ServiceRequest::STATUS_CANCELLED,
                'internal_notes' => trim(($serviceRequest->internal_notes ?? '')."\n"
                    .now()->format('d.m.Y H:i').' — manuell geschlossen: '.$data['reason']),
            ]);
        });

        activity()
            ->performedOn($serviceRequest)
            ->withProperties(['reason' => $data['reason']])
            ->log('Anfrage manuell geschlossen');

        NotifyCustomerNoResponseJob::dispatch($serviceRequest->id);

        return back()->with('success', 'Der Vorgang wurde geschlossen und der Kunde benachrichtigt.');
    }
}
