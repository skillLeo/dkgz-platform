<?php

use App\Models\City;
use App\Models\ContentBlock;
use App\Models\Post;
use App\Models\SeoSetting;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * The SEO screen.
 *
 * It does not hold a copy of anything. Titles and descriptions are read from
 * wherever they already live and an edit is written straight back there, so the
 * screen and the page's own editor can never disagree. The one thing it stores
 * is whether a page may be indexed, which has nowhere else to live.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

describe('the list', function () {
    it('gathers every kind of public page', function () {
        ServiceType::factory()->create(['name_de' => 'Unfallgutachten', 'is_active' => true]);
        $city = City::create(['name' => 'Köln', 'is_active' => true]);
        Post::factory()->published()->create(['title' => 'Wer zahlt das Gutachten?']);

        $this->actingAs($this->admin)
            ->get('/admin/seo')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pages', fn ($pages) => collect($pages)->pluck('group')->unique()->sort()->values()->all()
                    === ['Leistungen', 'Ratgeber', 'Seiten', 'Städte']));
    });

    it('marks a page with no description', function () {
        ContentBlock::where('page_key', 'kontakt')->where('field_key', 'meta_text')->update(['value' => '']);

        $this->actingAs($this->admin)
            ->get('/admin/seo')
            ->assertInertia(fn ($page) => $page
                ->where('pages', fn ($pages) => collect($pages)
                    ->firstWhere('path', '/kontakt')['issues'] === ['Keine Beschreibung']));
    });

    it('marks a title that Google will cut off', function () {
        ContentBlock::where('page_key', 'kontakt')->where('field_key', 'meta_titel')
            ->update(['value' => str_repeat('Sehr langer Titel ', 8)]);

        $this->actingAs($this->admin)
            ->get('/admin/seo')
            ->assertInertia(fn ($page) => $page
                ->where('pages', fn ($pages) => in_array('Titel zu lang', collect($pages)
                    ->firstWhere('path', '/kontakt')['issues'], true)));
    });
});

describe('editing', function () {
    it('writes a page title back to the content block it came from', function () {
        $this->actingAs($this->admin)
            ->post('/admin/seo', [
                'path' => '/kontakt',
                'source' => 'content',
                'reference' => 'kontakt.kopf',
                'title' => 'Kontakt zur DKGZ | DKGZ',
                'description' => 'So erreichen Sie uns.',
                'indexed' => true,
            ])
            ->assertSessionHasNoErrors();

        expect(ContentBlock::where('page_key', 'kontakt')->where('field_key', 'meta_titel')->value('value'))
            ->toBe('Kontakt zur DKGZ | DKGZ');
    });

    it('writes a city title back to the city', function () {
        $city = City::create(['name' => 'Köln', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->post('/admin/seo', [
                'path' => '/kfz-gutachter/koeln',
                'source' => 'city',
                'reference' => (string) $city->id,
                'title' => 'Kfz-Gutachter Köln',
                'description' => 'Sachverständige in Köln.',
                'indexed' => true,
            ])
            ->assertSessionHasNoErrors();

        expect($city->fresh()->meta_title)->toBe('Kfz-Gutachter Köln');
    });

    it('writes an article title back to the article', function () {
        $post = Post::factory()->published()->create(['title' => 'Wer zahlt?']);

        $this->actingAs($this->admin)
            ->post('/admin/seo', [
                'path' => "/ratgeber/{$post->slug}",
                'source' => 'post',
                'reference' => (string) $post->id,
                'title' => 'Wer zahlt das Gutachten? | DKGZ',
                'description' => 'Die Antwort.',
                'indexed' => true,
            ])
            ->assertSessionHasNoErrors();

        expect($post->fresh()->meta_title)->toBe('Wer zahlt das Gutachten? | DKGZ');
    });

    it('refuses somebody who may read content but not change it', function () {
        $viewer = User::factory()->create();
        $viewer->assignRole('support');

        $this->actingAs($viewer)
            ->post('/admin/seo', ['path' => '/kontakt', 'source' => 'none'])
            ->assertForbidden();
    });
});

describe('switching a page out of the index', function () {
    it('records the decision', function () {
        $this->actingAs($this->admin)
            ->post('/admin/seo', ['path' => '/kontakt', 'source' => 'none', 'indexed' => false])
            ->assertSessionHasNoErrors();

        expect(SeoSetting::indexes('/kontakt'))->toBeFalse()
            ->and(SeoSetting::indexes('/faq'))->toBeTrue();
    });

    it('tells the crawler in the page itself, without waiting for JavaScript', function () {
        SeoSetting::set('/kontakt', false);

        expect($this->get('/kontakt')->getContent())->toContain('name="robots" content="noindex, follow"');
        expect($this->get('/faq')->getContent())->not->toContain('noindex');
    });

    it('takes the page out of the sitemap as well', function () {
        // Asking a crawler to fetch something and then telling it to forget
        // what it found wastes the budget the sitemap exists to direct.
        expect($this->get('/sitemap.xml')->getContent())->toContain('/kontakt');

        SeoSetting::set('/kontakt', false);

        expect($this->get('/sitemap.xml')->getContent())->not->toContain('/kontakt');
    });

    it('lets a page back in again', function () {
        SeoSetting::set('/kontakt', false);
        SeoSetting::set('/kontakt', true);

        expect(SeoSetting::indexes('/kontakt'))->toBeTrue();
    });
});
