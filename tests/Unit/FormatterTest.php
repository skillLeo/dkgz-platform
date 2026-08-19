<?php

use App\Support\Formatter;

describe('money', function () {
    it('formats cents in German notation with the symbol after the value', function (int $cents, string $expected) {
        expect(Formatter::money($cents))->toBe($expected);
    })->with([
        [0, '0,00 €'],
        [1, '0,01 €'],
        [50, '0,50 €'],
        [85_000, '850,00 €'],
        [127_550, '1.275,50 €'],
        [127_500, '1.275,00 €'],
        [164_000, '1.640,00 €'],
        [12_750, '127,50 €'],
        [100_000_000, '1.000.000,00 €'],
        [-127_550, '-1.275,50 €'],
    ]);

    it('omits the symbol for input fields', function () {
        expect(Formatter::amount(127_550))->toBe('1.275,50');
    });

    it('round-trips German input back to cents', function (string $input, ?int $expected) {
        expect(Formatter::parseMoney($input))->toBe($expected);
    })->with([
        ['1.275,50', 127_550],
        ['1275,50', 127_550],
        ['850,00', 85_000],
        ['850', 85_000],
        ['1.275,50 €', 127_550],
        ['', null],
        ['keine Zahl', null],
    ]);

    it('never loses a cent across a parse and format cycle', function () {
        foreach ([1, 99, 5_000, 85_000, 127_550, 999_999, 5_000_000] as $cents) {
            expect(Formatter::parseMoney(Formatter::amount($cents)))->toBe($cents);
        }
    });
});

describe('dates', function () {
    it('formats a date as 17.08.2026', function () {
        expect(Formatter::date('2026-08-17 14:32:00'))->toBe('17.08.2026');
    });

    it('formats a date and time as 17.08.2026, 14:32 Uhr', function () {
        expect(Formatter::dateTime('2026-08-17 14:32:00'))->toBe('17.08.2026, 14:32 Uhr');
    });

    it('formats a time as 14:32 Uhr', function () {
        expect(Formatter::time('2026-08-17 14:32:00'))->toBe('14:32 Uhr');
    });

    it('returns an empty string for null', function () {
        expect(Formatter::date(null))->toBe('')
            ->and(Formatter::dateTime(null))->toBe('');
    });
});

describe('phone', function () {
    it('renders numbers exactly as the design shows them', function (string $input, string $expected) {
        expect(Formatter::phone($input))->toBe($expected);
    })->with([
        ['01794480169', '+49 179 4480169'],
        ['+492114470012', '+49 211 4470012'],
        ['+494215580120', '+49 421 5580120'],
        ['03012345678', '+49 30 12345678'],
    ]);
});

describe('percent', function () {
    it('drops needless decimals', function () {
        expect(Formatter::percent(15.0))->toBe('15 %')
            ->and(Formatter::percent(12.5))->toBe('12,5 %');
    });
});

describe('file size', function () {
    // The design labels a 2411724-byte report "2,4 MB" and a 318000-byte
    // invoice "318 KB" — decimal units, not binary.
    it('reproduces the sizes printed in the design', function (int $bytes, string $expected) {
        expect(Formatter::fileSize($bytes))->toBe($expected);
    })->with([
        [2_411_724, '2,4 MB'],
        [318_000, '318 KB'],
        [1_200_000, '1,2 MB'],
        [512, '512 B'],
    ]);
});
