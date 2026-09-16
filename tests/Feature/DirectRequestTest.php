<?php

use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\AttentionQueue;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * A request made from one partner's own profile.
 *
 * It carries no postal code — the form does not ask for one, because the
 * assessor is already chosen. Everything that reads a request has to cope with
 * that, and the admin page did not: it asked which partners cover "no postal
 * code" and took a TypeError doing it, so the office could not open the case at
 * all.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    $this->seed(ContentBlockSeeder::class);

    $this->type = ServiceType::factory()->create(['is_active' => true]);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
});

function listedPartner(bool $available = true): Assessor
{
    $user = User::factory()->create(['is_active' => true]);

    $assessor = Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_listed' => true,
        'is_available' => $available,
        'company_name' => 'Sachverständigenbüro Dahab',
    ]);

    $assessor->serviceTypes()->attach(test()->type->id);

    AssessorServiceArea::factory()->create([
        'assessor_id' => $assessor->id,
        'postal_code_from' => 10000,
        'postal_code_to' => 19999,
    ]);

    return $assessor;
}

function directRequest(Assessor $assessor): ServiceRequest
{
    return ServiceRequest::factory()->create([
        'service_type_id' => test()->type->id,
        'requested_assessor_id' => $assessor->id,
        // The profile form never asks for one.
        'postal_code' => null,
        'city' => null,
        'status' => ServiceRequest::STATUS_NEW,
        'matched_count' => 0,
    ]);
}

it('opens in the admin panel even with no postal code', function () {
    $request = directRequest(listedPartner());

    $this->actingAs($this->admin)
        ->get("/admin/anfragen/{$request->id}")
        ->assertOk();
});

it('asks nobody to cover a postal code that does not exist', function () {
    // The scope took a non-nullable string and threw a TypeError on null, which
    // is what the admin page died of. No postal code means no area, so nobody
    // covers it — that is an answer, not a failure.
    expect(Assessor::covering(null)->count())->toBe(0)
        ->and(Assessor::covering('')->count())->toBe(0);
});

it('names the partner the customer chose, instead of an area', function () {
    $assessor = listedPartner();
    $request = directRequest($assessor);

    $this->actingAs($this->admin)
        ->get("/admin/anfragen/{$request->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('matching', fn ($m) => $m['requested']['id'] === $assessor->id
            && $m['requested']['company_name'] === 'Sachverständigenbüro Dahab'
            && $m['excluded'] === []));
});

it('says why the chosen partner could not be reached', function () {
    $assessor = listedPartner(available: false);
    $request = directRequest($assessor);

    $this->actingAs($this->admin)
        ->get("/admin/anfragen/{$request->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('matching', fn ($m) => $m['requested']['reachable'] === false
            && in_array('Als nicht verfügbar markiert', $m['requested']['reasons'], true)));
});

it('claims no fault when the chosen partner was reachable', function () {
    // whyNot() falls back to the liability cover once the visible criteria pass,
    // which would read as a fault on a partner who is perfectly fine.
    $request = directRequest(listedPartner());

    $this->actingAs($this->admin)
        ->get("/admin/anfragen/{$request->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('matching', fn ($m) => $m['requested']['reachable'] === true
            && $m['requested']['reasons'] === []));
});

it('tells the office the wish partner could not take it, not that an area is empty', function () {
    $request = directRequest(listedPartner(available: false));

    $matters = collect(AttentionQueue::items(PHP_INT_MAX))->pluck('matter')->implode(' | ');

    // It used to read "Kein Partner im PLZ-Gebiet " with nothing after it, and
    // it named the wrong problem anyway.
    expect($matters)->toContain('Sachverständigenbüro Dahab')
        ->and($matters)->not->toContain('PLZ-Gebiet ');

    expect($request->reference)->not->toBeEmpty();
});

describe('the profile of a partner who has paused', function () {
    it('does not offer a form that would reach nobody', function () {
        $assessor = listedPartner(available: false);

        $this->get("/sachverstaendige/{$assessor->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('assessor.accepting', false));

        $source = file_get_contents(resource_path('js/Pages/Public/Sachverstaendiger.vue'));

        expect($source)->toContain('v-if="! assessor.accepting"')
            ->and($source)->toContain("t('profil', 'pausiert_titel'");
    });

    it('still offers it while they are available', function () {
        $assessor = listedPartner();

        $this->get("/sachverstaendige/{$assessor->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('assessor.accepting', true));
    });

    it('keeps the partner in the directory either way', function () {
        // Listed and available are different things: pausing is not delisting.
        $paused = listedPartner(available: false);

        $this->get('/sachverstaendige')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('assessors.data', fn ($rows) => collect($rows)
                ->contains('slug', $paused->slug)));
    });
});
