<?php

use App\Models\ContentBlock;
use App\Models\Setting;
use App\Support\Branding;
use App\Support\Content;
use App\Support\Settings;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\SettingsSeeder;

describe('settings', function () {
    beforeEach(fn () => $this->seed(SettingsSeeder::class));

    it('reads a seeded value with its declared type', function () {
        expect(Settings::float('business.commission_rate'))->toBe(15.00)
            ->and(Settings::int('business.review_min_rating'))->toBe(8)
            ->and(Settings::bool('features.review_flow'))->toBeTrue()
            ->and(Settings::bool('features.maintenance_mode'))->toBeFalse();
    });

    it('falls back to the default when a key is unset', function () {
        expect(Settings::get('business.review_redirect_url', 'kein-ziel'))->toBe('kein-ziel');
    });

    it('busts its cache when a value is saved', function () {
        expect(Settings::commissionRate())->toBe(15.00);

        Settings::set('business.commission_rate', '12.5');

        expect(Settings::commissionRate())->toBe(12.5);
    });

    it('encrypts a secret at rest and never stores it in clear', function () {
        Settings::set('integrations.smtp_password', 'geheim123');

        $raw = Setting::where('key', 'integrations.smtp_password')->value('value');

        expect($raw)->not->toContain('geheim123')
            ->and(Settings::get('integrations.smtp_password'))->toBe('geheim123');
    });

    it('leaves a stored secret alone when the field is submitted empty', function () {
        Settings::set('integrations.smtp_password', 'geheim123');

        Settings::setMany([
            'integrations.smtp_password' => '',
            'integrations.smtp_host' => 'smtp.example.de',
        ]);

        expect(Settings::get('integrations.smtp_password'))->toBe('geheim123')
            ->and(Settings::get('integrations.smtp_host'))->toBe('smtp.example.de');
    });

    it('survives a value that cannot be decrypted', function () {
        $setting = Setting::where('key', 'integrations.smtp_password')->first();
        $setting->forceFill(['value' => 'nicht-entschluesselbar'])->save();

        expect($setting->fresh()->rawValue())->toBeNull();
    });
});

describe('content blocks', function () {
    beforeEach(fn () => $this->seed(ContentBlockSeeder::class));

    it('nests a page as section then field', function () {
        $page = Content::page('startseite');

        expect($page)->toHaveKey('hero')
            ->and($page['hero'])->toHaveKey('zeile_1')
            ->and($page['hero']['zeile_1'])->toBe('Kfz-Gutachter finden.');
    });

    it('resolves a dotted key', function () {
        expect(Content::get('anfrage.formular.cta'))->toBe('Anfrage absenden');
    });

    it('returns the default for a key that does not exist', function () {
        expect(Content::get('startseite.gibtsnicht.feld', 'Ersatz'))->toBe('Ersatz');
    });

    it('busts its cache when a block is edited', function () {
        expect(Content::get('startseite.hero.cta'))->toBe('Anfragen');

        ContentBlock::where('page_key', 'startseite')
            ->where('section_key', 'hero')
            ->where('field_key', 'cta')
            ->first()
            ->update(['value' => 'Sachverständigen finden']);

        expect(Content::get('startseite.hero.cta'))->toBe('Sachverständigen finden');
    });

    it('seeds copy for every page the admin editor offers', function () {
        foreach (array_keys(Content::pageKeys()) as $pageKey) {
            expect(Content::page($pageKey))->not->toBeEmpty("Seite {$pageKey} hat keine Inhalte.");
        }
    });
});

describe('branding', function () {
    beforeEach(fn () => $this->seed(SettingsSeeder::class));

    it('emits nothing while every colour is still the default', function () {
        expect(Branding::cssCustomProperties())->toBe('');
    });

    it('emits only the tokens that were actually overridden', function () {
        Settings::set('branding.color_navy_700', '#123456');

        $css = Branding::cssCustomProperties();

        expect($css)->toBe('--color-navy-700:#123456');
    });

    it('refuses anything that is not a literal hex colour', function () {
        Settings::set('branding.color_navy_700', 'red; background:url(javascript:alert(1))');

        expect(Branding::cssCustomProperties())->toBe('');
    });

    it('covers every Foundations colour token', function () {
        expect(Branding::tokens())->toHaveCount(17)
            ->and(array_keys(Branding::tokens()))->toContain('navy_700', 'gray_800', 'accent', 'danger', 'success', 'warning');
    });
});
