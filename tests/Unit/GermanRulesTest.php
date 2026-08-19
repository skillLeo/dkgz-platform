<?php

use App\Rules\GermanLicencePlate;
use App\Rules\GermanVatId;
use App\Rules\VehicleIdentificationNumber;

function failures($rule, $value): array
{
    $messages = [];
    $rule->validate('feld', $value, function (string $message) use (&$messages) {
        $messages[] = $message;
    });

    return $messages;
}

describe('USt-IdNr.', function () {
    it('accepts a number with a valid checksum', function () {
        // DE136695976 is the published example in the tax office specification.
        expect(failures(new GermanVatId, 'DE136695976'))->toBeEmpty();
    });

    it('rejects the wrong shape', function () {
        expect(failures(new GermanVatId, 'DE12345'))->not->toBeEmpty()
            ->and(failures(new GermanVatId, 'AT123456789'))->not->toBeEmpty();
    });

    it('rejects a correct shape with a broken checksum', function () {
        expect(failures(new GermanVatId, 'DE136695977'))->not->toBeEmpty();
    });
});

describe('Kennzeichen', function () {
    it('accepts the formats people actually type', function (string $plate) {
        expect(failures(new GermanLicencePlate, $plate))->toBeEmpty();
    })->with(['D-AB 1234', 'D-AB1234', 'M-A 1', 'GAP-XY 999', 'B-EL 100E', 'K-AB 77H']);

    it('rejects malformed plates', function (string $plate) {
        expect(failures(new GermanLicencePlate, $plate))->not->toBeEmpty();
    })->with(['1234-AB D', 'ABCD-EF 1234', 'D 12345678', '']);
});

describe('Fahrgestellnummer', function () {
    it('accepts a 17-character VIN', function () {
        expect(failures(new VehicleIdentificationNumber, 'WVWZZZ1KZAW123456'))->toBeEmpty();
    });

    it('rejects the wrong length', function () {
        expect(failures(new VehicleIdentificationNumber, 'WVWZZZ1KZAW'))->not->toBeEmpty();
    });

    it('rejects I, O and Q', function () {
        expect(failures(new VehicleIdentificationNumber, 'WVWZZZ1KZAW12345I'))->not->toBeEmpty()
            ->and(failures(new VehicleIdentificationNumber, 'WVWZZZ1KZAW12345O'))->not->toBeEmpty()
            ->and(failures(new VehicleIdentificationNumber, 'WVWZZZ1KZAW12345Q'))->not->toBeEmpty();
    });
});
