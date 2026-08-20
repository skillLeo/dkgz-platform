<?php

namespace App\Http\Controllers\Portal;

use App\Actions\CompleteAssignmentAction;
use App\Actions\StoreAssignmentDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Assignment;
use App\Models\AssignmentDocument;
use App\Support\Formatter;
use App\Support\Money;
use App\Support\Settings;
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
                'fee_cents' => $assignment->fee_cents,
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
                'fee_cents' => $assignment->fee_cents,
                'assessor_notes' => $assignment->assessor_notes,
                'can_complete' => $assignment->hasRequiredDocuments() && $assignment->isOpen(),
                'is_open' => $assignment->isOpen(),
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
                'fee_cents' => $assignment->commission->fee_cents,
                'rate_percent' => (float) $assignment->commission->rate_percent,
                'commission_cents' => $assignment->commission->commission_cents,
                'assessor_share_cents' => $assignment->commission->assessorShareCents(),
                'status' => $assignment->commission->status,
                'status_label' => $assignment->commission->statusLabel(),
            ],
            // The dialog shows the split live as the fee is typed, so it needs
            // the current rate; the frozen rate on a settled commission is
            // separate and comes back on the commission payload above.
            'commissionRate' => Settings::commissionRate(),
            'feeBounds' => [
                'min' => Money::MIN_FEE_CENTS,
                'max' => Money::MAX_FEE_CENTS,
                'review_threshold' => Money::REVIEW_THRESHOLD_CENTS,
            ],
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

        $data = $request->validate([
            'fee_cents' => ['required', 'integer', 'min:'.Money::MIN_FEE_CENTS, 'max:'.Money::MAX_FEE_CENTS],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'fee_cents.min' => 'Das Honorar erscheint zu niedrig. Bitte prüfen Sie die Eingabe.',
            'fee_cents.max' => 'Das Honorar erscheint zu hoch. Bitte prüfen Sie die Eingabe.',
        ], [
            'fee_cents' => 'das Honorar',
            'notes' => 'die Anmerkung',
        ]);

        try {
            $complete->execute($assignment, (int) $data['fee_cents'], $data['notes'] ?? null);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['fee_cents' => $e->getMessage()]);
        }

        return redirect()
            ->route('portal.assignments.show', $assignment)
            ->with('success', 'Der Auftrag ist abgeschlossen. Die Provision wurde berechnet.');
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
