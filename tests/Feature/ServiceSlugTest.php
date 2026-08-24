<?php

use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * The public address of a service follows its name. Leaving the old slug behind
 * means the URL describes something that is no longer there — and it lived in
 * the admin controller, so a rename from anywhere else did not update it.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
});

it('builds the slug from the name on creation', function () {
    $type = ServiceType::factory()->create(['name_de' => 'Oldtimer Gutachten']);

    expect($type->slug)->toBe('oldtimer-gutachten');
});

it('follows a rename, whoever performs it', function () {
    $type = ServiceType::factory()->create(['name_de' => 'Wertgutachten']);

    $type->update(['name_de' => 'Marktwertgutachten']);

    expect($type->fresh()->slug)->toBe('marktwertgutachten');
});

it('follows a rename made through the admin screen', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $type = ServiceType::factory()->create([
        'name_de' => 'Kurzgutachten',
        'is_active' => true,
        'dkgz_fee_cents' => 7_900,
    ]);

    $this->actingAs($admin)->post("/admin/leistungsarten/{$type->id}", [
        'name_de' => 'Ausführliches Gutachten',
        'is_active' => true,
        'dkgz_fee_cents' => 7_900,
    ])->assertSessionHasNoErrors();

    expect($type->fresh()->slug)->toBe('ausfuehrliches-gutachten');
});

it('never takes over another service address', function () {
    ServiceType::factory()->create(['name_de' => 'Unfallgutachten']);
    $second = ServiceType::factory()->create(['name_de' => 'Etwas anderes']);

    $second->update(['name_de' => 'Unfallgutachten']);

    expect($second->fresh()->slug)->toBe('unfallgutachten-2');
});

it('leaves the slug alone when the name has not changed', function () {
    $type = ServiceType::factory()->create(['name_de' => 'Beweissicherung']);
    $original = $type->slug;

    $type->update(['description_de' => 'Eine neue Beschreibung.']);

    expect($type->fresh()->slug)->toBe($original);
});

it('serves the page at the new address after a rename', function () {
    $type = ServiceType::factory()->create(['name_de' => 'Kaskogutachten', 'is_active' => true]);

    $type->update(['name_de' => 'Vollkaskogutachten']);

    $this->get('/leistungen/vollkaskogutachten')->assertOk();
});
