<?php

use App\Models\Assessor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('holds a flagged administrator on the password screen', function () {
    $admin = User::factory()->create(['is_active' => true, 'must_change_password' => true]);
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin/anfragen')->assertRedirect('/admin/profil');
});

it('lets a flagged user reach the screen where they can change it', function () {
    $admin = User::factory()->create(['is_active' => true, 'must_change_password' => true]);
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin/profil')->assertOk();
});

it('lets everyone else through', function () {
    $admin = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin/anfragen')->assertOk();
});

it('clears the flag once the password is changed', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => true,
        'password' => Hash::make('AltesPasswort2026!'),
    ]);
    $user->assignRole('assessor');
    Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
    ]);

    $this->actingAs($user)->post('/portal/einstellungen/passwort', [
        'current_password' => 'AltesPasswort2026!',
        'password' => 'NeuesSicheres2026!',
        'password_confirmation' => 'NeuesSicheres2026!',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->must_change_password)->toBeFalse();
});

it('flags the account when the console sets a password', function () {
    $admin = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
    $admin->assignRole('admin');

    $this->artisan("dkgz:set-admin-password {$admin->email} --password=EinLangesPasswort2026")
        ->assertSuccessful();

    expect($admin->fresh()->must_change_password)->toBeTrue()
        ->and(Hash::check('EinLangesPasswort2026', $admin->fresh()->password))->toBeTrue();
});
