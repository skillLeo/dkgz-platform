<?php

namespace App\Http\Controllers\Portal;

use App\Actions\CompleteAssignmentAction;
use App\Actions\ConfirmAssignmentAction;
use App\Actions\StoreAssignmentDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Assignment;
use App\Models\AssignmentDocument;
use App\Models\Commission;
use App\Support\Formatter;
use App\Support\Money;
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
                'confirmed_at' => $assignment->confirmed_at,
                'can_confirm' => $assignment->status === Assignment::STATUS_ACCEPTED,
                'customer_invoice_cents' => $assignment->customer_invoice_cents,
                'customer_invoice_recipient' => $assignment->customer_invoice_recipient,
                'customer_invoice_number' => $assignment->customer_invoice_number,
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
                'fee_type' => $assignment->commission->fee_type,
                'assessor_share_cents' => $assignment->commission->assessorShareCents(),
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

    /** The assessor's own invoice to the customer or their insurer. */
    public function updateCustomerInvoice(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize('work', $assignment);

        $data = $request->validate([
            'customer_invoice_cents' => ['nullable', 'integer', 'min:0', 'max:'.Money::MAX_FEE_CENTS],
            'customer_invoice_recipient' => ['nullable', Rule::in(['kunde', 'versicherung'])],
            'customer_invoice_number' => ['nullable', 'string', 'max:60'],
        ], [], [
            'customer_invoice_cents' => 'der Rechnungsbetrag',
            'customer_invoice_recipient' => 'der Rechnungsempfänger',
            'customer_invoice_number' => 'die Rechnungsnummer',
        ]);

        $assignment->update($data);

        return back()->with('success', 'Die Rechnungsangaben wurden gespeichert.');
    }

    /** The DKGZ invoice PDF for one commission, from inside the job. */
    public function downloadCommissionInvoice(Request $request, Commission $commission): StreamedResponse
    {
        $this->authorize('downloadInvoice', $commission);

        $disk = Storage::disk('private');

        abort_unless($disk->exists($commission->invoice_path), 404);

        return $disk->download(
            $commission->invoice_path,
            'DKGZ-Rechnung-'.$commission->invoice_number.'.pdf',
        );
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
            // Optional: it is the assessor's own record of what they charged
            // their customer and drives no calculation on this platform.
            'fee_cents' => ['nullable', 'integer', 'min:'.Money::MIN_FEE_CENTS, 'max:'.Money::MAX_FEE_CENTS],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'fee_cents.min' => 'Das Honorar erscheint zu niedrig. Bitte prüfen Sie die Eingabe.',
            'fee_cents.max' => 'Das Honorar erscheint zu hoch. Bitte prüfen Sie die Eingabe.',
        ], [
            'fee_cents' => 'das Honorar',
            'notes' => 'die Anmerkung',
        ]);

        try {
            $complete->execute(
                $assignment,
                isset($data['fee_cents']) ? (int) $data['fee_cents'] : null,
                $data['notes'] ?? null,
            );
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
