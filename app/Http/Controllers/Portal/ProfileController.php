<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\ServiceType;
use App\Rules\ExistingPostalCode;
use App\Rules\GermanVatId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $assessor = $request->user()->assessor;

        return Inertia::render('Portal/Profil', [
            'profile' => [
                'first_name' => $request->user()->first_name,
                'last_name' => $request->user()->last_name,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
                'company_name' => $assessor->company_name,
                'legal_form' => $assessor->legal_form,
                'street' => $assessor->street,
                'house_number' => $assessor->house_number,
                'postal_code' => $assessor->postal_code,
                'city' => $assessor->city,
                'vat_id' => $assessor->vat_id,
                'website' => $assessor->website,
                'certification_body' => $assessor->certification_body,
                'certification_number' => $assessor->certification_number,
                'certification_valid_until' => $assessor->certification_valid_until,
                'years_experience' => $assessor->years_experience,
                'has_document' => $assessor->qualification_document_path !== null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $assessor = $user->assessor;

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:40'],
            'company_name' => ['required', 'string', 'max:180'],
            'street' => ['required', 'string', 'max:180'],
            'house_number' => ['required', 'string', 'max:20'],
            'postal_code' => ['required', new ExistingPostalCode],
            'city' => ['required', 'string', 'max:120'],
            'vat_id' => ['nullable', new GermanVatId],
            'website' => ['nullable', 'string', 'max:255'],
            'certification_number' => ['required', 'string', 'max:60'],
            'certification_valid_until' => ['nullable', 'date'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
        ]);

        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'phone' => $data['phone'],
        ]);

        $assessor->update(collect($data)->except(['first_name', 'last_name', 'phone'])->all());

        return back()->with('success', 'Ihr Profil wurde gespeichert.');
    }

    public function services(Request $request): Response
    {
        $assessor = $request->user()->assessor;

        return Inertia::render('Portal/Leistungen', [
            'serviceTypes' => ServiceType::active()->ordered()->get(['id', 'name_de', 'description_de']),
            'selected' => $assessor->serviceTypes()->pluck('service_types.id'),
        ]);
    }

    public function updateServices(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_type_ids' => ['required', 'array', 'min:1'],
            'service_type_ids.*' => ['integer', Rule::exists('service_types', 'id')],
        ], [
            'service_type_ids.required' => 'Bitte wählen Sie mindestens eine Leistung aus.',
            'service_type_ids.min' => 'Bitte wählen Sie mindestens eine Leistung aus.',
        ], ['service_type_ids' => 'die Leistungen']);

        $request->user()->assessor->serviceTypes()->sync($data['service_type_ids']);

        return back()->with('success', 'Ihre Leistungen wurden gespeichert.');
    }

    public function availability(Request $request): RedirectResponse
    {
        $data = $request->validate(['is_available' => ['required', 'boolean']]);

        $request->user()->assessor->update(['is_available' => $data['is_available']]);

        return back()->with('success', $data['is_available']
            ? 'Sie sind wieder für neue Anfragen verfügbar.'
            : 'Sie erhalten vorerst keine neuen Anfragen.');
    }

    public function settings(Request $request): Response
    {
        return Inertia::render('Portal/Einstellungen', [
            'email' => $request->user()->email,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordResetController::passwordRules()],
        ], [], [
            'current_password' => 'das aktuelle Passwort',
            'password' => 'das neue Passwort',
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Ihr Passwort wurde geändert.');
    }

    public function commissions(Request $request): Response
    {
        $commissions = Commission::query()
            ->where('assessor_id', $request->user()->assessor->id)
            ->with('assignment.serviceRequest')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Portal/Provisionen', [
            'commissions' => $commissions->through(fn (Commission $commission) => [
                'id' => $commission->id,
                'reference' => $commission->assignment?->serviceRequest?->reference,
                'fee_cents' => $commission->fee_cents,
                'rate_percent' => (float) $commission->rate_percent,
                'commission_cents' => $commission->commission_cents,
                'assessor_share_cents' => $commission->assessorShareCents(),
                'status' => $commission->status,
                'status_label' => $commission->statusLabel(),
                'invoice_number' => $commission->invoice_number,
                'created_at' => $commission->created_at,
            ]),
            'totals' => [
                'open_cents' => (int) Commission::where('assessor_id', $request->user()->assessor->id)
                    ->open()->sum('commission_cents'),
                'settled_cents' => (int) Commission::where('assessor_id', $request->user()->assessor->id)
                    ->where('status', Commission::STATUS_SETTLED)->sum('commission_cents'),
            ],
        ]);
    }
}
