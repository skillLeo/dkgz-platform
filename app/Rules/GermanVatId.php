<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * DE + nine digits, with the checksum German VAT numbers actually carry.
 * A syntactically valid but checksum-invalid number is a typo, not a number.
 */
class GermanVatId implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = strtoupper(trim((string) $value));

        if (! preg_match('/^DE[0-9]{9}$/', $value)) {
            $fail('Eine deutsche USt-IdNr. besteht aus DE und neun Ziffern.');

            return;
        }

        if (! $this->checksumValid(substr($value, 2))) {
            $fail('Diese USt-IdNr. ist nicht gültig. Bitte prüfen Sie die Ziffern.');
        }
    }

    /**
     * The ISO 7064 MOD 11,10 variant the German tax office uses.
     */
    private function checksumValid(string $digits): bool
    {
        $product = 10;

        for ($i = 0; $i < 8; $i++) {
            $sum = ((int) $digits[$i] + $product) % 10;
            $sum = $sum === 0 ? 10 : $sum;
            $product = (2 * $sum) % 11;
        }

        $check = (11 - $product) % 10;

        return $check === (int) $digits[8];
    }
}
