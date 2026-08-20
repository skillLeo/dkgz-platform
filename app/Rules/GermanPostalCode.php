<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Five digits, and nothing more.
 *
 * This deliberately does not check the code against the seeded table. That
 * table holds a few hundred entries against Germany's ~8,200 codes, so it was
 * rejecting perfectly valid addresses — the seeded list is a convenience for
 * filling in a city name, never an authority on what exists. A wrong-but-valid
 * code surfaces as a request nobody covers, which the admin attention queue
 * already reports; a rejected real address just loses the customer.
 */
class GermanPostalCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[0-9]{5}$/', trim((string) $value))) {
            $fail('Bitte geben Sie eine fünfstellige Postleitzahl ein.');
        }
    }
}
