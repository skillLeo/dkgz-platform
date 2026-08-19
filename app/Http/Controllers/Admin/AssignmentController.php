<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentDocument;
use App\Support\Formatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Assignment::class);

        $assignments = Assignment::query()
            ->with(['assessor', 'serviceRequest.serviceType', 'commission'])
            ->when($request->filled('suche'), fn ($q) => $q->where(function ($inner) use ($request) {
                $term = '%'.$request->string('suche')->toString().'%';
                $inner->whereHas('serviceRequest', fn ($s) => $s->where('reference', 'like', $term))
                    ->orWhereHas('assessor', fn ($a) => $a->where('company_name', 'like', $term));
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy(
                $request->string('sort')->toString() ?: 'accepted_at',
                $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc'
            )
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Auftraege', [
            'assignments' => $assignments->through(fn (Assignment $a) => [
                'id' => $a->id,
                'reference' => $a->serviceRequest?->reference,
                'assessor' => $a->assessor?->company_name,
                'service_type' => $a->serviceRequest?->serviceType?->name_de,
                'location' => $a->serviceRequest?->locationLabel(),
                'status' => $a->status,
                'accepted_at' => $a->accepted_at,
                'completed_at' => $a->completed_at,
                'fee_cents' => $a->fee_cents,
                'commission_cents' => $a->commission?->commission_cents,
            ]),
            'filters' => $request->only(['suche', 'status', 'sort', 'direction']),
            'statusOptions' => [
                Assignment::STATUS_ACCEPTED => 'Angenommen',
                Assignment::STATUS_IN_PROGRESS => 'In Bearbeitung',
                Assignment::STATUS_DOCUMENTS_UPLOADED => 'Unterlagen hochgeladen',
                Assignment::STATUS_COMPLETED => 'Abgeschlossen',
                Assignment::STATUS_CANCELLED => 'Storniert',
            ],
        ]);
    }

    public function show(Request $request, Assignment $assignment): Response
    {
        $this->authorize('view', $assignment);

        $assignment->load([
            'assessor.user',
            'serviceRequest.serviceType',
            'documents',
            'statusEvents.actor',
            'commission',
            'review',
        ]);

        $serviceRequest = $assignment->serviceRequest;

        return Inertia::render('Admin/Auftrag', [
            'assignment' => [
                'id' => $assignment->id,
                'status' => $assignment->status,
                'status_label' => $assignment->statusLabel(),
                'accepted_at' => $assignment->accepted_at,
                'started_at' => $assignment->started_at,
                'completed_at' => $assignment->completed_at,
                'cancelled_at' => $assignment->cancelled_at,
                'cancellation_reason' => $assignment->cancellation_reason,
                'fee_cents' => $assignment->fee_cents,
                'assessor_notes' => $assignment->assessor_notes,
                'is_open' => $assignment->isOpen(),
            ],
            'assessor' => [
                'id' => $assignment->assessor?->id,
                'company_name' => $assignment->assessor?->company_name,
                'contact' => $assignment->assessor?->user?->fullName(),
                'email' => $assignment->assessor?->user?->email,
                'phone' => Formatter::phone($assignment->assessor?->user?->phone),
            ],
            'request' => $serviceRequest === null ? null : [
                'id' => $serviceRequest->id,
                'reference' => $serviceRequest->reference,
                'service_type' => $serviceRequest->serviceType?->name_de,
                'location' => $serviceRequest->locationLabel(),
                'vehicle' => $serviceRequest->vehicleLabel(),
                'customer_name' => $serviceRequest->customer_name,
                'customer_phone' => Formatter::phone($serviceRequest->customer_phone),
                'customer_email' => $serviceRequest->customer_email,
            ],
            'documents' => $assignment->documents->map(fn (AssignmentDocument $d) => [
                'id' => $d->id,
                'type_label' => $d->typeLabel(),
                'original_name' => $d->original_name,
                'size_label' => Formatter::fileSize($d->size_bytes),
                'uploaded_at' => $d->uploaded_at,
                'download_url' => route('admin.assignments.documents.download', [$assignment, $d]),
            ]),
            'timeline' => $assignment->statusEvents->map(fn ($e) => [
                'to_status' => $e->to_status,
                'actor_type' => $e->actor_type,
                'actor' => $e->actor?->fullName(),
                'note' => $e->note,
                'created_at' => $e->created_at,
            ]),
            'commission' => $assignment->commission === null ? null : [
                'id' => $assignment->commission->id,
                'commission_cents' => $assignment->commission->commission_cents,
                'rate_percent' => (float) $assignment->commission->rate_percent,
                'status' => $assignment->commission->status,
                'status_label' => $assignment->commission->statusLabel(),
            ],
            'review' => $assignment->review === null ? null : [
                'rating' => $assignment->review->rating,
                'feedback_category' => $assignment->review->feedback_category,
                'feedback' => $assignment->review->feedback,
                'submitted_at' => $assignment->review->submitted_at,
            ],
            'can' => [
                'cancel' => $request->user()->can('cancel', $assignment),
                'edit' => $request->user()->can('update', $assignment),
            ],
        ]);
    }

    public function cancel(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize('cancel', $assignment);

        $data = $request->validate(
            ['reason' => ['required', 'string', 'max:500']],
            ['reason.required' => 'Bitte geben Sie eine Begründung an.'],
            ['reason' => 'die Begründung']
        );

        $previous = $assignment->status;

        $assignment->update([
            'status' => Assignment::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $data['reason'],
        ]);

        $assignment->recordStatusEvent(
            $previous,
            Assignment::STATUS_CANCELLED,
            'admin',
            $request->user()->id,
            $data['reason'],
        );

        return back()->with('success', 'Der Auftrag wurde storniert.');
    }

    public function downloadDocument(Request $request, Assignment $assignment, AssignmentDocument $document): StreamedResponse
    {
        $this->authorize('downloadDocument', $assignment);
        abort_unless($document->assignment_id === $assignment->id, 404);

        $disk = Storage::disk(AssignmentDocument::DISK);
        abort_unless($disk->exists($document->path), 404);

        return $disk->download($document->path, $document->original_name);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Assignment::class);

        return response()->streamDownload(function () {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));
            $csv->setDelimiter(';');
            $csv->insertOne([
                'Referenz', 'Sachverständiger', 'Art', 'Status',
                'Angenommen am', 'Abgeschlossen am', 'Honorar netto', 'Provision',
            ]);

            Assignment::with(['assessor', 'serviceRequest.serviceType', 'commission'])
                ->chunk(200, function ($rows) use ($csv) {
                    foreach ($rows as $a) {
                        $csv->insertOne([
                            $a->serviceRequest?->reference,
                            $a->assessor?->company_name,
                            $a->serviceRequest?->serviceType?->name_de,
                            $a->statusLabel(),
                            Formatter::date($a->accepted_at),
                            $a->completed_at ? Formatter::date($a->completed_at) : '',
                            $a->fee_cents ? Formatter::amount($a->fee_cents) : '',
                            $a->commission ? Formatter::amount($a->commission->commission_cents) : '',
                        ]);
                    }
                });
        }, 'auftraege-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
