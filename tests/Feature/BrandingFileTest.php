<?php

use App\Models\User;
use App\Support\Settings;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Uploading branding was one-way, and each replacement orphaned the file it
 * replaced. Both directions are covered here because the failure is invisible:
 * the setting looks right either way, and only the disk knows.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    Storage::fake('public');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('stores an uploaded logo and lets it be removed again', function () {
    $this->actingAs($this->admin)->post('/admin/einstellungen/branding', [
        'branding__logo_light' => UploadedFile::fake()->image('logo.png', 400, 120),
    ])->assertRedirect();

    $path = Settings::get('branding.logo_light');

    expect($path)->not->toBeEmpty();
    Storage::disk('public')->assertExists($path);

    $this->actingAs($this->admin)
        ->delete('/admin/einstellungen/branding/datei/branding__logo_light')
        ->assertRedirect();

    expect(Settings::get('branding.logo_light'))->toBeEmpty();
    Storage::disk('public')->assertMissing($path);
});

it('deletes the file it replaces rather than leaving it behind', function () {
    $this->actingAs($this->admin)->post('/admin/einstellungen/branding', [
        'branding__seal' => UploadedFile::fake()->image('siegel-alt.png', 200, 200),
    ]);

    $first = Settings::get('branding.seal');

    $this->actingAs($this->admin)->post('/admin/einstellungen/branding', [
        'branding__seal' => UploadedFile::fake()->image('siegel-neu.png', 200, 200),
    ]);

    $second = Settings::get('branding.seal');

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

it('refuses to remove a setting that holds no file', function () {
    $this->actingAs($this->admin)
        ->delete('/admin/einstellungen/branding/datei/branding__platform_name')
        ->assertNotFound();
});

it('keeps the removal behind the settings permission', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->delete('/admin/einstellungen/branding/datei/branding__logo_light')
        ->assertForbidden();
});
