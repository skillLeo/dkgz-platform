<?php

use App\Models\EmailTemplate;
use Database\Seeders\ProductionSeeder;
use Illuminate\Support\Facades\File;

/**
 * The report goes to the client, so its arithmetic is held by a test rather
 * than by whoever last edited it. The earlier hand-written version disagreed
 * with itself: screen counts that did not sum, and a table missing templates
 * that existed.
 */
beforeEach(function () {
    $this->seed(ProductionSeeder::class);
    $this->path = 'FLOW_REPORT.test.md';
    $this->artisan("dkgz:generate-flow-report --path={$this->path}")->assertSuccessful();
    $this->report = File::get(base_path($this->path));
});

afterEach(fn () => File::delete(base_path($this->path)));

it('makes the screen counts sum to the stated total', function () {
    preg_match('/\| Bildschirme \| (\d+) \|/', $this->report, $total);
    preg_match('/\| davon \| (.+?) \|/', $this->report, $parts);

    $numbers = [];
    preg_match_all('/(\d+)/', $parts[1], $numbers);
    $numbers = array_map('intval', $numbers[1]);

    $stated = array_pop($numbers);

    expect(array_sum($numbers))->toBe($stated)
        ->and($stated)->toBe((int) $total[1]);
});

it('lists every e-mail template that exists, with a recipient for each', function () {
    $count = EmailTemplate::count();

    expect($this->report)->toContain("## E-Mail-Vorlagen ({$count})");

    foreach (EmailTemplate::pluck('key') as $key) {
        expect($this->report)->toContain("`{$key}`");
    }

    // Every row must name a recipient; an em dash means the map has a gap.
    preg_match('/## E-Mail-Vorlagen.*?\n\n(.*?)\n\n/s', $this->report, $section);
    expect($section[1])->not->toContain('| — | — |');
});

it('gives every section heading a count matching its own table', function () {
    $sections = [
        'Öffentliche Seiten' => '/\| `\/[^`]*` \|/',
        'Rechtliche Seiten' => '/\| `\/[^`]*` \|/',
        'Rollen und Berechtigungen' => '/\| `[a-z_]+` \|/',
        'E-Mail-Vorlagen' => '/\| `[a-z-]+` \|/',
        'Geplante Aufgaben' => '/\| `[^`]+` \|/',
    ];

    foreach ($sections as $heading => $rowPattern) {
        preg_match('/## '.preg_quote($heading, '/').' \((\d+)\)\n\n(.*?)(?=\n## |\z)/s', $this->report, $m);

        expect($m)->not->toBeEmpty("Abschnitt „{$heading}“ fehlt");

        $rows = preg_match_all($rowPattern, $m[2]);

        expect($rows)->toBe((int) $m[1], "„{$heading}“: Überschrift {$m[1]}, Tabelle {$rows}");
    }
});

it('documents the withdrawal page, the unplaced status and the fee model', function () {
    expect($this->report)
        ->toContain('/widerruf')
        ->toContain('`unanswered`')
        ->toContain('anfrage-keine-rueckmeldung')
        ->toContain('Gebührenmodell')
        // The deadline was removed; the report must not describe one.
        ->not->toContain('`expired`')
        ->not->toContain('Frist läuft');
});

it('never prints a test figure it did not verify', function () {
    // The recorded summary is a real repository artefact; borrow it, do not
    // destroy it.
    $summary = base_path('storage/app/private/test-summary.json');
    $backup = File::exists($summary) ? File::get($summary) : null;

    File::delete($summary);

    try {
        $this->artisan("dkgz:generate-flow-report --path={$this->path}")->assertSuccessful();

        expect(File::get(base_path($this->path)))->toContain('nicht ermittelt');
    } finally {
        if ($backup !== null) {
            File::put($summary, $backup);
        }
    }
});
