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
