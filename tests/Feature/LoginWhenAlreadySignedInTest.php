<?php

use App\Models\Assessor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * Somebody whose session is still alive asks for the login screen.
 *
 * Laravel's default sends them to the site root, and the public homepage says
 * nothing about being signed in — so a partner who came back to the site
 * clicked "Partner-Portal", landed on the homepage, and concluded that logging
 * in was broken. They were never signed out; they were being bounced to a page
 * that would not admit it.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
});

it('sends a signed-in partner to their portal rather than the homepage', function () {
    $assessor = Assessor::factory()->create(['approval_status' => Assessor::STATUS_APPROVED]);
    $assessor->user->assignRole('assessor');

    $this->actingAs($assessor->user)
        ->get('/anmelden')
        ->assertRedirect(route('portal.dashboard'));
});

it('sends a signed-in administrator to the admin panel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/anmelden')
        ->assertRedirect(route('admin.dashboard'));

    $this->actingAs($admin)
        ->get('/admin/anmelden')
        ->assertRedirect(route('admin.dashboard'));
});

it('still shows the login screen to somebody who is not signed in', function () {
    $this->get('/anmelden')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Auth/Anmelden'));
});

it('lets a signed-out user log in again straight away', function () {
    $assessor = Assessor::factory()->create(['approval_status' => Assessor::STATUS_APPROVED]);
    $assessor->user->assignRole('assessor');
    $assessor->user->update(['password' => bcrypt('EinPasswort2026!')]);

    $this->actingAs($assessor->user)->post('/abmelden');

    $this->get('/anmelden')->assertOk();

    $this->post('/anmelden', [
        'email' => $assessor->user->email,
        'password' => 'EinPasswort2026!',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($assessor->user->fresh());
});
