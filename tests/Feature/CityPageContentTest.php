<?php

use App\Models\City;
use App\Models\ContentBlock;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * What a city page actually says.
 *
 * "Kfz-Gutachter Köln" is among the most valuable searches DKGZ can win, and
 * the page answering it was a heading and a list of services — thin enough that
 * a search engine has no reason to prefer it to anybody else's. The shared
 * sections carry every city at once; the per-city text is for the part that
 * cannot be templated, which is the part that earns the ranking.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);

    $this->city = City::create(['name' => 'Köln', 'postal_code' => '50667', 'is_active' => true]);
    $this->service = ServiceType::factory()->create(['name_de' => 'Unfallgutachten', 'is_active' => true]);
    $this->city->serviceTypes()->attach($this->service);
});

describe('the shared sections', function () {
    it('carries the steps and the questions for every city', function () {
        $this->get('/kfz-gutachter/koeln')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('content.stadt.schritt_1')
                ->has('content.stadt.schritt_3')
                ->has('content.stadt.faq_1_frage'));
    });

    it('no longer offers wording for the introduction it dropped', function () {
        // Left in the table it would sit in the admin panel offering fields
        // that appear nowhere on the page.
        $gone = ContentBlock::where('page_key', 'staedte')
            ->where('section_key', 'stadt')
            ->whereIn('field_key', ['einleitung_text', 'hinweis_1_titel', 'hinweise_ueberschrift'])
            ->count();

        expect($gone)->toBe(0);
    });

    it('hands the page both the placeholder and the name to fill it with', function () {
        // The substitution happens where the page is drawn, so the props carry
        // the template and the city — the test is that both arrive, not that
        // the server did the filling.
        $this->get('/kfz-gutachter/koeln')
            ->assertInertia(fn ($page) => $page
                ->where('city.name', 'Köln')
                ->where('content.stadt.faq_1_frage', fn ($v) => str_contains($v, '{stadt}')));
    });
});

describe('what one city can say for itself', function () {
    it('shows its own passage where somebody has written one', function () {
        $this->city->update(['body' => '<p>In Köln sitzt das Landgericht am Reichenspergerplatz.</p>']);

        $this->get('/kfz-gutachter/koeln')
            ->assertInertia(fn ($page) => $page->where('city.body', '<p>In Köln sitzt das Landgericht am Reichenspergerplatz.</p>'));
    });

    it('falls back to the shared text where nobody has', function () {
        $this->get('/kfz-gutachter/koeln')
            ->assertInertia(fn ($page) => $page->where('city.body', null));
    });

    it('puts its own questions before the shared ones', function () {
        $this->city->update(['faqs' => [['frage' => 'Gibt es in Köln eine Werkstatt?', 'antwort' => 'Ja, mehrere.']]]);

        $this->get('/kfz-gutachter/koeln')
            ->assertInertia(fn ($page) => $page
                ->where('city.faqs.0.frage', 'Gibt es in Köln eine Werkstatt?'));
    });
});

describe('writing it in the admin panel', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    });

    it('saves the passage and the questions', function () {
        $this->actingAs($this->admin)
            ->post("/admin/staedte/{$this->city->id}", [
                'name' => 'Köln',
                'is_active' => true,
                'body' => '<p>Etwas Wahres über Köln.</p>',
                'faqs' => [['frage' => 'Eine Frage?', 'antwort' => 'Eine Antwort.']],
            ])
            ->assertSessionHasNoErrors();

        $city = $this->city->fresh();

        expect($city->body)->toBe('<p>Etwas Wahres über Köln.</p>')
            ->and($city->faqs[0]['frage'])->toBe('Eine Frage?');
    });

    it('refuses a question with no answer', function () {
        $this->actingAs($this->admin)
            ->post("/admin/staedte/{$this->city->id}", [
                'name' => 'Köln',
                'is_active' => true,
                'faqs' => [['frage' => 'Nur eine Frage?', 'antwort' => '']],
            ])
            ->assertSessionHasErrors('faqs.0.antwort');
    });
});
