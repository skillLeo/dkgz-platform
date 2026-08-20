<?php

use App\Actions\StoreRequestImagesAction;
use App\Models\Assessor;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');

    $this->user = User::factory()->create(['is_active' => true, 'name' => 'Michael Reinhardt']);
    $this->user->assignRole('assessor');
    Assessor::factory()->create([
        'user_id' => $this->user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
        'company_name' => 'Kfz-Sachverständigenbüro Reinhardt',
    ]);
    $this->user = $this->user->fresh('assessor');
});

it('stores a portrait re-encoded as a square webp', function () {
    $this->actingAs($this->user)
        ->post('/portal/profil/bild', ['photo' => UploadedFile::fake()->image('portrait.jpg', 1200, 800)])
        ->assertSessionHasNoErrors()
        ->assertStatus(302);

    $path = $this->user->assessor->fresh()->photo_path;

    expect($path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);

    // Re-encoding is what strips EXIF; a stored original would keep it.
    $size = getimagesizefromstring(Storage::disk('public')->get($path));
    expect($size[0])->toBe($size[1]);
});

it('deletes the previous portrait when one is replaced', function () {
    $this->actingAs($this->user)->post('/portal/profil/bild', [
        'photo' => UploadedFile::fake()->image('erste.jpg', 400, 400),
    ]);
    $first = $this->user->assessor->fresh()->photo_path;

    $this->actingAs($this->user)->post('/portal/profil/bild', [
        'photo' => UploadedFile::fake()->image('zweite.jpg', 400, 400),
    ]);
    $second = $this->user->assessor->fresh()->photo_path;

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
});

it('removes the portrait and falls back to initials', function () {
    $this->actingAs($this->user)->post('/portal/profil/bild', [
        'photo' => UploadedFile::fake()->image('portrait.jpg', 400, 400),
    ]);
    $path = $this->user->assessor->fresh()->photo_path;

    $this->actingAs($this->user)->delete('/portal/profil/bild')->assertSessionHasNoErrors();

    expect($this->user->assessor->fresh()->photo_path)->toBeNull()
        ->and($this->user->assessor->fresh()->photoUrl())->toBeNull()
        ->and($this->user->assessor->fresh()->initials())->toBe('KR');

    Storage::disk('public')->assertMissing($path);
});

it('refuses a file that is not an image', function () {
    $this->actingAs($this->user)
        ->post('/portal/profil/bild', ['photo' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf')])
        ->assertSessionHasErrors('photo');

    expect($this->user->assessor->fresh()->photo_path)->toBeNull();
});

it('never lets one partner change another partner s portrait', function () {
    $other = User::factory()->create(['is_active' => true]);
    $other->assignRole('assessor');
    Assessor::factory()->create(['user_id' => $other->id, 'approval_status' => Assessor::STATUS_APPROVED]);

    $this->actingAs($other->fresh('assessor'))->post('/portal/profil/bild', [
        'photo' => UploadedFile::fake()->image('x.jpg', 400, 400),
    ]);

    // Each request writes to the acting user's own assessor, never an id from
    // the payload — so the first partner is untouched.
    expect($this->user->assessor->fresh()->photo_path)->toBeNull();
});

it('stores request photos too — the same image pipeline', function () {
    Storage::fake('private');

    $type = ServiceType::factory()->create();
    $request = ServiceRequest::factory()->create([
        'service_type_id' => $type->id,
        'reference' => ServiceRequest::nextReference(),
    ]);

    $stored = app(StoreRequestImagesAction::class)->execute($request, [
        UploadedFile::fake()->image('schaden.jpg', 3000, 2000),
    ]);

    expect($stored)->toBe(1);

    $image = $request->images()->firstOrFail();

    Storage::disk('private')->assertExists($image->path);
    expect($image->path)->toEndWith('.webp');
});
