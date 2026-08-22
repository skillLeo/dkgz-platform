<?php

use App\Http\Controllers\Admin\EmailTemplateController;
use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * Mail has to survive a phone.
 *
 * A width attribute on the container was forcing 600px onto a 360px screen:
 * Gmail's mobile app honours the attribute and discards the inline max-width
 * beside it, so the client zoomed the whole message out and it arrived looking
 * like a desktop page. Nothing about that is visible in the admin preview,
 * which renders in a wide frame — so it is asserted here instead.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

/** What a client that is not Outlook actually receives. */
function renderedWithoutOutlookBranch(EmailTemplate $template): string
{
    $controller = app(EmailTemplateController::class);
    $sampleData = new ReflectionMethod($controller, 'sampleData');
    $sampleData->setAccessible(true);

    $html = (new TemplateMail($template->key, $sampleData->invoke($controller, $template)))->render();

    // Outlook on Windows gets a fixed-width table of its own inside a
    // conditional comment. Every other client skips it entirely.
    return preg_replace('/<!--\[if mso\]>.*?<!\[endif\]-->/s', '', $html) ?? '';
}

it('never forces a fixed width on a phone', function () {
    foreach (EmailTemplate::all() as $template) {
        $html = renderedWithoutOutlookBranch($template);

        expect($html)->not->toMatch('/width="\d{3,}"/', "Vorlage {$template->key} erzwingt eine feste Breite.");

        // A bare width in pixels is the same trap by another spelling; only
        // max-width may name a pixel figure.
        preg_match_all('/style="[^"]*(?<!max-)width:\s*\d{3,}px/', $html, $matches);

        expect($matches[0])->toBeEmpty("Vorlage {$template->key} setzt eine feste px-Breite.");
    }
});

it('tells the phone how wide it is and lets the layout narrow', function () {
    $html = renderedWithoutOutlookBranch(EmailTemplate::where('key', 'anfrage-eingegangen')->firstOrFail());

    expect($html)->toContain('name="viewport"')
        ->and($html)->toContain('width=device-width')
        ->and($html)->toContain('@media')
        ->and($html)->toContain('max-width: 620px')
        ->and($html)->toContain('max-width:600px');
});

it('keeps a fixed-width table for Outlook, which cannot do max-width', function () {
    $controller = app(EmailTemplateController::class);
    $sampleData = new ReflectionMethod($controller, 'sampleData');
    $sampleData->setAccessible(true);

    $template = EmailTemplate::where('key', 'anfrage-eingegangen')->firstOrFail();
    $html = (new TemplateMail($template->key, $sampleData->invoke($controller, $template)))->render();

    expect($html)->toContain('<!--[if mso]>')
        ->and($html)->toContain('<![endif]-->');

    // Opened and closed the same number of times, or Outlook renders nothing.
    expect(substr_count($html, '<!--[if mso]>'))->toBe(substr_count($html, '<![endif]-->'));
});

it('stops iOS turning reference numbers into telephone links', function () {
    $html = renderedWithoutOutlookBranch(EmailTemplate::where('key', 'anfrage-eingegangen')->firstOrFail());

    expect($html)->toContain('format-detection');
});

/**
 * Counts opening and closing tags of one kind, ignoring self-closing ones.
 *
 * @return array{open: int, close: int}
 */
function tagBalance(string $html, string $tag): array
{
    preg_match_all('/<'.$tag.'(\s[^>]*)?>/i', $html, $open);
    preg_match_all('/<\/'.$tag.'>/i', $html, $close);

    return ['open' => count($open[0]), 'close' => count($close[0])];
}

describe('the document closes every table it opens', function () {
    // The portrait block used to hand its own closing tags to the data-rows
    // branch, so a message with a portrait and no rows left a table open and
    // every element after it — the sign-off, the whole footer — drifted inside
    // it. Nothing about that is visible in a preview of the one template that
    // happens to have both.
    $combinations = [
        'nichts' => [],
        'nur Zeilen' => ['dataTitle' => 'Angaben', 'rows' => [['k' => 'Referenz', 'v' => 'DKGZ26084817']]],
        'nur Portrait' => ['sv_bild' => 'https://dkgz.test/foto.webp'],
        'nur Initialen' => ['sv_initialen' => 'JO'],
        'Portrait und Zeilen' => [
            'sv_bild' => 'https://dkgz.test/foto.webp',
            'dataTitle' => 'Angaben',
            'rows' => [['k' => 'Referenz', 'v' => 'DKGZ26084817']],
        ],
    ];

    foreach ($combinations as $name => $extra) {
        it("balances its tags with {$name}", function () use ($extra) {
            $html = (new TemplateMail('anfrage-eingegangen', array_merge([
                'headline' => 'Test',
                'referenz' => 'DKGZ26084817',
            ], $extra)))->render();

            foreach (['table', 'tr', 'td', 'div'] as $tag) {
                $balance = tagBalance($html, $tag);

                expect($balance['open'])->toBe(
                    $balance['close'],
                    "<{$tag}>: {$balance['open']} geöffnet, {$balance['close']} geschlossen."
                );
            }
        });
    }
});

it('renders the data block on a message that has no portrait', function () {
    // Almost every message has rows and no portrait. The crossed @endif meant
    // Blade skipped from the portrait condition straight past the entire rows
    // branch, so those messages arrived with their details silently missing —
    // and adding more fields to them changed nothing at all.
    $html = (new TemplateMail('neue-anfrage-im-gebiet', [
        'headline' => 'Neue Anfrage',
        'dataTitle' => 'Anfragedaten',
        'rows' => [
            ['k' => 'Art des Gutachtens', 'v' => 'Unfallgutachten'],
            ['k' => 'Standort', 'v' => '40589 Düsseldorf'],
        ],
    ]))->render();

    expect($html)->toContain('Anfragedaten')
        ->and($html)->toContain('Unfallgutachten')
        ->and($html)->toContain('40589 Düsseldorf');
});

it('renders the portrait on a message that has no rows', function () {
    $html = (new TemplateMail('sachverstaendiger-steht-fest', [
        'headline' => 'Ihr Sachverständiger steht fest.',
        'sv_bild' => 'https://dkgz.test/foto.webp',
    ]))->render();

    expect($html)->toContain('https://dkgz.test/foto.webp');

    foreach (['table', 'tr', 'td'] as $tag) {
        $balance = tagBalance($html, $tag);

        expect($balance['open'])->toBe($balance['close'], "<{$tag}> bleibt offen.");
    }
});
