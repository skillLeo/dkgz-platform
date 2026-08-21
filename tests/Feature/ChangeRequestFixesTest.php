<?php

use App\Models\Assessor;
use App\Models\ContentBlock;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    $this->seed(ContentBlockSeeder::class);
});

describe('editable content survives deployment', function () {
    // Every deployment ran the seeder, and the seeder overwrote `value`. The
    // hero photograph somebody uploaded reverted to the placeholder each time,
    // and the only clue was that it kept coming back.
    it('never overwrites a block an operator has edited', function () {
        $block = ContentBlock::where('page_key', 'startseite')
            ->where('field_key', 'bild')
            ->firstOrFail();

        $block->update(['value' => 'content/echtes-foto.webp']);

        $this->seed(ContentBlockSeeder::class);

        expect($block->fresh()->value)->toBe('content/echtes-foto.webp');
    });

    it('still adds blocks that did not exist yet', function () {
        ContentBlock::where('page_key', 'rechnung')->delete();

        $this->seed(ContentBlockSeeder::class);

        expect(ContentBlock::where('page_key', 'rechnung')->count())->toBeGreaterThan(0);
    });

    it('refreshes the label and help text, which belong to us', function () {
        $block = ContentBlock::where('page_key', 'startseite')
            ->where('field_key', 'bild')
            ->firstOrFail();

        $block->update(['label_de' => 'Kaputt', 'help_de' => null]);

        $this->seed(ContentBlockSeeder::class);

        expect($block->fresh()->label_de)->not->toBe('Kaputt')
            ->and($block->fresh()->help_de)->not->toBeNull();
    });
});

describe('branding actually reaches the page', function () {
    // The root document asked for both of these and nothing supplied them, so
    // an uploaded favicon and every configured colour had no effect at all.
    it('renders the uploaded favicon', function () {
        Settings::setMany(['branding.favicon' => 'branding/eigenes-icon.png']);

        $this->get('/')->assertOk()->assertSee('branding/eigenes-icon.png', false);
    });

    it('falls back to the bundled icon when none is set', function () {
        Settings::setMany(['branding.favicon' => null]);

        $this->get('/')->assertOk()->assertSee('/icons/icon-192.png', false);
    });

    it('injects a configured brand colour as a custom property', function () {
        Settings::setMany(['branding.color_navy_700' => '#123456']);

        $this->get('/')->assertOk()->assertSee('#123456', false);
    });

    it('refuses anything that is not a literal hex colour', function () {
        Settings::setMany(['branding.color_navy_700' => 'red;}body{display:none']);

        $this->get('/')->assertOk()->assertDontSee('display:none', false);
    });
});

describe('the portrait lives in settings', function () {
    it('is offered on the settings screen', function () {
        $assessor = Assessor::factory()->create(['approval_status' => Assessor::STATUS_APPROVED]);
        $assessor->user->assignRole('assessor');

        $this->actingAs($assessor->user)
            ->get('/portal/einstellungen')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('photo.initials'));
    });

    it('accepts a photograph larger than four megabytes', function () {
        Storage::fake('public');

        $assessor = Assessor::factory()->create(['approval_status' => Assessor::STATUS_APPROVED]);
        $assessor->user->assignRole('assessor');

        // A phone photograph; the old ceiling rejected these outright.
        $photo = UploadedFile::fake()->image('portrait.jpg', 3000, 4000)->size(9_000);

        $this->actingAs($assessor->user)
            ->post('/portal/profil/bild', ['photo' => $photo])
            ->assertSessionHasNoErrors();

        expect($assessor->fresh()->photo_path)->not->toBeNull();
    });
});

describe('requests in placement', function () {
    it('lists only what nobody has accepted', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $type = ServiceType::factory()->create();

        $waiting = ServiceRequest::factory()->create([
            'service_type_id' => $type->id,
            'status' => ServiceRequest::STATUS_MATCHED,
        ]);

        ServiceRequest::factory()->create([
            'service_type_id' => $type->id,
            'status' => ServiceRequest::STATUS_COMPLETED,
        ]);

        $this->actingAs($admin)
            ->get('/admin/in-vermittlung')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('requests.data', 1)
                ->where('requests.data.0.reference', $waiting->reference));
    });
});

describe('sending a request by hand', function () {
    // The matching engine is strict on purpose, but the office knows things it
    // does not — that somebody is free again, or will take this one anyway.
    it('notifies an assessor the engine passed over', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $type = ServiceType::factory()->create();

        // Covers the area but is marked unavailable, so matching skipped them.
        $assessor = Assessor::factory()->create([
            'approval_status' => Assessor::STATUS_APPROVED,
            'is_available' => false,
        ]);
        $assessor->serviceAreas()->create([
            'postal_code_from' => '40000',
            'postal_code_to' => '49999',
        ]);
        $assessor->serviceTypes()->sync([$type->id]);

        $serviceRequest = ServiceRequest::factory()->create([
            'service_type_id' => $type->id,
            'postal_code' => '40589',
            'status' => ServiceRequest::STATUS_NEW,
        ]);

        $this->actingAs($admin)
            ->post("/admin/anfragen/{$serviceRequest->id}/senden/{$assessor->id}")
            ->assertRedirect();

        expect($serviceRequest->fresh()->matches()->where('assessor_id', $assessor->id)->exists())
            ->toBeTrue()
            ->and($serviceRequest->fresh()->status)->toBe(ServiceRequest::STATUS_MATCHED);
    });

    it('names them among the partners it did not write to', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $type = ServiceType::factory()->create();

        $assessor = Assessor::factory()->create([
            'approval_status' => Assessor::STATUS_APPROVED,
            'is_available' => false,
        ]);
        $assessor->serviceAreas()->create([
            'postal_code_from' => '40000',
            'postal_code_to' => '49999',
        ]);
        $assessor->serviceTypes()->sync([$type->id]);

        $serviceRequest = ServiceRequest::factory()->create([
            'service_type_id' => $type->id,
            'postal_code' => '40589',
            'status' => ServiceRequest::STATUS_NEW,
        ]);

        $this->actingAs($admin)
            ->get("/admin/anfragen/{$serviceRequest->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('matching.excluded', 1)
                ->where('matching.excluded.0.reasons.0', 'Als nicht verfügbar markiert'));
    });
});
