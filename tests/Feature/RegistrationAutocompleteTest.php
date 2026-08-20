<?php

use Illuminate\Support\Facades\File;

/**
 * Mobile browsers offer to save a password whenever they believe they are
 * looking at a credential field. A field with no autocomplete attribute is a
 * field the browser guesses about — which is what produced the repeated
 * save-password prompts while filling in company and address details.
 */
it('declares an autocomplete value on every registration input', function () {
    $source = File::get(base_path('resources/js/Pages/Auth/Registrieren.vue'));

    preg_match_all('/<Base(?:Input|PasswordInput|Select|DatePicker|VatInput|PostalCodeInput)\b[^>]*/s', $source, $matches);

    $undeclared = collect($matches[0])
        ->reject(fn (string $tag) => str_contains($tag, 'autocomplete'))
        // These wrappers hard-code their own value; see the components.
        ->reject(fn (string $tag) => str_contains($tag, 'BasePostalCodeInput')
            || str_contains($tag, 'BaseVatInput')
            || str_contains($tag, 'BaseSelect')
            || str_contains($tag, 'BaseDatePicker'))
        ->map(fn (string $tag) => trim(explode("\n", $tag)[0]))
        ->values()
        ->all();

    expect($undeclared)->toBe([]);
});

it('uses new-password only on the actual password fields', function () {
    $source = File::get(base_path('resources/js/Pages/Auth/Registrieren.vue'));

    preg_match_all('/<[^>]*autocomplete="new-password"[^>]*/s', $source, $matches);

    foreach ($matches[0] as $tag) {
        expect($tag)->toContain('BasePasswordInput');
    }

    expect($matches[0])->toHaveCount(2);
});
