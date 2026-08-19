<?php

use App\Models\Assignment;

it('keeps money as an integer number of cents', function () {
    $assignment = new Assignment(['fee_cents' => 164_000]);

    expect($assignment->fee_cents)->toBe(164_000)->toBeInt();
});

it('accepts a numeric string of cents', function () {
    $assignment = new Assignment(['fee_cents' => '85000']);

    expect($assignment->fee_cents)->toBe(85_000);
});

it('refuses a float, because money is never floated', function () {
    expect(fn () => new Assignment(['fee_cents' => 850.00]))
        ->toThrow(InvalidArgumentException::class);
});

it('allows null for a fee that has not been entered yet', function () {
    $assignment = new Assignment(['fee_cents' => null]);

    expect($assignment->fee_cents)->toBeNull();
});
