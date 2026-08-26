<?php

use App\Models\ContentBlock;
use App\Models\PostalCode;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Database\QueryException;

/**
 * Two questions before the telephone number.
 *
 * The request used to be three steps and the middle one asked for the make,
 * the model, the year, the plate, a description and photographs. Somebody
 * arriving from a paid advert had a screen of work to do before DKGZ had any
 * way of contacting them, and the assessor telephones and asks all of it
 * anyway. What is left is what the matching actually runs on — the service and
 * the postal code — and then who to call.
 */
beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    PostalCode::create(['code' => '40210', 'city' => 'Düsseldorf', 'state' => 'Nordrhein-Westfalen']);
    PostalCode::create(['code' => '50667', 'city' => 'Köln', 'state' => 'Nordrhein-Westfalen']);

    $this->service = ServiceType::factory()->create([
        'name_de' => 'Unfallgutachten',
        'is_active' => true,
        'dkgz_fee_cents' => 8500,
    ]);
});

/** Everything the shortened form actually sends. */
function shortRequest(array $overrides = []): array
{
    return array_merge([
        'service_type_id' => test()->service->id,
        'postal_code' => '40210',
        'customer_name' => 'Martina Reinhardt',
        'customer_phone' => '+49 179 4480169',
        'customer_email' => 'martina@beispiel.de',
    ], $overrides);
}

describe('what the form still asks for', function () {
    it('takes a request with a service, a postal code and three contact fields', function () {
        $this->post('/anfrage', shortRequest())->assertSessionHasNoErrors();

        $request = ServiceRequest::latest('id')->first();

        expect($request->customer_name)->toBe('Martina Reinhardt')
            ->and($request->postal_code)->toBe('40210')
            ->and($request->vehicle_make)->toBeNull()
            ->and($request->vehicle_model)->toBeNull();
    });

    it('fills the town in from the postal code rather than asking for it', function () {
        // Asking for both invited them to disagree, and the code is the one the
        // matching runs on.
        $this->post('/anfrage', shortRequest())->assertSessionHasNoErrors();

        expect(ServiceRequest::latest('id')->first()->city)->toBe('Düsseldorf');
    });

    it('still records when consent was given, without a tick box', function () {
        $this->post('/anfrage', shortRequest())->assertSessionHasNoErrors();

        expect(ServiceRequest::latest('id')->first()->consent_at)->not->toBeNull();
    });

    it('refuses a postal code that is not a real one', function () {
        // A typo here becomes a request no assessor covers and a customer who
        // never hears anything back.
        $this->post('/anfrage', shortRequest(['postal_code' => '00000']))
            ->assertSessionHasErrors('postal_code');

        expect(ServiceRequest::count())->toBe(0);
    });

    it('still insists on the name, the telephone number and the address', function () {
        foreach (['customer_name', 'customer_phone', 'customer_email'] as $field) {
            $this->post('/anfrage', shortRequest([$field => '']))
                ->assertSessionHasErrors($field);
        }
    });

    it('lets the office add the vehicle later without the form asking for it', function () {
        $this->post('/anfrage', shortRequest())->assertSessionHasNoErrors();

        $request = ServiceRequest::latest('id')->first();

        expect($request->vehicleLabel())->toBe('noch nicht angegeben');

        $request->update(['vehicle_make' => 'VW', 'vehicle_model' => 'Passat B8']);

        expect($request->fresh()->vehicleLabel())->toBe('VW Passat B8');
    });
});

describe('arriving with the first step already answered', function () {
    it('carries the service and the town across from the homepage', function () {
        $this->get("/anfrage?leistung={$this->service->slug}&plz=40210")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('selected.service_type_id', $this->service->id)
                ->where('selected.postal_code', '40210')
                ->where('selected.city', 'Düsseldorf'));
    });

    it('asks the first step when nothing was answered', function () {
        $this->get('/anfrage')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('selected', []));
    });

    it('ignores a postal code it cannot place rather than guessing', function () {
        $this->get("/anfrage?leistung={$this->service->slug}&plz=00000")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('selected.service_type_id', $this->service->id)
                ->missing('selected.city'));
    });

    it('ignores a service that is not active', function () {
        $hidden = ServiceType::factory()->create(['name_de' => 'Beweissicherung', 'is_active' => false]);

        $this->get("/anfrage?leistung={$hidden->slug}&plz=40210")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->missing('selected.service_type_id'));
    });
});

describe('the postal code table behind it', function () {
    it('answers with the town for a code it knows', function () {
        $this->getJson('/api/plz/50667')
            ->assertOk()
            ->assertJson(['code' => '50667', 'city' => 'Köln', 'known' => true]);
    });

    it('says so plainly for a code it does not', function () {
        $this->getJson('/api/plz/00000')
            ->assertOk()
            ->assertJson(['city' => null, 'known' => false]);
    });

    it('holds each code once', function () {
        expect(fn () => PostalCode::create([
            'code' => '40210', 'city' => 'Anderswo', 'state' => 'Nordrhein-Westfalen',
        ]))->toThrow(QueryException::class);
    });
});

describe('the pages that start it', function () {
    it('gives the homepage the services its first question needs', function () {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('serviceTypes.0.name_de', 'Unfallgutachten')
                ->has('serviceTypes.0.description_de'));
    });

    it('asks the same two questions from one file on both pages', function () {
        // Somebody who started at the top and somebody who clicked through from
        // a service page have to meet the same thing.
        foreach (['Pages/Public/Startseite.vue', 'Pages/Public/Anfrage.vue'] as $page) {
            expect(file_get_contents(resource_path("js/{$page}")))
                ->toContain('RequestStarter');
        }
    });

    it('no longer offers the fields it stopped asking for', function () {
        // Only what the form binds, so the comment explaining what went is not
        // mistaken for the thing itself.
        $source = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

        foreach ([
            'vehicle_make', 'vehicle_model', 'vehicle_year', 'vehicle_plate',
            'form.description', 'form.urgency', 'form.images', 'BaseFileUpload', 'BaseCheckbox',
        ] as $gone) {
            expect($source)->not->toContain($gone);
        }
    });
});

describe('copy the operator had already rewritten', function () {
    /** Runs one migration by hand against wording set up for it. */
    function runRequestCopyMigration(): void
    {
        (require database_path('migrations/2026_08_26_093000_the_second_step_describes_itself.php'))->up();
    }

    function requestBlock(string $field): ?ContentBlock
    {
        return ContentBlock::where('page_key', 'anfrage')
            ->where('section_key', 'formular')
            ->where('field_key', $field)
            ->first();
    }

    it('stops the second step describing the car', function () {
        // Their wording, from the live site — kept by the migration that only
        // rewrites untouched copy, and now above a name and a telephone number.
        requestBlock('schritt_2_text')?->update([
            'value' => 'Geben Sie die wichtigsten Fahrzeugdaten an. Fotos und eine kurze Beschreibung helfen bei der ersten Einschätzung.',
        ]);

        runRequestCopyMigration();

        expect(requestBlock('schritt_2_text')->value)
            ->not->toContain('Fahrzeugdaten')
            ->toContain('vervollständigen');
    });

    it('puts the consent in front of their reassurance rather than over it', function () {
        requestBlock('datenschutzhinweis')?->update([
            'value' => 'Die Anfrage und Vermittlung sind für Sie kostenfrei und unverbindlich.',
        ]);

        runRequestCopyMigration();

        expect(requestBlock('datenschutzhinweis')->value)
            ->toStartWith('Mit dem Absenden willigen Sie ein')
            ->toContain('kostenfrei und unverbindlich');
    });

    it('leaves a line alone that already says it', function () {
        $already = 'Mit dem Absenden willigen Sie ein. Sonst nichts.';

        requestBlock('datenschutzhinweis')?->update(['value' => $already]);

        runRequestCopyMigration();

        expect(requestBlock('datenschutzhinweis')->value)->toBe($already);
    });
});

describe('the box in the hero', function () {
    it('shows its button from the start rather than growing one', function () {
        // A box with no visible destination gives the eye nowhere to land while
        // somebody is still deciding whether to bother.
        $source = file_get_contents(resource_path('js/Components/Domain/RequestStarter.vue'));

        expect($source)->toContain(':disabled="! ready"');

        // No v-if on the button: it is inert, not absent.
        expect($source)->not->toMatch('/<BaseButton\b[^>]*\sv-if=/s');
    });

    it('says what it is before it asks anything', function () {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('content.hero.cta', 'Jetzt Gutachter anfragen')
                ->where('content.hero.cta_button', 'Weiter'));
    });

    it('lets the operator rename the heading and the button separately', function () {
        foreach (['cta', 'cta_button', 'cta_hinweis', 'telefon_titel'] as $field) {
            expect(ContentBlock::where('page_key', 'startseite')
                ->where('section_key', 'hero')
                ->where('field_key', $field)
                ->exists())->toBeTrue("startseite.hero.{$field} fehlt");
        }
    });
});
