<?php

use App\Support\Formatter;

/**
 * The acceptance checklist requires money to render identically on both sides.
 * This runs the Vue composable under Node against the same fixture table the
 * PHP Formatter is tested with, and fails if a single cell differs.
 */
function nodeAvailable(): bool
{
    exec('node --version 2>/dev/null', $out, $code);

    return $code === 0;
}

it('formats money, dates, phone and file sizes identically in PHP and JS', function () {
    if (! nodeAvailable()) {
        $this->markTestSkipped('Node ist auf diesem System nicht verfügbar.');
    }

    $moneyCases = [0, 1, 50, 85_000, 127_500, 127_550, 164_000, 12_750, 100_000_000, -127_550];
    $parseCases = ['1.275,50', '1275,50', '850,00', '850', '1.275,50 €'];
    $dateCases = ['2026-08-17T14:32:00', '2026-01-05T09:07:00', '2026-12-31T23:59:00'];
    $phoneCases = ['01794480169', '+492114470012', '+494215580120', '03012345678'];
    $sizeCases = [2_411_724, 318_000, 1_200_000, 512, 999];
    $percentCases = [15.0, 12.5, 7.25];

    // The import resolves against the script's own directory, so the path to
    // the composable is injected absolute rather than written relative.
    $composable = base_path('resources/js/Composables/useGermanFormat.js');

    $script = <<<JS
    import { useGermanFormat } from '{$composable}'
    const f = useGermanFormat()
    const input = JSON.parse(process.argv[2])
    console.log(JSON.stringify({
        money: input.money.map((c) => f.money(c)),
        parse: input.parse.map((v) => f.parseMoney(v)),
        date: input.date.map((d) => f.date(d)),
        dateTime: input.date.map((d) => f.dateTime(d)),
        phone: input.phone.map((v) => f.phone(v)),
        size: input.size.map((b) => f.fileSize(b)),
        percent: input.percent.map((v) => f.percent(v)),
    }))
    JS;

    $scriptPath = base_path('storage/framework/testing/format-parity.mjs');
    @mkdir(dirname($scriptPath), 0777, true);
    file_put_contents($scriptPath, $script);

    $payload = json_encode([
        'money' => $moneyCases,
        'parse' => $parseCases,
        'date' => $dateCases,
        'phone' => $phoneCases,
        'size' => $sizeCases,
        'percent' => $percentCases,
    ]);

    $command = sprintf(
        'cd %s && node %s %s 2>&1',
        escapeshellarg(base_path()),
        escapeshellarg($scriptPath),
        escapeshellarg($payload)
    );

    $output = shell_exec($command);
    @unlink($scriptPath);

    $js = json_decode(trim((string) $output), true);

    expect($js)->not->toBeNull("Node lieferte keine verwertbare Ausgabe: {$output}");

    expect($js['money'])->toBe(array_map(fn (int $c) => Formatter::money($c), $moneyCases));
    expect($js['parse'])->toBe(array_map(fn (string $s) => Formatter::parseMoney($s), $parseCases));
    expect($js['date'])->toBe(array_map(fn (string $d) => Formatter::date($d), $dateCases));
    expect($js['dateTime'])->toBe(array_map(fn (string $d) => Formatter::dateTime($d), $dateCases));
    expect($js['phone'])->toBe(array_map(fn (string $p) => Formatter::phone($p), $phoneCases));
    expect($js['size'])->toBe(array_map(fn (int $b) => Formatter::fileSize($b), $sizeCases));
    expect($js['percent'])->toBe(array_map(fn (float $p) => Formatter::percent($p), $percentCases));
});
