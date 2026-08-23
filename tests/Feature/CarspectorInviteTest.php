<?php

use App\Jobs\SendInvitationJob;
use App\Models\Invitation;
use App\Models\User;
use App\Support\Mailer;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

/**
 * An invitation to somebody who already works with Carspector has to read like
 * news from a firm they know, not an approach from a stranger. Getting that
 * round the wrong way is the kind of mistake a partner remembers.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('records the audience on the invitation', function () {
    $this->actingAs($this->admin)->post('/admin/einladungen/import', [
        'emails' => ['partner@carspector.test'],
        'message' => 'Hallo.',
        'known_partner' => true,
    ])->assertRedirect();

    expect(Invitation::firstWhere('email', 'partner@carspector.test')->known_partner)->toBeTrue();
});

it('defaults to a cold invitation when the box is not ticked', function () {
    $this->actingAs($this->admin)->post('/admin/einladungen/import', [
        'emails' => ['fremd@example.test'],
        'message' => 'Hallo.',
    ])->assertRedirect();

    expect(Invitation::firstWhere('email', 'fremd@example.test')->known_partner)->toBeFalse();
});

it('sends the Carspector wording to an existing partner', function () {
    Mail::fake();

    $invitation = Invitation::create([
        'email' => 'partner@carspector.test',
        'role' => 'assessor',
        'known_partner' => true,
        'token' => Invitation::generateToken(),
        'invited_by' => $this->admin->id,
        'expires_at' => now()->addDays(14),
    ]);

    (new SendInvitationJob($invitation->id))->handle();

    expect(\App\Models\EmailLog::latest('id')->first()->template_key)
        ->toBe('einladung-carspector');
});

it('sends the ordinary wording to everybody else', function () {
    Mail::fake();

    $invitation = Invitation::create([
        'email' => 'fremd@example.test',
        'role' => 'assessor',
        'known_partner' => false,
        'token' => Invitation::generateToken(),
        'invited_by' => $this->admin->id,
        'expires_at' => now()->addDays(14),
    ]);

    (new SendInvitationJob($invitation->id))->handle();

    expect(\App\Models\EmailLog::latest('id')->first()->template_key)
        ->toBe('einladung-partnerschaft');
});

describe('the CSV import', function () {
    // The controller flashed the preview under a key the shared props did not
    // list, so it never reached Vue: the operator uploaded a file, the request
    // succeeded, and absolutely nothing appeared on screen.
    it('hands the preview back to the page', function () {
        $csv = "email;name\npartner1@example.test;Büro Eins\npartner2@example.test;Büro Zwei\n";

        $this->actingAs($this->admin)
            ->post('/admin/einladungen/import-vorschau', [
                'file' => UploadedFile::fake()->createWithContent('partner.csv', $csv),
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->get('/admin/einladungen')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('flash.invitationPreview.rows', 2)
                ->where('flash.invitationPreview.counts.neu', 2));
    });

    it('marks an address that already has an account', function () {
        User::factory()->create(['email' => 'schon@example.test']);

        $this->actingAs($this->admin)
            ->post('/admin/einladungen/import-vorschau', [
                'file' => UploadedFile::fake()->createWithContent('partner.csv', "schon@example.test\n"),
            ]);

        $this->actingAs($this->admin)
            ->get('/admin/einladungen')
            ->assertInertia(fn ($page) => $page
                ->where('flash.invitationPreview.rows.0.status', 'vorhanden'));
    });
});

it('offers the Carspector wording on a single invitation too', function () {
    $this->actingAs($this->admin)->post('/admin/einladungen', [
        'email' => 'einzeln@carspector.test',
        'role' => 'assessor',
        'known_partner' => true,
    ])->assertRedirect();

    expect(Invitation::firstWhere('email', 'einzeln@carspector.test')->known_partner)->toBeTrue();
});

it('leaves a single invitation cold unless the box is ticked', function () {
    $this->actingAs($this->admin)->post('/admin/einladungen', [
        'email' => 'einzeln2@example.test',
        'role' => 'assessor',
    ])->assertRedirect();

    expect(Invitation::firstWhere('email', 'einzeln2@example.test')->known_partner)->toBeFalse();
});
