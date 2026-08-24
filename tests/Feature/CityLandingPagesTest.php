<?php

use App\Models\City;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * SEO landing pages: one hub per city, one page per service offered there, with
 * addresses built from the names. These exist to be found, so what matters is
 * that the URLs are stable, that a page never appears with nothing on it, and
 * that a search engine can reach every one of them.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->service = ServiceType::factory()->create([
        'name_de' => 'Unfallgutachten',
        'is_active' => true,
    ]);

    $this->city = City::create([
        'name' => 'Düsseldorf',
        'state' => 'Nordrhein-Westfalen',
        'postal_code' => '40213',
        'is_active' => true,
    ]);

    $this->city->serviceTypes()->sync([$this->service->id]);
});

describe('the addresses', function () {
    it('builds them from the names, with umlauts spelled out', function () {
        expect($this->city->slug)->toBe('duesseldorf');

        $this->get('/kfz-gutachter/duesseldorf')->assertOk();
        $this->get('/kfz-gutachter/duesseldorf/unfallgutachten')->assertOk();
    });

    it('follows a rename', function () {
        $this->city->update(['name' => 'Köln']);

        $this->get('/kfz-gutachter/koeln')->assertOk();
    });

    it('never collides with a page that already exists', function () {
        City::create(['name' => 'Anfrage', 'is_active' => true])
            ->serviceTypes()->sync([$this->service->id]);

        // The prefix keeps them apart: the request form is untouched.
        $this->get('/anfrage')->assertOk();
        $this->get('/kfz-gutachter/anfrage')->assertOk();
    });
});

describe('what is published', function () {
    it('hides a city that is switched off', function () {
        $this->city->update(['is_active' => false]);

        $this->get('/kfz-gutachter/duesseldorf')->assertNotFound();
        $this->get('/kfz-gutachter/duesseldorf/unfallgutachten')->assertNotFound();
    });

    it('refuses a city offering nothing rather than showing an empty page', function () {
        $this->city->serviceTypes()->sync([]);

        $this->get('/kfz-gutachter/duesseldorf')->assertNotFound();
    });

    it('refuses a service that is not offered in that city', function () {
        $other = ServiceType::factory()->create(['name_de' => 'Wertgutachten', 'is_active' => true]);

        $this->get("/kfz-gutachter/duesseldorf/{$other->slug}")->assertNotFound();
    });

    it('refuses a service that has been deactivated altogether', function () {
        $this->service->update(['is_active' => false]);

        $this->get('/kfz-gutachter/duesseldorf/unfallgutachten')->assertNotFound();
    });
});

describe('the page itself', function () {
    it('names the service and the city in the title', function () {
        $this->get('/kfz-gutachter/duesseldorf/unfallgutachten')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('city.name', 'Düsseldorf')
                ->where('serviceType.name_de', 'Unfallgutachten'));
    });

    it('links onward rather than being a dead end', function () {
        $second = ServiceType::factory()->create(['name_de' => 'Wertgutachten', 'is_active' => true]);
        $this->city->serviceTypes()->attach($second->id);

        $koeln = City::create(['name' => 'Köln', 'is_active' => true]);
        $koeln->serviceTypes()->sync([$this->service->id]);

        $this->get('/kfz-gutachter/duesseldorf/unfallgutachten')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('otherServices', 1)
                ->has('otherCities', 1));
    });
});

describe('being found', function () {
    it('lists every city page in the sitemap', function () {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        expect($xml)->toContain('/kfz-gutachter/duesseldorf')
            ->and($xml)->toContain('/kfz-gutachter/duesseldorf/unfallgutachten');
    });

    it('leaves a hidden city out of the sitemap', function () {
        $this->city->update(['is_active' => false]);

        expect($this->get('/sitemap.xml')->getContent())
            ->not->toContain('/kfz-gutachter/duesseldorf');
    });
});

describe('the admin screen', function () {
    it('creates a city with its services in one go', function () {
        $this->actingAs($this->admin)->post('/admin/staedte', [
            'name' => 'Hamburg',
            'state' => 'Hamburg',
            'postal_code' => '20095',
            'is_active' => true,
            'service_type_ids' => [$this->service->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $hamburg = City::firstWhere('name', 'Hamburg');

        expect($hamburg->slug)->toBe('hamburg')
            ->and($hamburg->serviceTypes)->toHaveCount(1);

        $this->get('/kfz-gutachter/hamburg/unfallgutachten')->assertOk();
    });

    it('keeps the screen behind its own permission', function () {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get('/admin/staedte')->assertForbidden();
        $this->actingAs($outsider)->post('/admin/staedte', ['name' => 'X'])->assertForbidden();
    });

    it('rejects a postal code that is not five digits', function () {
        $this->actingAs($this->admin)
            ->post('/admin/staedte', ['name' => 'Bremen', 'postal_code' => '281'])
            ->assertSessionHasErrors('postal_code');
    });
});
