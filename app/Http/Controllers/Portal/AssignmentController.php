<?php

namespace App\Http\Controllers\Portal;

use App\Actions\CompleteAssignmentAction;
use App\Actions\ConfirmAssignmentAction;
use App\Actions\DeclineAssignmentAction;
use App\Actions\StoreAssignmentDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Assignment;
use App\Models\AssignmentDocument;
use App\Models\Commission;
use App\Support\Formatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $assessorId = $request->user()->assessor->id;

        $filters = $request->validate([
            'status' => ['nullable', 'in:aktiv,abgeschlossen'],
            'suche' => ['nullable', 'string', 'max:100'],
        ]);

        $assignments = Assignment::query()
            ->where('assessor_id', $assessorId)
            ->when(($filters['status'] ?? null) === 'aktiv', fn ($query) => $query->open())
            ->when(($filters['status'] ?? null) === 'abgeschlossen',
                fn ($query) => $query->where('status', Assignment::STATUS_COMPLETED))
            ->when(filled($filters['suche'] ?? null), function ($query) use ($filters) {
                $term = '%'.$filters['suche'].'%';
                $query->whereHas('serviceRequest', fn ($q) => $q
                    ->where('reference', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('postal_code', 'like', $term));
            })
            ->with(['serviceRequest.serviceType'])
            ->latest('accepted_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Portal/Auftraege', [
            'assignments' => $assignments->through(fn (Assignment $assignment) => [
                'id' => $assignment->id,
                'status' => $assignment->status,
                'status_label' => $assignment->statusLabel(),
                'accepted_at' => $assignment->accepted_at,
                'completed_at' => $assignment->completed_at,
                'request' => [
                    'reference' => $assignment->serviceRequest->reference,
                    'location' => $assignment->serviceRequest->locationLabel(),
                    'vehicle' => $assignment->serviceRequest->vehicleLabel(),
                    'service_type' => $assignment->serviceRequest->serviceType?->name_de,
                    'customer_initial' => $assignment->serviceRequest->customerShortName(),
                ],
            ]),
            'counts' => [
                'active' => Assignment::where('assessor_id', $assessorId)->open()->count(),
                'completed' => Assignment::where('assessor_id', $assessorId)
                    ->where('status', Assignment::STATUS_COMPLETED)->count(),
            ],
            'filters' => [
                'status' => $filters['status'] ?? null,
                'suche' => $filters['suche'] ?? '',
            ],
        ]);
    }

    public function show(Request $request, Assignment $assignment): Response
    {
        $this->authorize('view', $assignment);

        $assignment->load(['serviceRequest.serviceType', 'documents', 'statusEvents', 'commission']);

        return Inertia::render('Portal/Auftrag', [
            'assignment' => [
                'id' => $assignment->id,
                'status' => $assignment->status,
                'status_label' => $assignment->statusLabel(),
                'accepted_at' => $assignment->accepted_at,
                'started_at' => $assignment->started_at,
                'completed_at' => $assignment->completed_at,
                'assessor_notes' => $assignment->assessor_notes,
                'can_complete' => $assignment->isOpen(),
                'is_open' => $assignment->isOpen(),
                'confirmed_at' => $assignment->confirmed_at,
                'can_confirm' => $assignment->status === Assignment::STATUS_ACCEPTED,
                'decline_reasons' => DeclineAssignmentAction::REASONS,
            ],
            'request' => (new ServiceRequestResource($assignment->serviceRequest))->toArray($request),
            'documents' => $assignment->documents->map(fn (AssignmentDocument $doc) => [
                'id' => $doc->id,
                'type' => $doc->type,
                'type_label' => $doc->typeLabel(),
                'original_name' => $doc->original_name,
                'size_bytes' => $doc->size_bytes,
                'size_label' => Formatter::fileSize($doc->size_bytes),
                'uploaded_at' => $doc->uploaded_at,
                'download_url' => route('portal.assignments.documents.download', [$assignment, $doc]),
            ]),
            'timeline' => $assignment->statusEvents->map(fn ($event) => [
                'to_status' => $event->to_status,
                'label' => $this->timelineLabel($event->to_status),
                'actor_type' => $event->actor_type,
                'note' => $event->note,
                'created_at' => $event->created_at,
            ]),
            'commission' => $assignment->commission === null ? null : [
                'commission_cents' => $assignment->commission->commission_cents,
                'fee_type' => $assignment->commission->fee_type,
                'status' => $assignment->commission->status,
                'status_label' => $assignment->commission->statusLabel(),
            ],
            // Every invoice DKGZ has issued for this job, readable from inside
            // the job itself — an assessor should not have to hunt through a
            // separate register to find out what they were billed and why.
            'dkgzInvoices' => $assignment->commission === null || blank($assignment->commission->invoice_number)
                ? []
                : [[
                    'id' => $assignment->commission->id,
                    'invoice_number' => $assignment->commission->invoice_number,
                    'issued_at' => $assignment->commission->invoiced_at,
                    'issued_at_label' => Formatter::dateTime($assignment->commission->invoiced_at),
                    'amount_cents' => $assignment->commission->commission_cents,
                    'status' => $assignment->commission->status,
                    'status_label' => $assignment->commission->statusLabel(),
                    'download_url' => filled($assignment->commission->invoice_path)
                        ? route('portal.commissions.invoice', $assignment->commission)
                        : null,
                ]],
            // What DKGZ is owed for this assignment, fixed at acceptance.
            'dkgzFeeLabel' => Formatter::money(
                $assignment->dkgz_fee_snapshot_cents
                    ?? $assignment->serviceRequest?->serviceType?->dkgz_fee_cents
                    ?? 0
            ),
        ]);
    }

    public function updateStatus(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize('work', $assignment);

        $data = $request->validate([
            'status' => ['required', Rule::in([Assignment::STATUS_IN_PROGRESS])],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['status' => 'der Status']);

        $previous = $assignment->status;

        $assignment->update([
            'status' => $data['status'],
            'started_at' => $assignment->started_at ?? now(),
        ]);

        $assignment->recordStatusEvent(
            $previous,
            $data['status'],
            'assessor',
            $request->user()->id,
            $data['note'] ?? null,
        );

        return back()->with('success', 'Der Status wurde aktualisiert.');
    }

    /**
     * "Zustande gekommen": the customer engaged this assessor after all.
     *
     * This is the billable moment, so it books the fee and sends the invoice
     * rather than merely moving a label — see ConfirmAssignmentAction.
     */
    public function confirm(
        Request $request,
        Assignment $assignment,
        ConfirmAssignmentAction $confirm,
    ): RedirectResponse {
        $this->authorize('work', $assignment);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $commission = $confirm->execute($assignment, $data['note'] ?? null);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return back()->with(
            'success',
            'Der Auftrag ist als zustande gekommen bestätigt. Die Rechnung '
            .$commission->invoice_number.' wurde Ihnen per E-Mail zugestellt.'
        );
    }

    /**
     * "Nein" to the question of whether the job came about.
     *
     * A reason is required, and it is not a formality: it separates a job that
     * quietly died from one that was settled privately, which is the thing the
     * platform cannot otherwise see.
     */
    public function declineAssignment(
        Request $request,
        Assignment $assignment,
        DeclineAssignmentAction $decline,
    ): RedirectResponse {
        $this->authorize('work', $assignment);

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(DeclineAssignmentAction::REASONS))],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'reason.required' => 'Bitte wählen Sie aus, warum der Auftrag nicht zustande gekommen ist.',
        ], [
            'reason' => 'der Grund',
            'note' => 'die Anmerkung',
        ]);

        try {
            $decline->execute($assignment, $data['reason'], $data['note'] ?? null);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['reason' => $e->getMessage()]);
        }

        return redirect()
            ->route('portal.assignments')
            ->with('success', 'Danke — die Anfrage geht zurück in die Vermittlung.');
    }

    public function storeDocument(
        Request $request,
        Assignment $assignment,
        StoreAssignmentDocumentAction $store,
    ): RedirectResponse {
        $this->authorize('uploadDocuments', $assignment);

        $request->validate([
            'type' => ['required', Rule::in([
                AssignmentDocument::TYPE_REPORT,
                AssignmentDocument::TYPE_CUSTOMER_INVOICE,
                AssignmentDocument::TYPE_OTHER,
            ])],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [], [
            'type' => 'die Art der Unterlage',
            'document' => 'die Datei',
        ]);

        try {
            $store->execute($assignment, $request->file('document'), $request->string('type')->toString());
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['document' => $e->getMessage()]);
        }

        return back()->with('success', 'Die Unterlage wurde hinterlegt.');
    }

    public function destroyDocument(Request $request, Assignment $assignment, AssignmentDocument $document): RedirectResponse
    {
        $this->authorize('deleteDocument', $assignment);
        abort_unless($document->assignment_id === $assignment->id, 404);

        Storage::disk(AssignmentDocument::DISK)->delete($document->path);
        $document->delete();

        return back()->with('success', 'Die Unterlage wurde entfernt.');
    }

    /**
     * The only way a document is ever served. Authorisation runs first; the
     * path is never exposed to the client, so it cannot be guessed.
     */
    public function downloadDocument(Request $request, Assignment $assignment, AssignmentDocument $document): StreamedResponse
    {
        $this->authorize('downloadDocument', $assignment);
        abort_unless($document->assignment_id === $assignment->id, 404);

        $disk = Storage::disk(AssignmentDocument::DISK);
        abort_unless($disk->exists($document->path), 404);

        return $disk->download($document->path, $document->original_name);
    }

    public function complete(
        Request $request,
        Assignment $assignment,
        CompleteAssignmentAction $complete,
    ): RedirectResponse {
        $this->authorize('complete', $assignment);

        // Confirming the report is finished is the whole of it: no fee to type
        // and no documents to produce first.
        $data = $request->validate(
            ['notes' => ['nullable', 'string', 'max:1000']],
            [],
            ['notes' => 'die Anmerkung']
        );

        try {
            $complete->execute($assignment, $data['notes'] ?? null);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['notes' => $e->getMessage()]);
        }

        return redirect()
            ->route('portal.assignments.show', $assignment)
            ->with('success', 'Der Auftrag ist abgeschlossen.');
    }

    private function timelineLabel(string $status): string
    {
        return match ($status) {
            Assignment::STATUS_ACCEPTED => 'Auftrag angenommen',
            Assignment::STATUS_IN_PROGRESS => 'Bearbeitung begonnen',
            Assignment::STATUS_DOCUMENTS_UPLOADED => 'Unterlagen hinterlegt',
            Assignment::STATUS_COMPLETED => 'Auftrag abgeschlossen',
            Assignment::STATUS_CANCELLED => 'Auftrag storniert',
            default => $status,
        };
    }
}
