<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendCommissionInvoiceJob;
use App\Models\Assessor;
use App\Models\Commission;
use App\Support\Formatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommissionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Commission::class);

        $commissions = Commission::query()
            ->with(['assessor', 'assignment.serviceRequest'])
            ->when($request->filled('suche'), fn ($q) => $q->where(function ($inner) use ($request) {
                $term = '%'.$request->string('suche')->toString().'%';
                $inner->where('invoice_number', 'like', $term)
                    ->orWhereHas('assessor', fn ($a) => $a->where('company_name', 'like', $term))
                    ->orWhereHas('assignment.serviceRequest', fn ($s) => $s->where('reference', 'like', $term));
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('monat'), fn ($q) => $q
                ->whereYear('created_at', substr($request->string('monat'), 0, 4))
                ->whereMonth('created_at', substr($request->string('monat'), 5, 2)))
            ->when($request->filled('sachverstaendiger'),
                fn ($q) => $q->where('assessor_id', $request->integer('sachverstaendiger')))
            ->orderBy(
                $request->string('sort')->toString() ?: 'created_at',
                $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc'
            )
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Provisionen', [
            'commissions' => $commissions->through(fn (Commission $c) => [
                'id' => $c->id,
                'reference' => $c->assignment?->serviceRequest?->reference,
                'assessor' => $c->assessor?->company_name,
                'fee_cents' => $c->fee_cents,
                'rate_percent' => (float) $c->rate_percent,
                'commission_cents' => $c->commission_cents,
                'status' => $c->status,
                'invoice_number' => $c->invoice_number,
                'needs_review' => $c->needsReview(),
                'created_at' => $c->created_at,
            ]),
            'filters' => $request->only(['suche', 'status', 'sort', 'direction', 'monat', 'sachverstaendiger']),
            'monthOptions' => $this->monthOptions(),
            'assessorOptions' => Assessor::orderBy('company_name')->get(['id', 'company_name'])
                ->map(fn (Assessor $a) => ['id' => $a->id, 'label' => $a->company_name]),
            'summary' => $this->summaryFor($request),
            'statusOptions' => [
                Commission::STATUS_OPEN => 'Offen',
                Commission::STATUS_INVOICED => 'Abgerechnet',
                Commission::STATUS_SETTLED => 'Bezahlt',
                Commission::STATUS_WAIVED => 'Erlassen',
            ],
            'totals' => [
                'open_cents' => (int) Commission::open()->sum('commission_cents'),
                'invoiced_cents' => (int) Commission::where('status', Commission::STATUS_INVOICED)->sum('commission_cents'),
                'settled_cents' => (int) Commission::where('status', Commission::STATUS_SETTLED)->sum('commission_cents'),
            ],
        ]);
    }

    public function show(Request $request, Commission $commission): Response
    {
        $this->authorize('view', $commission);

        $commission->load(['assessor.user', 'assignment.serviceRequest.serviceType', 'settledBy']);

        return Inertia::render('Admin/Provision', [
            'commission' => [
                'id' => $commission->id,
                'fee_cents' => $commission->fee_cents,
                'rate_percent' => (float) $commission->rate_percent,
                'commission_cents' => $commission->commission_cents,
                'assessor_share_cents' => $commission->assessorShareCents(),
                'status' => $commission->status,
                'status_label' => $commission->statusLabel(),
                'invoice_number' => $commission->invoice_number,
                'invoiced_at' => $commission->invoiced_at,
                'settled_at' => $commission->settled_at,
                'settled_by' => $commission->settledBy?->fullName(),
                'notes' => $commission->notes,
                'needs_review' => $commission->needsReview(),
                'created_at' => $commission->created_at,
                'has_invoice' => $commission->invoice_path !== null,
            ],
            'assessor' => [
                'id' => $commission->assessor?->id,
                'company_name' => $commission->assessor?->company_name,
                'address' => $commission->assessor?->displayAddress(),
                'vat_id' => $commission->assessor?->vat_id,
                'email' => $commission->assessor?->user?->email,
            ],
            'assignment' => [
                'id' => $commission->assignment?->id,
                'reference' => $commission->assignment?->serviceRequest?->reference,
                'service_type' => $commission->assignment?->serviceRequest?->serviceType?->name_de,
                'completed_at' => $commission->assignment?->completed_at,
            ],
            'can' => [
                'invoice' => $request->user()->can('invoice', $commission),
                'settle' => $request->user()->can('settle', $commission),
                'waive' => $request->user()->can('waive', $commission),
            ],
        ]);
    }

    /** Generates the commission invoice PDF and stores it privately. */
    public function invoice(Request $request, Commission $commission): RedirectResponse
    {
        $this->authorize('invoice', $commission);

        $commission->load(['assessor.user', 'assignment.serviceRequest.serviceType']);

        $number = Commission::nextInvoiceNumber();

        $pdf = Pdf::loadView('pdf.commission-invoice', [
            'commission' => $commission,
            'invoiceNumber' => $number,
            'issuedAt' => now(),
        ])->setPaper('a4');

        $path = "provisionen/{$commission->id}/{$number}.pdf";
        Storage::disk('private')->put($path, $pdf->output());

        $commission->update([
            'status' => Commission::STATUS_INVOICED,
            'invoice_number' => $number,
            'invoice_path' => $path,
            'invoiced_at' => now(),
        ]);

        SendCommissionInvoiceJob::dispatch($commission->id);

        return back()->with('success', "Die Abrechnung {$number} wurde erzeugt und versendet.");
    }

    public function downloadInvoice(Request $request, Commission $commission): StreamedResponse
    {
        $this->authorize('downloadInvoice', $commission);

        $disk = Storage::disk('private');
        abort_unless($commission->invoice_path && $disk->exists($commission->invoice_path), 404);

        return $disk->download($commission->invoice_path, "{$commission->invoice_number}.pdf");
    }

    public function settle(Request $request, Commission $commission): RedirectResponse
    {
        $this->authorize('settle', $commission);

        $commission->update([
            'status' => Commission::STATUS_SETTLED,
            'settled_at' => now(),
            'settled_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Die Provision wurde als beglichen markiert.');
    }

    public function waive(Request $request, Commission $commission): RedirectResponse
    {
        $this->authorize('waive', $commission);

        $data = $request->validate(
            ['reason' => ['required', 'string', 'max:500']],
            ['reason.required' => 'Bitte geben Sie eine Begründung für den Erlass an.'],
            ['reason' => 'die Begründung']
        );

        $commission->update([
            'status' => Commission::STATUS_WAIVED,
            'notes' => $data['reason'],
        ]);

        return back()->with('success', 'Die Provision wurde erlassen.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Commission::class);

        return response()->streamDownload(function () {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));
            $csv->setDelimiter(';');
            $csv->insertOne([
                'Rechnungsnummer', 'Referenz', 'Sachverständiger', 'Honorar netto',
                'Satz', 'Provision', 'Anteil SV', 'Status', 'Erstellt am', 'Beglichen am',
            ]);

            Commission::with(['assessor', 'assignment.serviceRequest'])
                ->chunk(200, function ($rows) use ($csv) {
                    foreach ($rows as $c) {
                        $csv->insertOne([
                            $c->invoice_number,
                            $c->assignment?->serviceRequest?->reference,
                            $c->assessor?->company_name,
                            Formatter::amount($c->fee_cents),
                            Formatter::percent((float) $c->rate_percent),
                            Formatter::amount($c->commission_cents),
                            Formatter::amount($c->assessorShareCents()),
                            $c->statusLabel(),
                            Formatter::date($c->created_at),
                            $c->settled_at ? Formatter::date($c->settled_at) : '',
                        ]);
                    }
                });
        }, 'provisionen-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * The figures under the register, scoped to the same month the list is
     * showing. Summed in the database rather than from the paginated rows, so
     * page two does not report a different total than page one.
     *
     * @return array<string, mixed>
     */
    private function summaryFor(Request $request): array
    {
        $month = $request->filled('monat')
            ? Carbon::createFromFormat('Y-m', $request->string('monat')->toString())->startOfMonth()
            : now()->startOfMonth();

        $scope = fn () => Commission::query()
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->when($request->filled('sachverstaendiger'),
                fn ($q) => $q->where('assessor_id', $request->integer('sachverstaendiger')));

        return [
            'label' => 'Summe '.Formatter::monthName($month).' '.$month->year,
            'assignments' => $scope()->count(),
            'fee_cents' => (int) $scope()->sum('fee_cents'),
            'commission_cents' => (int) $scope()->sum('commission_cents'),
            'open_cents' => (int) $scope()->where('status', Commission::STATUS_OPEN)->sum('commission_cents'),
        ];
    }

    /**
     * Only months that actually hold commissions, newest first — offering empty
     * months would invite a filter that can only ever return nothing.
     *
     * @return array<int, array<string, string>>
     */
    private function monthOptions(): array
    {
        return Commission::query()
            ->orderByDesc('created_at')
            ->pluck('created_at')
            ->map(fn ($date) => $date->format('Y-m'))
            ->unique()
            ->values()
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => Formatter::monthName($value.'-01').' '.substr($value, 0, 4),
            ])
            ->all();
    }
}
