<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * German plate: 1–3 letters for the district, 1–2 letters, 1–4 digits, and an
 * optional E (electric) or H (historic) suffix. Accepts a space or a hyphen as
 * the separator, because both are what people type.
 */
class GermanLicencePlate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalised = preg_replace('/\s+/', ' ', strtoupper(trim((string) $value)));

        if (! preg_match('/^[A-ZÄÖÜ]{1,3}[- ][A-Z]{1,2}[- ]?[0-9]{1,4}[EH]?$/u', (string) $normalised)) {
            $fail('Bitte geben Sie das Kennzeichen im Format D-AB 1234 ein.');
        }
    }
}
