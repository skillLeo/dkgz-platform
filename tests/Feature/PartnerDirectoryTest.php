<?php

use App\Models\Assessor;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * The public directory, and the request that goes to one partner only.
 *
 * A page per partner is the only part of the site that names the firms
 * themselves. It works as a way in rather than a way around only if it carries
 * no telephone number and no e-mail address — a listed partner is asked for
 * work through the platform and reached no other way.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    $this->service = ServiceType::factory()->create([
        'name_de' => 'Unfallgutachten', 'is_active' => true, 'dkgz_fee_cents' => 8500,
    ]);

    $this->assessor = makeListedAssessor('Gutachterbüro Sander', '40210', 'Düsseldorf', [$this->service->id]);
});

function makeListedAssessor(string $name, string $postal, string $city, array $serviceIds): Assessor
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('assessor');

    $assessor = Assessor::factory()->create([
        'user_id' => $user->id,
        'company_name' => $name,
        'postal_code' => $postal,
        'city' => $city,
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_available' => true,
        'is_listed' => true,
    ]);

    $assessor->serviceTypes()->sync($serviceIds);

    return $assessor;
}

describe('the directory', function () {
    it('lists an approved partner', function () {
        $this->get('/sachverstaendige')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('assessors.data', 1)
                ->where('assessors.data.0.name', 'Gutachterbüro Sander'));
    });

    it('leaves out one who is not approved', function () {
        $this->assessor->update(['approval_status' => Assessor::STATUS_PENDING]);

        $this->get('/sachverstaendige')->assertInertia(fn ($page) => $page->has('assessors.data', 0));
        $this->get("/sachverstaendige/{$this->assessor->slug}")->assertNotFound();
    });

    it('leaves out one who has asked not to be listed', function () {
        $this->assessor->update(['is_listed' => false]);

        $this->get('/sachverstaendige')->assertInertia(fn ($page) => $page->has('assessors.data', 0));
        $this->get("/sachverstaendige/{$this->assessor->slug}")->assertNotFound();
    });

    it('keeps listing one who is merely unavailable this fortnight', function () {
        // A page that disappears and comes back takes its ranking with it.
        $this->assessor->update(['is_available' => false]);

        $this->get('/sachverstaendige')->assertInertia(fn ($page) => $page->has('assessors.data', 1));
    });

    it('filters by postal region', function () {
        makeListedAssessor('Kfz-Prüfstelle Süd', '80331', 'München', [$this->service->id]);

        $this->get('/sachverstaendige?region=8')
            ->assertInertia(fn ($page) => $page
                ->has('assessors.data', 1)
                ->where('assessors.data.0.name', 'Kfz-Prüfstelle Süd'));
    });

    it('filters by service', function () {
        $other = ServiceType::factory()->create(['name_de' => 'Wertgutachten', 'is_active' => true]);
        makeListedAssessor('Nur Wertgutachten', '10115', 'Berlin', [$other->id]);

        $this->get("/sachverstaendige?leistung={$other->slug}")
            ->assertInertia(fn ($page) => $page
                ->has('assessors.data', 1)
                ->where('assessors.data.0.name', 'Nur Wertgutachten'));
    });

    it('gives every listed partner a page in the sitemap', function () {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        expect($xml)->toContain('/sachverstaendige/'.$this->assessor->slug);
    });
});

describe('what a profile may not say', function () {
    it('carries no telephone number, e-mail address or street of the partner', function () {
        $this->assessor->update(['street' => 'Musterstraße', 'house_number' => '7']);

        $props = $this->get("/sachverstaendige/{$this->assessor->slug}")
            ->assertOk()
            ->viewData('page')['props'];

        $json = json_encode($props, JSON_UNESCAPED_UNICODE);

        expect($json)->not->toContain($this->assessor->user->email)
            ->and($json)->not->toContain('Musterstraße')
            ->and($json)->not->toContain('vat_id');
    });

    it('shows the name, region and services instead', function () {
        $this->get("/sachverstaendige/{$this->assessor->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('assessor.name', 'Gutachterbüro Sander')
                ->where('assessor.region', '40210 Düsseldorf')
                ->where('assessor.services.0.name', 'Unfallgutachten'));
    });

    it('offers only the services that partner actually does', function () {
        ServiceType::factory()->create(['name_de' => 'Wertgutachten', 'is_active' => true]);

        $this->get("/sachverstaendige/{$this->assessor->slug}")
            ->assertInertia(fn ($page) => $page->has('requestServiceTypes', 1));
    });
});

describe('a request aimed at one partner', function () {
    it('reaches that partner and nobody else', function () {
        // A second partner who would otherwise match on area and service.
        $other = makeListedAssessor('Auch Düsseldorf', '40210', 'Düsseldorf', [$this->service->id]);

        $this->post('/anfrage', [
            'service_type_id' => $this->service->id,
            'requested_assessor_id' => $this->assessor->id,
            'customer_name' => 'Martina Reinhardt',
            'customer_email' => 'martina@beispiel.de',
            'customer_phone' => '+49 179 4480169',
        ])->assertSessionHasNoErrors();

        $request = ServiceRequest::latest('id')->first();

        expect($request->requested_assessor_id)->toBe($this->assessor->id)
            ->and($request->matched_count)->toBe(1);

        $reached = RequestMatch::where('service_request_id', $request->id)->pluck('assessor_id');

        expect($reached->all())->toBe([$this->assessor->id])
            ->and($reached)->not->toContain($other->id);
    });

    it('does not ask for a postal code, because there is nothing to match', function () {
        $this->post('/anfrage', [
            'service_type_id' => $this->service->id,
            'requested_assessor_id' => $this->assessor->id,
            'customer_name' => 'Martina Reinhardt',
            'customer_email' => 'martina@beispiel.de',
            'customer_phone' => '+49 179 4480169',
        ])->assertSessionHasNoErrors();

        expect(ServiceRequest::latest('id')->first()->postal_code)->toBeNull();
    });

    it('still asks for one on every other route', function () {
        $this->post('/anfrage', [
            'service_type_id' => $this->service->id,
            'customer_name' => 'Martina Reinhardt',
            'customer_email' => 'martina@beispiel.de',
            'customer_phone' => '+49 179 4480169',
        ])->assertSessionHasErrors('postal_code');
    });

    it('refuses to be aimed at a partner who is not listed', function () {
        $this->assessor->update(['is_listed' => false]);

        $this->post('/anfrage', [
            'service_type_id' => $this->service->id,
            'requested_assessor_id' => $this->assessor->id,
            'customer_name' => 'Martina Reinhardt',
            'customer_email' => 'martina@beispiel.de',
            'customer_phone' => '+49 179 4480169',
        ])->assertSessionHasErrors('requested_assessor_id');
    });

    it('reaches nobody when that partner does not offer the service', function () {
        $other = ServiceType::factory()->create(['name_de' => 'Wertgutachten', 'is_active' => true, 'dkgz_fee_cents' => 8500]);

        $this->post('/anfrage', [
            'service_type_id' => $other->id,
            'requested_assessor_id' => $this->assessor->id,
            'customer_name' => 'Martina Reinhardt',
            'customer_email' => 'martina@beispiel.de',
            'customer_phone' => '+49 179 4480169',
        ])->assertSessionHasNoErrors();

        // Not handed to somebody else instead: they chose this assessor.
        expect(ServiceRequest::latest('id')->first()->matched_count)->toBe(0);
    });
});

describe('who decides whether a partner is listed', function () {
    it('lets the office switch it off', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post("/admin/sachverstaendige/{$this->assessor->id}", [
                'is_listed' => false,
                'public_profile' => 'Seit 1998 im Rheinland.',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $this->assessor->fresh();

        expect($fresh->is_listed)->toBeFalse()
            ->and($fresh->public_profile)->toBe('Seit 1998 im Rheinland.');
    });
});

describe('the address of a profile', function () {
    it('follows the trading name with umlauts spelled out', function () {
        $assessor = makeListedAssessor('Sachverständigenbüro Süd', '80331', 'München', [$this->service->id]);

        expect($assessor->slug)->toBe('sachverstaendigenbuero-sued');
    });

    it('stops following once the partner is approved', function () {
        // The page can have been linked to by then.
        $slug = $this->assessor->slug;

        $this->assessor->update(['company_name' => 'Ganz anderer Name']);

        expect($this->assessor->fresh()->slug)->toBe($slug);
    });
});
