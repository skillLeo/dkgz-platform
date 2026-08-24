<?php

use App\Models\City;
use App\Models\ServiceType;
use App\Support\Settings;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * What a crawler is told about the site.
 *
 * There was a static public/robots.txt as well as this route, and the web
 * server answered with the file — so the careful disallow list and the pointer
 * to the sitemap never reached a single crawler, and /admin was on offer to
 * anyone who looked.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
});

it('points crawlers at the sitemap', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Sitemap: '.route('sitemap'), false);
});

it('keeps the private areas out of the index', function () {
    $body = $this->get('/robots.txt')->assertOk()->getContent();

    foreach (['/admin/', '/portal/', '/bewertung/', '/auftrag-angebot/'] as $path) {
        expect($body)->toContain("Disallow: {$path}");
    }
});

it('is served by the application rather than a file beside it', function () {
    // A static file would win over the route and silently undo all of this.
    expect(file_exists(public_path('robots.txt')))->toBeFalse();
});

it('closes the whole site when the operator asks for noindex', function () {
    Settings::setMany(['seo.robots' => 'noindex, nofollow']);

    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /', false)
        ->assertDontSee('Sitemap:', false);
});

it('omits the sitemap line when the sitemap is switched off', function () {
    Settings::setMany(['seo.sitemap_enabled' => false]);

    $this->get('/robots.txt')->assertOk()->assertDontSee('Sitemap:', false);
    $this->get('/sitemap.xml')->assertNotFound();
});

it('lists the public pages in the sitemap', function () {
    $service = ServiceType::factory()->create(['is_active' => true]);
    $city = City::create(['name' => 'Hamburg', 'is_active' => true]);
    $city->serviceTypes()->sync([$service->id]);

    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('<urlset')
        ->and($xml)->toContain(route('home'))
        ->and($xml)->toContain('/kfz-gutachter/hamburg')
        ->and($xml)->toContain("/kfz-gutachter/hamburg/{$service->slug}");
});

it('serves the sitemap as XML', function () {
    expect($this->get('/sitemap.xml')->headers->get('Content-Type'))
        ->toContain('application/xml');
});
