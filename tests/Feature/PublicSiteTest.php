<?php

use App\Models\Page;
use App\Models\PostalCode;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ServiceTypeSeeder::class);
    $this->seed(ContentBlockSeeder::class);
    $this->seed(PageSeeder::class);
    $this->seed(FaqSeeder::class);
    PostalCode::create(['code' => '40589', 'city' => 'Düsseldorf', 'state' => 'Nordrhein-Westfalen']);
});

describe('public pages', function () {
    it('renders every public route', function (string $path, string $component) {
        $this->get($path)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component($component));
    })->with([
        ['/', 'Public/Startseite'],
        ['/leistungen', 'Public/Leistungen'],
        ['/ablauf', 'Public/Ablauf'],
        ['/ueber-uns', 'Public/UeberUns'],
        ['/fuer-sachverstaendige', 'Public/FuerSachverstaendige'],
        ['/kontakt', 'Public/Kontakt'],
        ['/anfrage', 'Public/Anfrage'],
        ['/impressum', 'Public/Rechtliches'],
        ['/datenschutz', 'Public/Rechtliches'],
        ['/agb', 'Public/Rechtliches'],
        ['/widerruf', 'Public/Rechtliches'],
    ]);

    it('renders a single service page', function () {
        $type = ServiceType::first();

        $this->get("/leistungen/{$type->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/Leistung')->where('serviceType.slug', $type->slug));
    });

    it('hides an inactive service page', function () {
        $type = ServiceType::first();
        $type->update(['is_active' => false]);

        $this->get("/leistungen/{$type->slug}")->assertNotFound();
    });

    it('hides an unpublished legal page', function () {
        Page::where('slug', 'agb')->update(['is_published' => false]);

        $this->get('/agb')->assertNotFound();
    });

    it('serves the copy from the design rather than placeholder text', function () {
        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('content.hero.eyebrow', 'Deutsche Kfz-Gutachterzentrale')
            ->where('content.hero.zeile_1', 'Kfz-Gutachter finden.')
            ->where('content.hero.zeile_2', 'Bundesweit koordiniert.')
            ->where('content.hero.zeile_3', 'Ohne Umwege.')
            ->where('content.hero.cta', 'Gutachter anfragen'));
    });

    it('shows all eight services and all six questions on the homepage', function () {
        $this->get('/')->assertInertia(fn ($page) => $page
            ->has('serviceTypes', 8)
            ->has('faqs', 6));
    });
});

describe('sitemap and robots', function () {
    it('lists the public routes in the sitemap', function () {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml');
        expect($response->getContent())->toContain('<urlset')->toContain('/anfrage');
    });

    it('hides the sitemap when the toggle is off', function () {
        Settings::set('seo.sitemap_enabled', false);

        $this->get('/sitemap.xml')->assertNotFound();
    });

    it('keeps the portal and admin out of robots.txt', function () {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        expect($response->getContent())->toContain('Disallow: /portal/')->toContain('Disallow: /admin/');
    });
});

describe('the postal code lookup', function () {
    it('resolves a known code to its city', function () {
        $this->getJson('/api/plz/40589')->assertOk()->assertJson(['code' => '40589', 'city' => 'Düsseldorf']);
    });

    it('reports an unknown code in German', function () {
        $this->getJson('/api/plz/99998')
            ->assertNotFound()
            ->assertJson(['message' => 'Diese Postleitzahl kennen wir nicht.']);
    });
});

describe('maintenance mode', function () {
    it('closes the public site but leaves the login reachable', function () {
        Settings::set('features.maintenance_mode', true);

        $this->get('/')->assertStatus(503);
        $this->get('/anmelden')->assertOk();
    });

    it('lets staff through', function () {
        Settings::set('features.maintenance_mode', true);

        $staff = User::factory()->create();
        $staff->assignRole('admin');

        $this->actingAs($staff)->get('/')->assertOk();
    });
});
