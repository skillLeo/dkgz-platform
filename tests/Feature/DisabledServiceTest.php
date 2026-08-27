<?php

use App\Models\Assessor;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * What happens to a service that is switched off.
 *
 * Switching one off is meant to retire it: nobody should be offered it, matched
 * for it, or told about it. But the pivot that records which assessor offers
 * what does not know about is_active, so a retired service went on being listed
 * to partners, went on being selectable when the office logged a request by
 * telephone, and went on being e-mailed out — under a name the site no longer
 * shows anywhere.
 *
 * The rows themselves stay. Switching a service off is not the same as deleting
 * it, and an operator who turns one back on should find their partners still
 * signed up for it rather than having to ask 130 people to tick it again.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);

    $this->live = ServiceType::factory()->create([
        'name_de' => 'Unfallgutachten', 'is_active' => true, 'dkgz_fee_cents' => 8500,
    ]);
    $this->retired = ServiceType::factory()->create([
        'name_de' => 'Alter Name', 'is_active' => true, 'dkgz_fee_cents' => 8500,
    ]);

    $this->user = User::factory()->create();
    $this->user->assignRole('assessor');
    $this->assessor = Assessor::factory()->create([
        'user_id' => $this->user->id,
        'approval_status' => 'approved',
        'is_available' => true,
    ]);
    $this->assessor->serviceTypes()->sync([$this->live->id, $this->retired->id]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // Renamed, then switched off — the two things the operator did.
    $this->retired->update(['name_de' => 'Neuer Name']);
    $this->retired->update(['is_active' => false]);
});

describe('the partner', function () {
    it('is not shown a service that has been switched off', function () {
        $this->actingAs($this->user)
            ->get('/portal/leistungen')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('serviceTypes', 1)
                ->where('selected', [$this->live->id]));
    });

    it('cannot filter their requests by one either', function () {
        $this->actingAs($this->user)
            ->get('/portal/anfragen')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('serviceTypes', 1));
    });

    it('cannot sign up for one', function () {
        $this->actingAs($this->user)
            ->post('/portal/leistungen', ['service_type_ids' => [$this->retired->id]])
            ->assertSessionHasErrors('service_type_ids.0');
    });

    it('keeps their old choice, so turning it back on restores it', function () {
        // Saving from a form that cannot see the retired service must not be
        // read as the partner giving it up.
        $this->actingAs($this->user)
            ->post('/portal/leistungen', ['service_type_ids' => [$this->live->id]])
            ->assertSessionHasNoErrors();

        expect($this->assessor->serviceTypes()->pluck('service_types.id')->all())
            ->toEqualCanonicalizing([$this->live->id, $this->retired->id]);

        $this->retired->update(['is_active' => true]);

        $this->actingAs($this->user)
            ->get('/portal/leistungen')
            ->assertInertia(fn ($page) => $page->has('serviceTypes', 2));
    });
});

describe('the office', function () {
    it('cannot log a request against a service that is switched off', function () {
        $this->actingAs($this->admin)
            ->get('/admin/anfragen/neu')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('serviceTypes', 1));
    });

    it('sees plainly which of a partner\'s services are retired', function () {
        $this->actingAs($this->admin)
            ->get("/admin/sachverstaendige/{$this->assessor->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('assessor.service_types', ['Unfallgutachten'])
                ->where('assessor.retired_service_types', ['Neuer Name']));
    });
});

describe('matching', function () {
    it('passes over an assessor whose only match is a retired service', function () {
        expect(Assessor::query()->offering($this->retired->id)->count())->toBe(0);
        expect(Assessor::query()->offering($this->live->id)->count())->toBe(1);
    });
});

describe('renaming', function () {
    it('reaches the partner straight away', function () {
        $this->live->update(['name_de' => 'Unfall- & Haftpflichtgutachten']);

        $this->actingAs($this->user)
            ->get('/portal/leistungen')
            ->assertInertia(fn ($page) => $page
                ->where('serviceTypes.0.name_de', 'Unfall- & Haftpflichtgutachten'));
    });
});
