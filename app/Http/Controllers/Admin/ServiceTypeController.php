<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use App\Support\Formatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ServiceTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServiceType::class);

        return Inertia::render('Admin/Leistungsarten', [
            'serviceTypes' => ServiceType::ordered()->withCount(['serviceRequests', 'assessors'])->get()
                ->map(fn (ServiceType $t) => [
                    'id' => $t->id,
                    'slug' => $t->slug,
                    'name_de' => $t->name_de,
                    'description_de' => $t->description_de,
                    'icon' => $t->icon,
                    'sort_order' => $t->sort_order,
                    'is_active' => $t->is_active,
                    'dkgz_fee_cents' => $t->dkgz_fee_cents,
                    'dkgz_fee_label' => $t->dkgz_fee_cents === null ? null : Formatter::money($t->dkgz_fee_cents),
                    // An active service with no fee would book a zero-euro
                    // referral on every completion; flagged rather than silent.
                    'fee_missing' => $t->is_active && $t->dkgz_fee_cents === null,
                    'requests_count' => $t->service_requests_count,
                    'assessors_count' => $t->assessors_count,
                    'can_delete' => $request->user()->can('delete', $t),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ServiceType::class);

        $data = $this->validated($request);

        ServiceType::create($data + [
            'slug' => Str::slug($data['name_de']),
            'sort_order' => (int) ServiceType::max('sort_order') + 1,
        ]);

        return back()->with('success', 'Die Leistungsart wurde angelegt.');
    }

    public function update(Request $request, ServiceType $serviceType): RedirectResponse
    {
        $this->authorize('update', $serviceType);

        $serviceType->update($this->validated($request, $serviceType));

        return back()->with('success', 'Die Leistungsart wurde gespeichert.');
    }

    public function destroy(Request $request, ServiceType $serviceType): RedirectResponse
    {
        $this->authorize('delete', $serviceType);

        $serviceType->delete();

        return back()->with('success', 'Die Leistungsart wurde gelöscht.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('servicetypes.manage');

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($data['order'] as $position => $id) {
            ServiceType::whereKey($id)->update(['sort_order' => $position + 1]);
        }

        return back()->with('success', 'Die Reihenfolge wurde gespeichert.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?ServiceType $existing = null): array
    {
        return $request->validate([
            'name_de' => ['required', 'string', 'max:120'],
            'description_de' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:60'],
            'is_active' => ['boolean'],
            // Required once the service is active, because an active service
            // without a fee books nothing when an assignment completes.
            'dkgz_fee_cents' => ['nullable', 'required_if:is_active,true,1', 'integer', 'min:0', 'max:10000000'],
        ], [
            'dkgz_fee_cents.required_if' => 'Eine aktive Leistung braucht eine festgelegte DKGZ-Gebühr.',
        ], [
            'name_de' => 'der Name',
            'description_de' => 'die Beschreibung',
            'dkgz_fee_cents' => 'die DKGZ-Gebühr',
        ]);
    }
}
