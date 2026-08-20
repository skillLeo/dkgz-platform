<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * IBAN with the mod-97 check digit actually verified.
 *
 * A length check alone accepts a transposed pair of digits, which is the most
 * common way an IBAN is mistyped and the one failure the checksum was designed
 * to catch. Bank details are entered once and used for months, so a wrong one
 * is expensive to discover later.
 */
class Iban implements ValidationRule
{
    /** Length per country, for the ones a German assessor plausibly banks with. */
    private const LENGTHS = [
        'DE' => 22, 'AT' => 20, 'CH' => 21, 'LI' => 21, 'LU' => 20,
        'NL' => 18, 'BE' => 16, 'FR' => 27, 'IT' => 27, 'ES' => 24,
        'PL' => 28, 'CZ' => 24, 'DK' => 18,
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $iban = strtoupper(preg_replace('/\s+/', '', (string) $value));

        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{10,30}$/', $iban)) {
            $fail('Die IBAN hat kein gültiges Format.');

            return;
        }

        $country = substr($iban, 0, 2);
        $expected = self::LENGTHS[$country] ?? null;

        if ($expected !== null && strlen($iban) !== $expected) {
            $fail("Eine IBAN aus {$country} muss {$expected} Zeichen haben.");

            return;
        }

        if (! $this->checksumIsValid($iban)) {
            $fail('Die Prüfziffer der IBAN stimmt nicht. Bitte prüfen Sie die Eingabe.');
        }
    }

    /** Move the first four characters to the end, letters → numbers, mod 97 = 1. */
    private function checksumIsValid(string $iban): bool
    {
        $rearranged = substr($iban, 4).substr($iban, 0, 4);

        $numeric = '';

        foreach (str_split($rearranged) as $character) {
            $numeric .= ctype_alpha($character)
                ? (string) (ord($character) - 55)
                : $character;
        }

        // Chunked because the value overflows PHP's integer range.
        $remainder = 0;

        foreach (str_split($numeric, 7) as $chunk) {
            $remainder = (int) (((string) $remainder).$chunk) % 97;
        }

        return $remainder === 1;
    }
}
