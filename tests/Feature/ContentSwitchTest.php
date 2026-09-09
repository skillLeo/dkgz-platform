<?php

use App\Models\ContentBlock;
use App\Models\User;
use App\Support\Content;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * Switch blocks — a content block that is an on/off state rather than a string.
 *
 * The whole point is that hiding a part of a page no longer means deleting the
 * words that fill it, so the tests worth having are the ones that prove the
 * words survive being switched off and that "off" survives the trip to the page.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);

    $this->switch = ContentBlock::where('page_key', 'startseite')
        ->where('section_key', 'hero')
        ->where('field_key', 'eyebrow_anzeigen')
        ->firstOrFail();
});

it('seeds the hero switch as a boolean block that starts on', function () {
    expect($this->switch->type)->toBe('boolean')
        ->and($this->switch->value)->toBe('1');
});

it('hands a switch to the page as a real boolean, never as text', function () {
    // The string "0" is false in PHP and true in JavaScript. Handed over as
    // text, an off switch would read as on in every Vue template on the site.
    $this->switch->update(['value' => '0']);
    Content::flush('startseite');

    $value = Content::page('startseite')['hero']['eyebrow_anzeigen'];

    expect($value)->toBeFalse()
        ->and($value)->not->toBe('0');
});

it('reads an on switch as true', function () {
    expect(Content::page('startseite')['hero']['eyebrow_anzeigen'])->toBeTrue();
});

it('counts a switch nobody has set as on, not as off', function () {
    // Empty is "not set", which is not the same as "off". Collapsing the two
    // would mean a switch added by hand hides the section it was added to
    // control, which is the one outcome the default exists to prevent.
    $this->switch->update(['value' => '']);
    Content::flush('startseite');

    expect(Content::page('startseite')['hero']['eyebrow_anzeigen'])->toBeNull()
        ->and(Content::bool('startseite.hero.eyebrow_anzeigen'))->toBeTrue();
});

it('counts a switch that was never seeded as on', function () {
    // Otherwise adding a switch would hide the thing it was added to control on
    // every site that has the new page but not yet the new row.
    expect(Content::bool('startseite.hero.gibtsnicht'))->toBeTrue()
        ->and(Content::bool('startseite.hero.gibtsnicht', false))->toBeFalse();
});

it('reads an off switch through the dotted accessor', function () {
    $this->switch->update(['value' => '0']);
    Content::flush('startseite');

    expect(Content::bool('startseite.hero.eyebrow_anzeigen'))->toBeFalse();
});

it('keeps the wording when the switch is turned off', function () {
    $this->switch->update(['value' => '0']);
    Content::flush('startseite');

    expect(Content::get('startseite.hero.eyebrow'))->toBe('Deutsche Kfz-Gutachterzentrale');
});

it('carries the switch to the homepage as a boolean prop', function () {
    $this->switch->update(['value' => '0']);
    Content::flush('startseite');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('content.hero.eyebrow_anzeigen', false)
            ->where('content.hero.eyebrow', 'Deutsche Kfz-Gutachterzentrale'));
});

it('lets an editor flip the switch from the content form', function () {
    $editor = User::factory()->create(['is_active' => true]);
    $editor->assignRole('admin');

    $this->actingAs($editor)
        ->post('/admin/inhalte/startseite', [
            'blocks' => [['id' => $this->switch->id, 'value' => '0']],
        ])
        ->assertSessionHasNoErrors();

    expect($this->switch->fresh()->value)->toBe('0')
        ->and(Content::bool('startseite.hero.eyebrow_anzeigen'))->toBeFalse();

    $this->actingAs($editor)
        ->post('/admin/inhalte/startseite', [
            'blocks' => [['id' => $this->switch->id, 'value' => '1']],
        ])
        ->assertSessionHasNoErrors();

    expect(Content::bool('startseite.hero.eyebrow_anzeigen'))->toBeTrue();
});

/**
 * There is no JavaScript test runner in this project, so the page's own half of
 * the feature is pinned the way the rest of the suite pins Vue behaviour: by
 * reading the source. Without this, showEyebrow could be deleted or flag()
 * inverted and every other test here would still pass.
 */
it('actually gates the hero line on the switch', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/Startseite.vue'));

    expect($source)->toContain('<template v-if="showEyebrow">')
        ->and($source)->toContain("flag('hero', 'eyebrow_anzeigen') && Boolean(t('hero', 'eyebrow'))");
});

it('keeps the page showing the line while the switch is still missing', function () {
    // Files reach dkgz.de over FTP before the seeder runs, so for a minute the
    // live homepage renders with no such key at all.
    $source = file_get_contents(resource_path('js/Support/switches.js'));

    // Unset falls back to the caller's default, and that default is ON — a
    // switch added to hide something must not hide it before its row exists.
    expect($source)->toContain('export function isOn (content, section, field, whenUnset = true)')
        ->and($source)->toContain("if (value === undefined || value === null || value === '') return whenUnset")
        ->and($source)->toContain("value !== false && value !== '0'");
});

it('offers the switch to the editor alongside its help text', function () {
    $editor = User::factory()->create(['is_active' => true]);
    $editor->assignRole('admin');

    $this->actingAs($editor)
        ->get('/admin/inhalte/startseite')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('sections.hero', function ($fields) {
            $switch = collect($fields)->firstWhere('field_key', 'eyebrow_anzeigen');

            return $switch !== null
                && $switch['type'] === 'boolean'
                && filled($switch['help']);
        }));
});
