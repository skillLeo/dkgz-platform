<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyAssessorApprovalJob;
use App\Models\Assessor;
use App\Support\Formatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $assessor->load(['user', 'serviceAreas', 'serviceTypes', 'approvedBy']);

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
                'has_document' => $assessor->qualification_document_path !== null,
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
                'service_types' => $assessor->serviceTypes->pluck('name_de'),
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

        $data = $request->validate(
            ['internal_notes' => ['nullable', 'string', 'max:4000']],
            [],
            ['internal_notes' => 'die interne Notiz']
        );

        $assessor->update($data);

        return back()->with('success', 'Die Notiz wurde gespeichert.');
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
}
