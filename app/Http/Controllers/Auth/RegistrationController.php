<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Assessor;
use App\Models\ServiceType;
use App\Models\User;
use App\Rules\ExistingPostalCode;
use App\Rules\GermanVatId;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Four-step assessor registration. Each step validates and autosaves into the
 * session, so a partner can leave and come back without losing their work; the
 * user and profile rows are only written when step four is submitted.
 */
class RegistrationController extends Controller
{
    private const SESSION_KEY = 'registrierung';

    public function show(Request $request): Response|RedirectResponse
    {
        if (! Settings::bool('features.self_registration', true)) {
            return redirect()->route('partner')
                ->with('info', 'Die Registrierung ist derzeit geschlossen. Bitte wenden Sie sich an die Administration.');
        }

        $draft = $request->session()->get(self::SESSION_KEY, []);

        return Inertia::render('Auth/Registrieren', [
            'draft' => $draft,
            'step' => (int) ($draft['_step'] ?? 1),
            'serviceTypes' => ServiceType::active()->ordered()->get(['id', 'name_de', 'description_de']),
            'legalForms' => $this->legalForms(),
            'certificationBodies' => $this->certificationBodies(),
        ]);
    }

    /** Autosave for one step. Validates that step only. */
    public function saveStep(Request $request, int $step): RedirectResponse
    {
        abort_unless($step >= 1 && $step <= 4, 404);

        $validated = $request->validate($this->rulesForStep($step), [], $this->attributeNames());

        $draft = array_merge(
            $request->session()->get(self::SESSION_KEY, []),
            $validated,
            ['_step' => min($step + 1, 4)]
        );

        $request->session()->put(self::SESSION_KEY, $draft);

        return back();
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Settings::bool('features.self_registration', true)) {
            abort(403, 'Die Registrierung ist derzeit geschlossen.');
        }

        $draft = $request->session()->get(self::SESSION_KEY, []);

        $data = $request->validate(
            array_merge(
                $this->rulesForStep(1),
                $this->rulesForStep(2),
                $this->rulesForStep(3),
                $this->rulesForStep(4),
            ),
            [],
            $this->attributeNames()
        );

        $data = array_merge($draft, $data);

        $user = DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'],
                'locale' => 'de',
                'is_active' => true,
            ]);

            $user->assignRole('assessor');

            $assessor = Assessor::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'legal_form' => $data['legal_form'],
                'street' => $data['street'],
                'house_number' => $data['house_number'],
                'postal_code' => $data['postal_code'],
                'city' => $data['city'],
                'country' => 'DE',
                'vat_id' => $data['vat_id'] ?? null,
                'website' => $data['website'] ?? null,
                'certification_body' => $data['certification_body'],
                'certification_number' => $data['certification_number'],
                'certification_valid_until' => $data['certification_valid_until'] ?? null,
                'years_experience' => $data['years_experience'] ?? null,
                'approval_status' => Assessor::STATUS_PENDING,
                'is_available' => true,
            ]);

            if ($request->hasFile('qualification_document')) {
                $assessor->update([
                    'qualification_document_path' => $request->file('qualification_document')
                        ->store('nachweise', 'private'),
                ]);
            }

            $assessor->serviceTypes()->sync($data['service_type_ids']);

            foreach ($data['service_areas'] as $area) {
                $assessor->serviceAreas()->create([
                    'postal_code_from' => $area['from'],
                    'postal_code_to' => $area['to'],
                    'label' => $area['label'] ?? null,
                ]);
            }

            return $user;
        });

        $request->session()->forget(self::SESSION_KEY);

        auth()->login($user);
        $user->sendEmailVerificationNotification();

        return redirect()->route('registration.pending');
    }

    /** @return array<string, mixed> */
    private function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
                'phone' => ['required', 'string', 'max:40'],
                'password' => ['required', 'confirmed', PasswordResetController::passwordRules()],
            ],
            2 => [
                'company_name' => ['required', 'string', 'max:180'],
                'legal_form' => ['required', Rule::in(array_keys($this->legalForms()))],
                'street' => ['required', 'string', 'max:180'],
                'house_number' => ['required', 'string', 'max:20'],
                'postal_code' => ['required', new ExistingPostalCode],
                'city' => ['required', 'string', 'max:120'],
                'vat_id' => ['nullable', new GermanVatId],
                'website' => ['nullable', 'string', 'max:255'],
            ],
            3 => [
                'certification_body' => ['required', Rule::in(array_keys($this->certificationBodies()))],
                'certification_number' => ['required', 'string', 'max:60'],
                'certification_valid_until' => ['nullable', 'date', 'after:today'],
                'years_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
                'qualification_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            ],
            4 => [
                'service_type_ids' => ['required', 'array', 'min:1'],
                'service_type_ids.*' => ['integer', Rule::exists('service_types', 'id')],
                'service_areas' => ['required', 'array', 'min:1'],
                'service_areas.*.from' => ['required', 'digits:5'],
                'service_areas.*.to' => ['required', 'digits:5', 'gte:service_areas.*.from'],
                'service_areas.*.label' => ['nullable', 'string', 'max:120'],
                'terms' => ['accepted'],
                'privacy' => ['accepted'],
            ],
            default => throw ValidationException::withMessages(['step' => 'Unbekannter Schritt.']),
        };
    }

    /** @return array<string, string> */
    private function legalForms(): array
    {
        return [
            'einzelunternehmen' => 'Einzelunternehmen',
            'gbr' => 'GbR',
            'gmbh' => 'GmbH',
            'ug' => 'UG (haftungsbeschränkt)',
            'ohg' => 'OHG',
            'kg' => 'KG',
            'gmbh_co_kg' => 'GmbH & Co. KG',
            'ag' => 'AG',
            'sonstige' => 'Sonstige',
        ];
    }

    /** @return array<string, string> */
    private function certificationBodies(): array
    {
        return [
            'tuev' => 'TÜV',
            'dekra' => 'DEKRA',
            'gtue' => 'GTÜ',
            'kues' => 'KÜS',
            'ihk' => 'IHK',
            'bvsk' => 'BVSK',
            'dat' => 'DAT',
            'sonstige' => 'Sonstige',
        ];
    }

    /** @return array<string, string> */
    private function attributeNames(): array
    {
        return [
            'first_name' => 'der Vorname',
            'last_name' => 'der Nachname',
            'email' => 'die E-Mail-Adresse',
            'phone' => 'die Telefonnummer',
            'password' => 'das Passwort',
            'company_name' => 'der Firmenname',
            'legal_form' => 'die Rechtsform',
            'street' => 'die Straße',
            'house_number' => 'die Hausnummer',
            'postal_code' => 'die Postleitzahl',
            'city' => 'der Ort',
            'vat_id' => 'die USt-IdNr.',
            'certification_body' => 'die Zertifizierungsstelle',
            'certification_number' => 'die Zertifizierungsnummer',
            'certification_valid_until' => 'die Gültigkeit der Zertifizierung',
            'years_experience' => 'die Berufserfahrung',
            'qualification_document' => 'der Qualifikationsnachweis',
            'service_type_ids' => 'die Leistungen',
            'service_areas' => 'das Einsatzgebiet',
        ];
    }
}
