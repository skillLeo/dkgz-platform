<?php

namespace App\Rules;

use App\Models\PostalCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Five digits that name a real place in Germany.
 *
 * This is for the customer's request, where the postal code is the one thing
 * the matching runs on and the form shows the town back as confirmation that it
 * landed. A code nobody can resolve is a typo, and letting it through produces
 * a request no assessor covers and a customer who never hears anything.
 *
 * Deliberately not used where a partner lists the areas they cover: those are
 * ranges and edges of their own choosing, and a reference table has no business
 * telling somebody which streets they are willing to drive to. That is what
 * {@see GermanPostalCode} is still for.
 *
 * If the table is empty — a fresh install nobody has seeded — the shape check
 * stands on its own rather than rejecting the whole country.
 */
class ExistingPostalCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = trim((string) $value);

        if (! preg_match('/^[0-9]{5}$/', $code)) {
            $fail('Bitte geben Sie eine fünfstellige Postleitzahl ein.');

            return;
        }

        if (PostalCode::query()->exists() && ! PostalCode::exists($code)) {
            $fail('Diese Postleitzahl gibt es in Deutschland nicht. Bitte prüfen Sie Ihre Eingabe.');
        }
    }
}
