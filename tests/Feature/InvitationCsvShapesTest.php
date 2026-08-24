<?php

use App\Support\InvitationImport;
use Illuminate\Http\UploadedFile;

/**
 * People do not produce tidy CSV. They paste out of Outlook, out of a
 * spreadsheet with the wrong locale, or type a run of addresses separated by
 * whatever key they reached for. Reading only the first address on a line meant
 * pasting fifty invited one person and silently discarded forty-nine.
 */
function addressesIn(string $content): array
{
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, $content);

    $preview = InvitationImport::preview(
        new UploadedFile($path, 'partner.csv', 'text/csv', null, true)
    );

    return array_column($preview['rows'], 'email');
}

it('reads every address on one semicolon-separated line', function () {
    expect(addressesIn('a@x.de;b@y.de;c@z.de'))
        ->toBe(['a@x.de', 'b@y.de', 'c@z.de']);
});

it('reads a line that mixes commas and semicolons', function () {
    expect(addressesIn('a@x.de;b@y.de,c@z.de'))
        ->toBe(['a@x.de', 'b@y.de', 'c@z.de']);
});

it('reads what Outlook puts on the clipboard', function () {
    expect(addressesIn('"Müller, Jan" <jan@x.de>; anna@y.de'))
        ->toBe(['jan@x.de', 'anna@y.de']);
});

it('still reads one address per line', function () {
    expect(addressesIn("a@x.de\nb@y.de\nc@z.de"))
        ->toBe(['a@x.de', 'b@y.de', 'c@z.de']);
});

it('still honours a heading row, keeping the name with its address', function () {
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, "email;name\na@x.de;Büro Eins\nb@y.de;Büro Zwei\n");

    $preview = InvitationImport::preview(
        new UploadedFile($path, 'partner.csv', 'text/csv', null, true)
    );

    expect(array_column($preview['rows'], 'email'))->toBe(['a@x.de', 'b@y.de'])
        ->and($preview['rows'][0]['name'])->toBe('Büro Eins')
        ->and($preview['rows'][1]['name'])->toBe('Büro Zwei');
});

it('reads several lines that each hold several addresses', function () {
    expect(addressesIn("a@x.de;b@y.de\nc@z.de;d@w.de"))
        ->toBe(['a@x.de', 'b@y.de', 'c@z.de', 'd@w.de']);
});

it('tolerates spaces around the separator', function () {
    expect(addressesIn('a@x.de ; b@y.de ; c@z.de'))
        ->toBe(['a@x.de', 'b@y.de', 'c@z.de']);
});

it('marks a repeated address rather than inviting twice', function () {
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, 'a@x.de;b@y.de;a@x.de');

    $preview = InvitationImport::preview(
        new UploadedFile($path, 'partner.csv', 'text/csv', null, true)
    );

    expect($preview['counts']['doppelt'])->toBe(1)
        ->and($preview['counts']['neu'])->toBe(2);
});
