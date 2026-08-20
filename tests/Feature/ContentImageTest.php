<?php

use App\Models\ContentBlock;
use App\Models\User;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ContentBlockSeeder::class);
    Storage::fake('public');

    $this->editor = User::factory()->create(['is_active' => true]);
    $this->editor->assignRole('admin');

    $this->block = ContentBlock::where('type', 'image')->firstOrFail();
});

it('stores an uploaded image against the block', function () {
    $this->actingAs($this->editor)
        ->post("/admin/inhalte-bild/{$this->block->id}", [
            'image' => UploadedFile::fake()->image('hero.jpg', 1200, 1500),
        ])->assertSessionHasNoErrors();

    $path = $this->block->fresh()->value;

    expect($path)->not->toBeEmpty();
    Storage::disk('public')->assertExists($path);
});

it('deletes the previous file when an image is replaced', function () {
    $this->actingAs($this->editor)->post("/admin/inhalte-bild/{$this->block->id}", [
        'image' => UploadedFile::fake()->image('erste.jpg'),
    ]);
    $first = $this->block->fresh()->value;

    $this->actingAs($this->editor)->post("/admin/inhalte-bild/{$this->block->id}", [
        'image' => UploadedFile::fake()->image('zweite.jpg'),
    ]);
    $second = $this->block->fresh()->value;

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertExists($second);
    Storage::disk('public')->assertMissing($first);
});

it('removes the image and clears the block', function () {
    $this->actingAs($this->editor)->post("/admin/inhalte-bild/{$this->block->id}", [
        'image' => UploadedFile::fake()->image('hero.jpg'),
    ]);
    $path = $this->block->fresh()->value;

    $this->actingAs($this->editor)
        ->delete("/admin/inhalte-bild/{$this->block->id}")
        ->assertSessionHasNoErrors();

    expect($this->block->fresh()->value)->toBe('');
    Storage::disk('public')->assertMissing($path);
});

it('refuses a file that is not an image', function () {
    $this->actingAs($this->editor)
        ->post("/admin/inhalte-bild/{$this->block->id}", [
            'image' => UploadedFile::fake()->create('schaden.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('image');

    expect($this->block->fresh()->value)->toBeEmpty();
});

it('refuses an upload onto a block that is not an image field', function () {
    $text = ContentBlock::where('type', '!=', 'image')->firstOrFail();

    $this->actingAs($this->editor)
        ->post("/admin/inhalte-bild/{$text->id}", ['image' => UploadedFile::fake()->image('x.jpg')])
        ->assertStatus(422);
});

it('does not let a role without content.edit touch images', function () {
    $support = User::factory()->create(['is_active' => true]);
    $support->assignRole('support');

    $this->actingAs($support)
        ->post("/admin/inhalte-bild/{$this->block->id}", ['image' => UploadedFile::fake()->image('x.jpg')])
        ->assertForbidden();

    $this->actingAs($support)
        ->delete("/admin/inhalte-bild/{$this->block->id}")
        ->assertForbidden();
});
