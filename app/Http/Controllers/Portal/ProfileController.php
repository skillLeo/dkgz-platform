<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Rules\ExistingPostalCode;
use App\Rules\GermanVatId;
use App\Rules\Iban;
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
        $windowDays = 90;

        // How much work each type has actually drawn inside this partner's own
        // area, so switching a type on or off is an informed decision rather
        // than a guess. Counted across all requests in range, not only the ones
        // this partner was matched to — otherwise a type they do not currently
        // offer would always read zero.
        $demand = ServiceRequest::query()
            ->where('created_at', '>=', now()->subDays($windowDays))
            ->whereExists(fn ($sub) => $sub->selectRaw('1')
                ->from('assessor_service_areas')
                ->where('assessor_service_areas.assessor_id', $assessor->id)
                ->whereColumn('service_requests.postal_code', '>=', 'assessor_service_areas.postal_code_from')
                ->whereColumn('service_requests.postal_code', '<=', 'assessor_service_areas.postal_code_to'))
            ->selectRaw('service_type_id, COUNT(*) AS total')
            ->groupBy('service_type_id')
            ->pluck('total', 'service_type_id');

        return Inertia::render('Portal/Leistungen', [
            'serviceTypes' => ServiceType::active()->ordered()->get(['id', 'name_de', 'description_de'])
                ->map(fn (ServiceType $type) => [
                    'id' => $type->id,
                    'name_de' => $type->name_de,
                    'description_de' => $type->description_de,
                    'demand' => (int) ($demand[$type->id] ?? 0),
                ]),
            'selected' => $assessor->serviceTypes()->pluck('service_types.id'),
            'demandWindowDays' => $windowDays,
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
        $user = $request->user();
        $assessor = $user->assessor;

        return Inertia::render('Portal/Einstellungen', [
            'email' => $user->email,
            'company' => [
                'company_name' => $assessor->company_name,
                'vat_id' => $assessor->vat_id,
                'phone' => $user->phone,
                'email' => $user->email,
            ],
            'bank' => [
                'bank_account_holder' => $assessor->bank_account_holder,
                'bank_iban' => $assessor->bank_iban,
                'bank_bic' => $assessor->bank_bic,
            ],
            'notifications' => [
                'notify_new_request' => $assessor->notify_new_request,
                'notify_deadline_reminder' => $assessor->notify_deadline_reminder,
                'notify_commission_statement' => $assessor->notify_commission_statement,
            ],
        ]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:180'],
            'vat_id' => ['nullable', new GermanVatId],
            'phone' => ['required', 'string', 'max:40'],
        ], [], [
            'company_name' => 'der Firmenname',
            'vat_id' => 'die USt-IdNr.',
            'phone' => 'die Telefonnummer',
        ]);

        $request->user()->update(['phone' => $data['phone']]);
        $request->user()->assessor->update([
            'company_name' => $data['company_name'],
            'vat_id' => $data['vat_id'],
        ]);

        return back()->with('success', 'Ihre Firmendaten wurden gespeichert.');
    }

    /**
     * Recorded so the monthly commission invoice can be settled by transfer.
     * DKGZ never debits this account — no payment runs through the platform.
     */
    public function updateBank(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_account_holder' => ['required', 'string', 'max:180'],
            'bank_iban' => ['required', new Iban],
            // Case-insensitive: the value is upper-cased on the way in, so
            // rejecting a lower-case BIC here would fail input we accept.
            'bank_bic' => ['nullable', 'string', 'regex:/^[A-Za-z]{6}[A-Za-z0-9]{2}([A-Za-z0-9]{3})?$/'],
        ], [
            'bank_bic.regex' => 'Die BIC muss aus 8 oder 11 Zeichen bestehen.',
        ], [
            'bank_account_holder' => 'der Kontoinhaber',
            'bank_iban' => 'die IBAN',
            'bank_bic' => 'die BIC',
        ]);

        $request->user()->assessor->update([
            'bank_account_holder' => $data['bank_account_holder'],
            'bank_iban' => str($data['bank_iban'])->replace(' ', '')->upper()->value(),
            'bank_bic' => $data['bank_bic'] === null ? null : strtoupper($data['bank_bic']),
        ]);

        return back()->with('success', 'Ihre Bankverbindung wurde gespeichert.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notify_new_request' => ['required', 'boolean'],
            'notify_deadline_reminder' => ['required', 'boolean'],
            'notify_commission_statement' => ['required', 'boolean'],
        ]);

        $request->user()->assessor->update($data);

        return back()->with('success', 'Ihre Benachrichtigungen wurden gespeichert.');
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
