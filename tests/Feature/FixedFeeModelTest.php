<?php

use App\Actions\MatchRequestAction;
use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
});

describe('the admin fee editor', function () {
    it('stores a fee per service type', function () {
        $type = ServiceType::factory()->create(['dkgz_fee_cents' => null, 'is_active' => true]);

        $this->actingAs($this->admin)->post("/admin/leistungsarten/{$type->slug}", [
            'name_de' => $type->name_de,
            'description_de' => $type->description_de,
            'icon' => $type->icon,
            'is_active' => true,
            'dkgz_fee_cents' => 7_900,
        ])->assertSessionHasNoErrors();

        expect($type->fresh()->dkgz_fee_cents)->toBe(7_900);
    });

    it('refuses to activate a service with no fee', function () {
        $type = ServiceType::factory()->create(['dkgz_fee_cents' => null]);

        $this->actingAs($this->admin)->post("/admin/leistungsarten/{$type->slug}", [
            'name_de' => $type->name_de,
            'is_active' => true,
            'dkgz_fee_cents' => null,
        ])->assertSessionHasErrors('dkgz_fee_cents');
    });

    it('flags an active service that has no fee', function () {
        ServiceType::factory()->create(['dkgz_fee_cents' => null, 'is_active' => true]);

        $this->actingAs($this->admin)->get('/admin/leistungsarten')
            ->assertInertia(fn ($page) => $page->where('serviceTypes.0.fee_missing', true));
    });
});

describe('the fee the assessor sees', function () {
    it('is visible on a request before any decision is made', function () {
        $type = ServiceType::factory()->create(['dkgz_fee_cents' => 7_900]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('assessor');
        $assessor = Assessor::factory()->create([
            'user_id' => $user->id,
            'approval_status' => Assessor::STATUS_APPROVED,
            'is_available' => true,
        ]);
        AssessorServiceArea::factory()->covering('40589')->create(['assessor_id' => $assessor->id]);
        $assessor->serviceTypes()->attach($type->id);

        $request = ServiceRequest::factory()->inPostalCode('40589')->create([
            'service_type_id' => $type->id,
            'reference' => ServiceRequest::nextReference(),
        ]);

        app(MatchRequestAction::class)->execute($request);

        $this->actingAs($user)->get('/portal/anfragen')
            ->assertInertia(fn ($page) => $page->where('requests.data.0.dkgz_fee_label', '79,00 €'));

        $this->actingAs($user)->get("/portal/anfragen/{$request->id}")
            ->assertInertia(fn ($page) => $page->where('request.dkgz_fee_label', '79,00 €'));
    });
});

it('no longer offers a global commission rate in the settings screen', function () {
    $this->actingAs($this->admin)->get('/admin/einstellungen/business')
        ->assertInertia(fn ($page) => $page->where(
            'settings',
            fn ($settings) => collect($settings)->pluck('field')->doesntContain('business.commission_rate')
        ));
});
