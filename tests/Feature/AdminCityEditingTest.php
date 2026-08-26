<?php

use App\Models\City;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Editing and deleting a city from the admin panel.
 *
 * The model is addressed by its slug in public URLs, because that is what the
 * city pages are built from. The admin panel is not a public URL: it holds the
 * row's id, posts the id, and every save and delete came back 404 — the same
 * shape of bug that once made the service types unsaveable.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->city = City::create(['name' => 'Düsseldorf', 'is_active' => true]);
});

it('saves a city addressed by its id', function () {
    $this->actingAs($this->admin)
        ->post("/admin/staedte/{$this->city->id}", [
            'name' => 'Düsseldorf',
            'state' => 'Nordrhein-Westfalen',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($this->city->fresh()->state)->toBe('Nordrhein-Westfalen');
});

it('deletes a city addressed by its id', function () {
    $id = $this->city->id;

    $this->actingAs($this->admin)
        ->delete("/admin/staedte/{$id}")
        ->assertRedirect();

    expect(City::find($id))->toBeNull();
});

it('keeps the services ticked against the city when it is saved', function () {
    $type = ServiceType::factory()->create(['name_de' => 'Unfallgutachten', 'is_active' => true]);

    $this->actingAs($this->admin)
        ->post("/admin/staedte/{$this->city->id}", [
            'name' => 'Düsseldorf',
            'is_active' => true,
            'service_type_ids' => [$type->id],
        ])
        ->assertSessionHasNoErrors();

    expect($this->city->fresh()->serviceTypes)->toHaveCount(1);
});

it('renames a city and takes its address with it', function () {
    // The slug follows the name, so the pages move — which is the whole reason
    // the admin panel must not be addressing the row by that slug.
    $this->actingAs($this->admin)
        ->post("/admin/staedte/{$this->city->id}", ['name' => 'Köln', 'is_active' => true])
        ->assertSessionHasNoErrors();

    expect($this->city->fresh()->slug)->toBe('koeln');
});

it('still refuses somebody without the permission', function () {
    $editor = User::factory()->create();
    $editor->assignRole('content_editor');

    $this->actingAs($editor)
        ->post("/admin/staedte/{$this->city->id}", ['name' => 'Köln'])
        ->assertForbidden();
});
