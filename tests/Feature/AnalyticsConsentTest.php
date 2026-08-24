<?php

use App\Support\Settings;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * Google's own snippet goes into <head> and starts reporting before the visitor
 * has been asked anything. Under the TDDDG and the GDPR that is precisely what
 * the consent banner exists to prevent, so the tag is loaded from the client
 * only after the banner has been answered — and the policy that would otherwise
 * block Google opens only where an operator has actually configured tracking.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(Database\Seeders\ContentBlockSeeder::class);
});

it('sends no Google tag in the document itself', function () {
    Settings::setMany(['integrations.analytics_id' => 'G-MQ2T5QTKWD']);

    $html = $this->get('/')->assertOk()->getContent();

    // The identifier reaches the page as a prop; the script does not.
    expect($html)->not->toContain('<script async src="https://www.googletagmanager.com')
        ->and($html)->not->toContain("gtag('config'");
});

it('hands the property to the page so the banner can start it', function () {
    Settings::setMany(['integrations.analytics_id' => 'G-MQ2T5QTKWD']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('app.analytics_id', 'G-MQ2T5QTKWD')
            ->where('app.analytics_configured', true));
});

it('shows no banner and names no property when tracking is not configured', function () {
    Settings::setMany(['integrations.analytics_id' => null]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('app.analytics_id', null)
            ->where('app.analytics_configured', false));
});

it('opens the policy to Google only where tracking is configured', function () {
    Settings::setMany(['integrations.analytics_id' => 'G-MQ2T5QTKWD']);

    $policy = $this->get('/')->headers->get('Content-Security-Policy');

    expect($policy)->toContain('https://www.googletagmanager.com')
        ->and($policy)->toContain('https://www.google-analytics.com');
});

it('keeps the policy closed when tracking is not configured', function () {
    Settings::setMany(['integrations.analytics_id' => null]);

    $policy = $this->get('/')->headers->get('Content-Security-Policy');

    expect($policy)->not->toContain('googletagmanager')
        ->and($policy)->not->toContain('google-analytics')
        ->and($policy)->toContain("script-src 'self'");
});

it('loads nothing until consent, and remembers a refusal', function () {
    // The client-side contract, asserted against the source: the loader is only
    // ever called from the stored acceptance or the banner's event.
    $source = file_get_contents(resource_path('js/Support/analytics.js'));

    expect($source)->toContain("storedConsent() === 'accepted'")
        ->and($source)->toContain('dkgz:analytics-consent')
        ->and($source)->toContain('{ once: true }');
});

it('carries the banner wording from the admin panel', function () {
    Settings::setMany(['integrations.analytics_id' => 'G-MQ2T5QTKWD']);

    App\Models\ContentBlock::where('page_key', 'cookies')
        ->where('field_key', 'titel')
        ->update(['value' => 'Unsere Cookies']);

    App\Support\Content::flush('cookies');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('app.cookie_notice.titel', 'Unsere Cookies'));
});

it('puts the Search Console token in the document', function () {
    Settings::setMany(['integrations.google_site_verification' => 'ABC123token']);

    $this->get('/')
        ->assertOk()
        ->assertSee('name="google-site-verification"', false)
        ->assertSee('ABC123token', false);
});

it('emits no verification tag when none is configured', function () {
    Settings::setMany(['integrations.google_site_verification' => null]);

    $this->get('/')->assertOk()->assertDontSee('google-site-verification', false);
});

describe('the Google Ads tag', function () {
    // Ads sets cookies for advertising, which needs consent at least as clearly
    // as measurement does — so it goes through the same gate, and shares one
    // gtag.js with Analytics because loading it twice doubles every event.
    it('reaches the page alongside the analytics property', function () {
        Settings::setMany([
            'integrations.analytics_id' => 'G-MQ2T5QTKWD',
            'integrations.google_ads_id' => 'AW-11007240787',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('app.analytics_id', 'G-MQ2T5QTKWD')
                ->where('app.google_ads_id', 'AW-11007240787'));
    });

    it('shows the banner when only the Ads tag is configured', function () {
        Settings::setMany([
            'integrations.analytics_id' => null,
            'integrations.google_ads_id' => 'AW-11007240787',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('app.analytics_configured', true));
    });

    it('opens the policy to the Ads endpoints as well', function () {
        Settings::setMany(['integrations.google_ads_id' => 'AW-11007240787']);

        $policy = $this->get('/')->headers->get('Content-Security-Policy');

        expect($policy)->toContain('https://www.googleadservices.com')
            ->and($policy)->toContain('https://googleads.g.doubleclick.net');
    });

    it('sends neither tag in the document itself', function () {
        Settings::setMany([
            'integrations.analytics_id' => 'G-MQ2T5QTKWD',
            'integrations.google_ads_id' => 'AW-11007240787',
        ]);

        $html = $this->get('/')->getContent();

        expect($html)->not->toContain('<script async src="https://www.googletagmanager.com')
            ->and($html)->not->toContain("gtag('config'");
    });

    it('loads gtag.js once for both properties', function () {
        $source = file_get_contents(resource_path('js/Support/analytics.js'));

        // One script tag, requested with the first id, then every id configured
        // on top of it.
        expect(substr_count($source, 'googletagmanager.com/gtag/js'))->toBe(1)
            ->and($source)->toContain('for (const id of ids)');
    });
});
