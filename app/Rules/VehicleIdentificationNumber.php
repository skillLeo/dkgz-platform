<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 17 characters. I, O and Q are excluded by the standard because they are too
 * easily confused with 1 and 0.
 */
class VehicleIdentificationNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $vin = strtoupper(trim((string) $value));

        if (strlen($vin) !== 17) {
            $fail('Eine Fahrgestellnummer besteht aus genau 17 Zeichen.');

            return;
        }

        if (! preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin)) {
            $fail('Eine Fahrgestellnummer enthält kein I, O oder Q.');
        }
    }
}
