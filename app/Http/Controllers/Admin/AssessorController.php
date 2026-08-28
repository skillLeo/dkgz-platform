<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyAssessorApprovalJob;
use App\Models\Assessor;
use App\Models\AssessorDocument;
use App\Support\Formatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssessorController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Assessor::class);

        $assessors = Assessor::query()
            ->with('user')
            ->withCount(['assignments', 'serviceAreas'])
            ->when($request->filled('suche'), fn ($q) => $q->where(function ($inner) use ($request) {
                $term = '%'.$request->string('suche')->toString().'%';
                $inner->where('company_name', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('postal_code', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $term));
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('approval_status', $request->string('status')))
            ->when($request->filled('verfuegbar'), fn ($q) => $q->where('is_available', $request->boolean('verfuegbar')))
            ->orderBy(
                $request->string('sort')->toString() ?: 'created_at',
                $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc'
            )
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Sachverstaendige', [
            'assessors' => $assessors->through(fn (Assessor $a) => [
                'id' => $a->id,
                'company_name' => $a->company_name,
                'contact' => $a->user?->fullName(),
                'email' => $a->user?->email,
                'location' => trim($a->postal_code.' '.$a->city),
                'approval_status' => $a->approval_status,
                'is_available' => $a->is_available,
                'assignments_count' => $a->assignments_count,
                'service_areas_count' => $a->service_areas_count,
                'created_at' => $a->created_at,
            ]),
            'filters' => $request->only(['suche', 'status', 'verfuegbar', 'sort', 'direction']),
            'statusOptions' => [
                Assessor::STATUS_PENDING => 'Wartet auf Prüfung',
                Assessor::STATUS_APPROVED => 'Freigegeben',
                Assessor::STATUS_REJECTED => 'Abgelehnt',
                Assessor::STATUS_SUSPENDED => 'Gesperrt',
            ],
            'pendingCount' => Assessor::pending()->count(),
        ]);
    }

    public function show(Request $request, Assessor $assessor): Response
    {
        $this->authorize('view', $assessor);

        $assessor->load(['documents', 'user', 'serviceAreas', 'serviceTypes', 'approvedBy']);

        return Inertia::render('Admin/Sachverstaendiger', [
            'assessor' => [
                'id' => $assessor->id,
                'company_name' => $assessor->company_name,
                'legal_form' => $assessor->legal_form,
                'address' => $assessor->displayAddress(),
                'vat_id' => $assessor->vat_id,
                'website' => $assessor->website,
                'certification_body' => $assessor->certification_body,
                'certification_number' => $assessor->certification_number,
                'certification_valid_until' => $assessor->certification_valid_until,
                'years_experience' => $assessor->years_experience,
                'partner_id' => $assessor->partnerId(),
                'acceptance_rate' => $assessor->acceptanceRate(),
                'cover_has_lapsed' => $assessor->liabilityCoverHasLapsed(),
                'documents' => $assessor->documents->map(fn (AssessorDocument $document) => [
                    'id' => $document->id,
                    'type_label' => $document->typeLabel(),
                    'original_name' => $document->original_name,
                    'size_label' => Formatter::fileSize($document->size_bytes),
                    'uploaded_at' => $document->uploaded_at,
                    'valid_until' => $document->valid_until,
                    'valid_until_label' => $document->valid_until === null
                        ? null
                        : 'gültig bis '.Formatter::date($document->valid_until),
                    'state' => $document->hasLapsed()
                        ? 'lapsed'
                        : ($document->expiresSoon() ? 'expiring' : 'ok'),
                    'state_label' => $document->hasLapsed()
                        ? 'Nachweis abgelaufen'
                        : ($document->expiresSoon() ? 'Läuft ab' : 'Geprüft'),
                    'download_url' => route('admin.assessors.documents.download', [$assessor, $document]),
                ])->all(),
                'approval_status' => $assessor->approval_status,
                'is_available' => $assessor->is_available,
                'approved_at' => $assessor->approved_at,
                'approved_by' => $assessor->approvedBy?->fullName(),
                'rejection_reason' => $assessor->rejection_reason,
                'suspension_reason' => $assessor->suspension_reason,
                'internal_notes' => $assessor->internal_notes,
                'created_at' => $assessor->created_at,
                'contact' => [
                    'name' => $assessor->user?->fullName(),
                    'email' => $assessor->user?->email,
                    'phone' => Formatter::phone($assessor->user?->phone),
                    'is_active' => $assessor->user?->is_active,
                    'last_login_at' => $assessor->user?->last_login_at,
                ],
                'service_areas' => $assessor->serviceAreas->map(fn ($a) => [
                    'id' => $a->id,
                    'range' => $a->range(),
                ]),
                'is_listed' => $assessor->is_listed,
                'public_profile' => $assessor->public_profile,
                'directory_url' => $assessor->slug ? "/sachverstaendige/{$assessor->slug}" : null,
                'service_types' => $assessor->serviceTypes->where('is_active', true)->pluck('name_de')->values(),
                // Signed up for, but the platform no longer offers it. Shown
                // apart rather than mixed in, so nobody reads a retired service
                // as one this partner is being sent work for.
                'retired_service_types' => $assessor->serviceTypes->where('is_active', false)->pluck('name_de')->values(),
            ],
            'can' => [
                'approve' => $request->user()->can('approve', $assessor),
                'reject' => $request->user()->can('reject', $assessor),
                'suspend' => $request->user()->can('suspend', $assessor),
                'unsuspend' => $request->user()->can('unsuspend', $assessor),
                'edit' => $request->user()->can('update', $assessor),
            ],
            'stats' => [
                'assignments' => $assessor->assignments()->count(),
                'completed' => $assessor->assignments()->where('status', 'completed')->count(),
                'commission_cents' => (int) $assessor->commissions()->sum('commission_cents'),
            ],
        ]);
    }

    public function approve(Request $request, Assessor $assessor): RedirectResponse
    {
        $this->authorize('approve', $assessor);

        $assessor->update([
            'approval_status' => Assessor::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        NotifyAssessorApprovalJob::dispatch($assessor->id, 'freigegeben');

        return back()->with('success', 'Der Sachverständige wurde freigegeben.');
    }

    public function reject(Request $request, Assessor $assessor): RedirectResponse
    {
        $this->authorize('reject', $assessor);

        $data = $request->validate(
            ['reason' => ['required', 'string', 'max:500']],
            ['reason.required' => 'Bitte geben Sie eine Begründung an.'],
            ['reason' => 'die Begründung']
        );

        $assessor->update([
            'approval_status' => Assessor::STATUS_REJECTED,
            'rejection_reason' => $data['reason'],
        ]);

        NotifyAssessorApprovalJob::dispatch($assessor->id, 'abgelehnt');

        return back()->with('success', 'Die Registrierung wurde abgelehnt.');
    }

    public function suspend(Request $request, Assessor $assessor): RedirectResponse
    {
        $this->authorize('suspend', $assessor);

        $data = $request->validate(
            ['reason' => ['required', 'string', 'max:500']],
            ['reason.required' => 'Bitte geben Sie eine Begründung an.'],
            ['reason' => 'die Begründung']
        );

        $assessor->update([
            'approval_status' => Assessor::STATUS_SUSPENDED,
            'suspension_reason' => $data['reason'],
            'suspended_at' => now(),
            // A suspended partner must stop receiving matches immediately.
            'is_available' => false,
        ]);

        NotifyAssessorApprovalJob::dispatch($assessor->id, 'gesperrt');

        return back()->with('success', 'Der Zugang wurde gesperrt.');
    }

    public function unsuspend(Request $request, Assessor $assessor): RedirectResponse
    {
        $this->authorize('unsuspend', $assessor);

        $assessor->update([
            'approval_status' => Assessor::STATUS_APPROVED,
            'suspension_reason' => null,
            'suspended_at' => null,
        ]);

        return back()->with('success', 'Die Sperre wurde aufgehoben.');
    }

    public function update(Request $request, Assessor $assessor): RedirectResponse
    {
        $this->authorize('update', $assessor);

        $data = $request->validate([
            'internal_notes' => ['nullable', 'string', 'max:4000'],
            // Whether this partner has a page in the public directory, and what
            // it says about them in their own words. The notes above are the
            // office's and never appear anywhere public.
            'is_listed' => ['boolean'],
            'public_profile' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'internal_notes' => 'die interne Notiz',
            'public_profile' => 'das öffentliche Profil',
        ]);

        // Read explicitly rather than from the validated array: a toggle
        // posting false arrives as an empty string, which the boolean rule
        // passes over — so switching a partner out of the directory silently
        // did nothing at all.
        $data['is_listed'] = $request->boolean('is_listed');

        $assessor->update($data);

        return back()->with('success', 'Gespeichert.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Assessor::class);

        return response()->streamDownload(function () {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));
            $csv->setDelimiter(';');
            $csv->insertOne([
                'Firma', 'Ansprechpartner', 'E-Mail', 'PLZ', 'Ort',
                'Status', 'Verfügbar', 'Einsatzgebiete', 'Aufträge', 'Registriert am',
            ]);

            Assessor::with('user')->withCount(['serviceAreas', 'assignments'])
                ->chunk(200, function ($rows) use ($csv) {
                    foreach ($rows as $a) {
                        $csv->insertOne([
                            $a->company_name,
                            $a->user?->fullName(),
                            $a->user?->email,
                            $a->postal_code,
                            $a->city,
                            $a->approval_status,
                            $a->is_available ? 'ja' : 'nein',
                            $a->service_areas_count,
                            $a->assignments_count,
                            Formatter::date($a->created_at),
                        ]);
                    }
                });
        }, 'sachverstaendige-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Streams a submitted proof. Approval is a judgement about evidence, so the
     * evidence has to be openable — knowing only that a file exists is not a
     * basis for admitting someone to the partner network.
     */
    public function downloadDocument(Assessor $assessor, AssessorDocument $document): StreamedResponse
    {
        $this->authorize('view', $assessor);
        abort_unless($document->assessor_id === $assessor->id, 404);

        $disk = Storage::disk('private');
        abort_unless($disk->exists($document->path), 404);

        return $disk->download($document->path, $document->original_name);
    }
}
