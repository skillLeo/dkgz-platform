<?php

use App\Http\Controllers\Admin\EmailTemplateController;
use App\Models\EmailTemplate;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * The template editor shows the preview inside a frame, which a blanket
 * X-Frame-Options: DENY silently blocked — the preview rendered perfectly and
 * the editor showed an empty box. Headers are therefore part of this feature
 * working, not merely policy around it.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('renders a preview the editor is allowed to frame', function () {
    $template = EmailTemplate::where('key', 'anfrage-eingegangen')->firstOrFail();

    $response = $this->actingAs($this->admin)
        ->get("/admin/email-vorlagen/{$template->key}/vorschau")
        ->assertOk();

    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'self'");
});

it('still refuses to be framed everywhere else', function () {
    $response = $this->actingAs($this->admin)->get('/admin')->assertOk();

    expect($response->headers->get('X-Frame-Options'))->toBe('DENY')
        ->and($response->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'none'");
});

it('greets the reader exactly once', function () {
    // The invariant is that the layout adds no greeting of its own: whatever
    // the editable body says, the rendered mail says exactly that and no more.
    // Counting raw occurrences would not do — a quoted contact enquiry really
    // does open with "Guten Tag", and that greeting belongs to the sender.
    $controller = app(EmailTemplateController::class);
    $sampleData = new ReflectionMethod($controller, 'sampleData');
    $sampleData->setAccessible(true);

    foreach (EmailTemplate::all() as $template) {
        $samples = $sampleData->invoke($controller, $template);
        $body = $template->body_html;

        foreach ($samples as $name => $value) {
            if (is_string($value)) {
                $body = str_replace(['{{ '.$name.' }}', '{{'.$name.'}}'], $value, $body);
            }
        }

        $html = $this->actingAs($this->admin)
            ->get("/admin/email-vorlagen/{$template->key}/vorschau")
            ->assertOk()
            ->getContent();

        expect(substr_count($html, 'Guten Tag'))->toBe(
            substr_count($body, 'Guten Tag'),
            "Vorlage {$template->key}: die Vorlage grüßt anders als die fertige Mail."
        );

        expect(substr_count($body, 'Guten Tag'))
            ->toBeGreaterThanOrEqual(1, "Vorlage {$template->key} hat gar keine Anrede.");
    }
});

it('never greets with a blank name', function () {
    // Moving the greeting into the editable body means each template's greeting
    // depends on a variable its sender actually supplies. Get that wrong and the
    // customer reads "Guten Tag ,", which no test of the sending path would see.
    foreach (EmailTemplate::all() as $template) {
        $html = $this->actingAs($this->admin)
            ->get("/admin/email-vorlagen/{$template->key}/vorschau")
            ->assertOk()
            ->getContent();

        expect($html)
            ->not->toContain('Guten Tag ,', "Vorlage {$template->key} grüßt ohne Namen.")
            ->not->toContain('Guten Tag,,', "Vorlage {$template->key} grüßt ohne Namen.");

        // A greeting whose placeholder never got a value leaves the braces in.
        expect($html)->not->toMatch('/Guten Tag[^<]*\{\{/');
    }
});
