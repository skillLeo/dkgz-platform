<?php

use App\Models\Assessor;
use App\Models\PostalCode;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** An administrator with the permission the assessor screens are gated on. */
function registrationAdmin(): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('admin');

    return $user;
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ServiceTypeSeeder::class);
    PostalCode::create(['code' => '40589', 'city' => 'Düsseldorf', 'state' => 'Nordrhein-Westfalen']);
});

function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Michael',
        'last_name' => 'Reinhardt',
        'email' => 'm.reinhardt@kfz-gutachten-d.test',
        'phone' => '+49 211 4470012',
        'password' => 'Gutachten2026!',
        'password_confirmation' => 'Gutachten2026!',
        'company_name' => 'Kfz-Sachverständigenbüro Reinhardt',
        'legal_form' => 'einzelunternehmen',
        'street' => 'Musterstraße',
        'house_number' => '12',
        'postal_code' => '40589',
        'city' => 'Düsseldorf',
        'vat_id' => 'DE136695976',
        'certification_body' => 'tuev',
        'certification_number' => 'TU-12345',
        'service_type_ids' => ServiceType::query()->limit(2)->pluck('id')->all(),
        'service_areas' => [['from' => '40000', 'to' => '40999', 'label' => 'Düsseldorf und Umgebung']],
        'terms' => true,
        'privacy' => true,
    ], $overrides);
}

it('shows the registration screen with service types', function () {
    $this->get('/registrieren')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Auth/Registrieren')->has('serviceTypes', 8));
});

it('closes registration when the feature toggle is off', function () {
    Settings::set('features.self_registration', false);

    $this->get('/registrieren')->assertRedirect(route('partner'));
});

it('creates the user, the profile, the areas and the services', function () {
    $this->post('/registrieren', registrationPayload())
        ->assertRedirect(route('registration.pending'));

    $user = User::firstWhere('email', 'm.reinhardt@kfz-gutachten-d.test');

    expect($user)->not->toBeNull()
        ->and($user->hasRole('assessor'))->toBeTrue()
        ->and($user->name)->toBe('Michael Reinhardt');

    $assessor = $user->assessor;

    expect($assessor->approval_status)->toBe(Assessor::STATUS_PENDING)
        ->and($assessor->company_name)->toBe('Kfz-Sachverständigenbüro Reinhardt')
        ->and($assessor->serviceAreas)->toHaveCount(1)
        ->and($assessor->serviceTypes)->toHaveCount(2)
        ->and($assessor->serviceAreas->first()->postal_code_from)->toBe('40000');
});

it('stores both proofs on the private disk, typed and named', function () {
    Storage::fake('private');

    $this->post('/registrieren', registrationPayload([
        'documents' => [
            ['type' => 'qualification', 'file' => UploadedFile::fake()->create('qualifikation.pdf', 400, 'application/pdf')],
            ['type' => 'liability', 'file' => UploadedFile::fake()->create('haftpflicht.pdf', 250, 'application/pdf')],
        ],
    ]))->assertRedirect(route('registration.pending'));

    $documents = User::firstWhere('email', 'm.reinhardt@kfz-gutachten-d.test')->assessor->documents;

    expect($documents)->toHaveCount(2)
        ->and($documents->pluck('type')->all())->toBe(['qualification', 'liability'])
        ->and($documents->firstWhere('type', 'qualification')->original_name)->toBe('qualifikation.pdf');

    foreach ($documents as $document) {
        Storage::disk('private')->assertExists($document->path);
        expect($document->size_bytes)->toBeGreaterThan(0);
    }
});

it('refuses more than three proofs', function () {
    Storage::fake('private');

    $this->post('/registrieren', registrationPayload([
        'documents' => collect(range(1, 4))->map(fn (int $n) => [
            'type' => 'other',
            'file' => UploadedFile::fake()->create("nachweis-{$n}.pdf", 100, 'application/pdf'),
        ])->all(),
    ]))->assertSessionHasErrors('documents');
});

it('lets an administrator download a submitted proof', function () {
    Storage::fake('private');

    $this->post('/registrieren', registrationPayload([
        'documents' => [
            ['type' => 'qualification', 'file' => UploadedFile::fake()->create('qualifikation.pdf', 400, 'application/pdf')],
        ],
    ]));

    $assessor = User::firstWhere('email', 'm.reinhardt@kfz-gutachten-d.test')->assessor;
    $document = $assessor->documents->first();

    $this->actingAs(registrationAdmin())
        ->get(route('admin.assessors.documents.download', [$assessor, $document]))
        ->assertOk()
        ->assertDownload('qualifikation.pdf');
});

it('accepts any well-formed five-digit postal code', function () {
    // Superseded by the client's change request: the seeded table holds a few
    // hundred of Germany's ~8,200 codes, so checking against it rejected real
    // addresses. Format is now the only rule.
    $this->post('/registrieren', registrationPayload(['postal_code' => '99999']))
        ->assertSessionHasNoErrors();
});

it('still rejects a postal code that is not five digits', function () {
    $this->post('/registrieren', registrationPayload(['postal_code' => '405']))
        ->assertSessionHasErrors('postal_code');
});

it('rejects a VAT number with a broken checksum', function () {
    $this->post('/registrieren', registrationPayload(['vat_id' => 'DE136695977']))
        ->assertSessionHasErrors('vat_id');
});

it('rejects a weak password', function () {
    $this->post('/registrieren', registrationPayload([
        'password' => 'passwort',
        'password_confirmation' => 'passwort',
    ]))->assertSessionHasErrors('password');
});

it('requires both consents', function () {
    $this->post('/registrieren', registrationPayload(['terms' => false]))->assertSessionHasErrors('terms');
    $this->post('/registrieren', registrationPayload(['privacy' => false]))->assertSessionHasErrors('privacy');
});

it('requires at least one service and one area', function () {
    $this->post('/registrieren', registrationPayload(['service_type_ids' => []]))->assertSessionHasErrors('service_type_ids');
    $this->post('/registrieren', registrationPayload(['service_areas' => []]))->assertSessionHasErrors('service_areas');
});

it('refuses an area whose end is below its start', function () {
    $this->post('/registrieren', registrationPayload([
        'service_areas' => [['from' => '40999', 'to' => '40000']],
    ]))->assertSessionHasErrors('service_areas.0.to');
});

it('refuses a duplicate e-mail address', function () {
    User::factory()->create(['email' => 'm.reinhardt@kfz-gutachten-d.test']);

    $this->post('/registrieren', registrationPayload())->assertSessionHasErrors('email');
});

describe('step autosave', function () {
    it('validates and remembers one step at a time', function () {
        $this->post('/registrieren/schritt/1', [
            'first_name' => 'Michael',
            'last_name' => 'Reinhardt',
            'email' => 'm.reinhardt@kfz-gutachten-d.test',
            'phone' => '+49 211 4470012',
            'password' => 'Gutachten2026!',
            'password_confirmation' => 'Gutachten2026!',
        ])->assertRedirect();

        $this->assertEquals('Michael', session('registrierung.first_name'));
        $this->assertEquals(2, session('registrierung._step'));
    });

    it('reports errors for that step without touching the others', function () {
        $this->post('/registrieren/schritt/1', ['first_name' => ''])
            ->assertSessionHasErrors(['first_name', 'email']);
    });

    it('rejects an unknown step', function () {
        $this->post('/registrieren/schritt/9', [])->assertNotFound();
    });
});

describe('registration with only the required fields', function () {
    it('succeeds without certification, without proofs, and with an unlisted postal code', function () {
        $payload = registrationPayload([
            'certification_body' => null,
            'certification_number' => null,
            // Deliberately a code the seeded table does not hold: the table
            // covers a fraction of Germany and must never gate registration.
            'postal_code' => '17033',
            'city' => 'Neubrandenburg',
        ]);
        unset($payload['documents']);

        $this->post('/registrieren', $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('registration.pending'));

        $user = User::firstWhere('email', 'm.reinhardt@kfz-gutachten-d.test');

        expect($user)->not->toBeNull()
            ->and($user->assessor)->not->toBeNull()
            ->and($user->assessor->certification_body)->toBeNull()
            ->and($user->assessor->certification_number)->toBeNull()
            ->and($user->assessor->postal_code)->toBe('17033')
            ->and($user->assessor->documents)->toHaveCount(0)
            ->and($user->assessor->approval_status)->toBe(Assessor::STATUS_PENDING);
    });

    it('requires the certification number once a body is chosen', function () {
        $this->post('/registrieren', registrationPayload([
            'certification_body' => 'tuev',
            'certification_number' => null,
        ]))->assertSessionHasErrors('certification_number');
    });

    it('requires the body once a number is given', function () {
        $this->post('/registrieren', registrationPayload([
            'certification_body' => null,
            'certification_number' => 'TU-12345',
        ]))->assertSessionHasErrors('certification_body');
    });

    it('does not treat a partner without proofs as having lapsed cover', function () {
        $payload = registrationPayload(['certification_body' => null, 'certification_number' => null]);
        unset($payload['documents']);

        $this->post('/registrieren', $payload);

        $assessor = User::firstWhere('email', 'm.reinhardt@kfz-gutachten-d.test')->assessor;

        expect($assessor->liabilityCoverHasLapsed())->toBeFalse()
            ->and($assessor->liabilityCoverValidUntil())->toBeNull();
    });
});
