<?php

namespace App\Rules;

use App\Models\PostalCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Five digits *and* a code that actually exists, so a typo is caught before a
 * request is created that no assessor can ever match.
 */
class ExistingPostalCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = trim((string) $value);

        if (! preg_match('/^[0-9]{5}$/', $code)) {
            $fail('Bitte geben Sie fünf Ziffern ein.');

            return;
        }

        if (! PostalCode::exists($code)) {
            $fail('Diese Postleitzahl kennen wir nicht. Bitte prüfen Sie die Eingabe.');
        }
    }
}
