<?php

namespace App\Http\Requests;

use App\Models\PostalCode;
use App\Rules\ExistingPostalCode;
use App\Rules\GermanLicencePlate;
use App\Rules\VehicleIdentificationNumber;
use App\Support\Settings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The public form is open by design — a customer needs no account.
        // Abuse is handled by the throttle, the honeypot and the timing check.
        return true;
    }

    /**
     * The same fields, minus the checks that only make sense for a stranger.
     *
     * An operator typing into the admin panel is authenticated, so the honeypot
     * and the three-second floor would catch nobody and punish anyone who types
     * quickly. Consent is confirmed on the telephone rather than by ticking a
     * box, so it is recorded rather than validated.
     *
     * @return array<string, mixed>
     */
    public function rulesForOperator(): array
    {
        $rules = $this->rules();

        unset($rules['consent'], $rules['website'], $rules['rendered_at']);

        return $rules;
    }

    /**
     * The town comes from the postal code, not from the visitor.
     *
     * Asking for both invited them to disagree, and the pair only has one
     * useful reading anyway: the code is what the matching runs on. The form no
     * longer has the field, so it is filled in here — for the telephone path in
     * the admin panel too, where an operator types a code and should not have
     * to look the town up.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('city')) {
            return;
        }

        $city = PostalCode::cityFor(trim((string) $this->input('postal_code')));

        if ($city !== null) {
            $this->merge(['city' => $city]);
        }
    }

    public function rules(): array
    {
        $maxImages = Settings::int('business.max_images_per_request', 5);
        $maxKb = Settings::int('business.max_upload_mb', 10) * 1024;

        return [
            'service_type_id' => ['required', 'integer', Rule::exists('service_types', 'id')->where('is_active', true)],
            'postal_code' => ['required', new ExistingPostalCode],
            'city' => ['required', 'string', 'max:120'],
            'customer_name' => ['required', 'string', 'max:160'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_email' => ['required', 'string', 'email', 'max:255'],
            // Optional since the form stopped asking: the assessor telephones
            // the customer and asks then. The office still fills them in when a
            // request is taken by telephone.
            'vehicle_make' => ['nullable', 'string', 'max:80'],
            'vehicle_model' => ['nullable', 'string', 'max:80'],
            'vehicle_year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'vehicle_plate' => ['nullable', new GermanLicencePlate],
            'vehicle_vin' => ['nullable', new VehicleIdentificationNumber],
            'description' => ['nullable', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'urgency' => ['nullable', Rule::in(['normal', 'soon', 'urgent'])],
            // No checkbox any more: the wording sits above the button, which
            // is the ordinary German pattern for a request somebody has asked
            // to be contacted about. consent_at is still recorded either way.
            'consent' => ['sometimes', 'accepted'],

            'images' => ['nullable', 'array', "max:{$maxImages}"],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', "max:{$maxKb}"],

            // Honeypot: a real person never fills this in.
            'website' => ['nullable', 'size:0'],
            'rendered_at' => ['nullable', 'numeric'],
        ];
    }

    /**
     * A submission that arrives within three seconds of the form rendering was
     * not typed by a human. No third-party captcha — that would send visitor
     * data to a third party, which is exactly what the GDPR brief rules out.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->filled('website')) {
                    $validator->errors()->add('website', 'Diese Anfrage konnte nicht verarbeitet werden.');
                }

                $renderedAt = (float) $this->input('rendered_at', 0);

                if ($renderedAt > 0 && (microtime(true) * 1000 - $renderedAt) < 3000) {
                    $validator->errors()->add(
                        'customer_name',
                        'Bitte nehmen Sie sich einen Moment Zeit und senden Sie das Formular erneut ab.'
                    );
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'service_type_id' => 'die Art des Gutachtens',
            'postal_code' => 'die Postleitzahl',
            'city' => 'der Standort des Fahrzeugs',
            'customer_name' => 'Ihr Name',
            'customer_phone' => 'Ihre Telefonnummer',
            'customer_email' => 'Ihre E-Mail-Adresse',
            'vehicle_make' => 'die Marke',
            'vehicle_model' => 'das Modell',
            'vehicle_year' => 'das Baujahr',
            'vehicle_plate' => 'das Kennzeichen',
            'vehicle_vin' => 'die Fahrgestellnummer',
            'description' => 'die Beschreibung',
            'preferred_date' => 'der Wunschtermin',
            'images' => 'die Fotos',
            'consent' => 'der Einwilligung',
        ];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'Ohne Ihre Einwilligung können wir die Anfrage nicht vermitteln.',
            'images.max' => 'Bitte laden Sie höchstens :max Fotos hoch.',
            'images.*.mimes' => 'Fotos müssen im Format JPG, PNG oder WebP vorliegen.',
        ];
    }
}
