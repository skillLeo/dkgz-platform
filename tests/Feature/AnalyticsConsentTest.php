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
