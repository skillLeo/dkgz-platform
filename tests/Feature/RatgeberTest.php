<?php

use App\Models\Post;
use App\Models\User;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * The Ratgeber.
 *
 * Every other page on this site answers somebody who has already decided they
 * need an assessor. These articles are for the person a week earlier, typing a
 * question into Google that DKGZ can answer properly and nobody else on the
 * site is answering at all.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);

    $this->editor = User::factory()->create();
    $this->editor->assignRole('content_editor');
});

describe('what a reader sees', function () {
    it('lists the published articles newest first', function () {
        Post::factory()->published()->create(['title' => 'Älterer Beitrag', 'published_at' => now()->subMonth()]);
        Post::factory()->published()->create(['title' => 'Neuerer Beitrag', 'published_at' => now()->subDay()]);

        $this->get('/ratgeber')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('posts', 2)
                ->where('posts.0.title', 'Neuerer Beitrag'));
    });

    it('keeps a draft off the listing and off its own address', function () {
        $draft = Post::factory()->create(['title' => 'Noch nicht fertig']);

        $this->get('/ratgeber')->assertInertia(fn ($page) => $page->has('posts', 0));
        $this->get("/ratgeber/{$draft->slug}")->assertNotFound();
    });

    it('holds a scheduled article back until its date', function () {
        // Ticking the box a week early is writing ahead, not publishing.
        $post = Post::factory()->scheduled()->create(['title' => 'Erscheint nächste Woche']);

        $this->get('/ratgeber')->assertInertia(fn ($page) => $page->has('posts', 0));
        $this->get("/ratgeber/{$post->slug}")->assertNotFound();

        $post->update(['published_at' => now()->subMinute()]);

        $this->get('/ratgeber')->assertInertia(fn ($page) => $page->has('posts', 1));
        $this->get("/ratgeber/{$post->slug}")->assertOk();
    });

    it('offers something to read next rather than being a dead end', function () {
        Post::factory()->published()->create(['title' => 'Der gelesene Beitrag']);
        Post::factory()->published()->create(['title' => 'Ein anderer Beitrag']);

        $this->get('/ratgeber/der-gelesene-beitrag')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('post.title', 'Der gelesene Beitrag')
                ->has('more', 1)
                ->where('more.0.title', 'Ein anderer Beitrag'));
    });

    it('offers only the categories something is filed under', function () {
        Post::factory()->published()->create(['title' => 'Eins', 'category' => 'Unfall und Schaden']);
        Post::factory()->published()->create(['title' => 'Zwei', 'category' => 'Fahrzeugwert']);
        Post::factory()->create(['title' => 'Entwurf', 'category' => 'Versicherung und Recht']);

        $this->get('/ratgeber')
            ->assertInertia(fn ($page) => $page
                ->where('categories', ['Fahrzeugwert', 'Unfall und Schaden']));
    });
});

describe('the address of an article', function () {
    it('follows the title, with umlauts spelled out', function () {
        // "pruefen" is what people type and what Google expects; "prufen" is
        // neither, and Str::slug produces the second one on its own.
        $post = Post::factory()->create(['title' => 'Fahrzeugwert prüfen — die Übersicht']);

        expect($post->slug)->toBe('fahrzeugwert-pruefen-die-uebersicht');
    });

    it('never repeats itself', function () {
        Post::factory()->create(['title' => 'Gutachten verstehen']);
        $second = Post::factory()->create(['title' => 'Gutachten verstehen']);

        expect($second->slug)->toBe('gutachten-verstehen-2');
    });

    it('stops following the title once the article is published', function () {
        // An address that has been shared or indexed is a promise.
        $post = Post::factory()->published()->create(['title' => 'Der ursprüngliche Titel']);
        $slug = $post->slug;

        $post->update(['title' => 'Ein besserer Titel']);

        expect($post->fresh()->slug)->toBe($slug);
    });

    it('still follows the title while it is a draft', function () {
        $post = Post::factory()->create(['title' => 'Erster Versuch']);

        $post->update(['title' => 'Zweiter Versuch']);

        expect($post->fresh()->slug)->toBe('zweiter-versuch');
    });

    it('cannot collide with a page that already exists', function () {
        // Under a fixed prefix, so an article titled "Kontakt" takes
        // /ratgeber/kontakt and never the contact page's address.
        Post::factory()->published()->create(['title' => 'Kontakt']);

        $this->get('/kontakt')->assertOk()->assertInertia(fn ($page) => $page->component('Public/Kontakt'));
        $this->get('/ratgeber/kontakt')->assertOk()->assertInertia(fn ($page) => $page->component('Public/RatgeberBeitrag'));
    });
});

describe('writing one', function () {
    it('needs a title and nothing else', function () {
        $this->actingAs($this->editor)
            ->post('/admin/ratgeber', ['title' => 'Ein neuer Beitrag'])
            ->assertSessionHasNoErrors();

        expect(Post::firstWhere('title', 'Ein neuer Beitrag'))->not->toBeNull();
    });

    it('dates an article the moment it is published, unless told otherwise', function () {
        $this->actingAs($this->editor)
            ->post('/admin/ratgeber', ['title' => 'Sofort sichtbar', 'is_published' => true])
            ->assertSessionHasNoErrors();

        expect(Post::firstWhere('title', 'Sofort sichtbar')->published_at)->not->toBeNull();
    });

    it('refuses a category that is not one of the fixed set', function () {
        $this->actingAs($this->editor)
            ->post('/admin/ratgeber', ['title' => 'Beitrag', 'category' => 'Erfunden'])
            ->assertSessionHasErrors('category');
    });

    it('falls back to the start of the article when no excerpt is written', function () {
        $post = Post::factory()->create([
            'title' => 'Ohne Anriss',
            'excerpt' => null,
            'body' => '<p>Der erste Absatz steht hier und muss die Übersicht füllen.</p>',
        ]);

        expect($post->summary())->toBe('Der erste Absatz steht hier und muss die Übersicht füllen.');
    });

    it('rounds the reading time up rather than down', function () {
        // Four and a half minutes shown as four has misled the one person who
        // was counting.
        $post = Post::factory()->create(['body' => str_repeat('Wort ', 210)]);

        expect($post->readingMinutes())->toBe(2);
    });

    it('says an empty article takes a minute rather than none', function () {
        expect(Post::factory()->create(['body' => null])->readingMinutes())->toBe(1);
    });
});

describe('who may write one', function () {
    it('lets the editor in', function () {
        $this->actingAs($this->editor)->get('/admin/ratgeber')->assertOk();
    });

    it('keeps everybody else out', function () {
        $support = User::factory()->create();
        $support->assignRole('support');

        $this->actingAs($support)->get('/admin/ratgeber')->assertForbidden();
        $this->actingAs($support)->post('/admin/ratgeber', ['title' => 'Nein'])->assertForbidden();
    });

    it('asks a stranger to sign in', function () {
        $this->get('/admin/ratgeber')->assertRedirect('/anmelden');
    });
});

describe('being found', function () {
    it('lists every published article in the sitemap', function () {
        Post::factory()->published()->create(['title' => 'Im Sitemap']);
        Post::factory()->create(['title' => 'Ein Entwurf']);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        expect($xml)->toContain('/ratgeber/im-sitemap')
            ->and($xml)->toContain('/ratgeber<')
            ->and($xml)->not->toContain('ein-entwurf');
    });

    it('carries the article structured data Google wants', function () {
        Post::factory()->published()->create(['title' => 'Mit Auszeichnung']);

        $this->get('/ratgeber/mit-auszeichnung')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('post.published_iso'));
    });

    it('is in the public navigation', function () {
        expect(file_get_contents(resource_path('js/Layouts/PublicLayout.vue')))
            ->toContain("{ href: '/ratgeber', label: 'Ratgeber' }");
    });
});
