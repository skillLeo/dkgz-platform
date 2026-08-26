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
    it('carries the chosen service across from a service page', function () {
        // The postal code is asked on the second step now, beside the contact
        // details, so it does not travel in the address any more.
        $this->get("/anfrage?leistung={$this->service->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('selected.service_type_id', $this->service->id)
                ->missing('selected.postal_code'));
    });

    it('asks the first step when nothing was answered', function () {
        $this->get('/anfrage')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('selected', []));
    });

    it('still opens the contact step when an old link carries a postal code', function () {
        // Links from before the postal code moved are still out there.
        $this->get("/anfrage?leistung={$this->service->slug}&plz=40210")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('selected.service_type_id', $this->service->id));
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

describe('what the shortened flow shows and hides', function () {
    it('asks for the postal code on the second step, not the first', function () {
        $starter = file_get_contents(resource_path('js/Components/Domain/RequestStarter.vue'));
        $form = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

        expect($starter)->not->toContain('postal_code');
        expect($form)->toContain('form.postal_code');
    });

    it('drops the three reassurances from under the homepage box', function () {
        $home = file_get_contents(resource_path('js/Pages/Public/Startseite.vue'));
        $form = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

        expect($home)->not->toContain('TrustRow');
        expect($form)->not->toContain('TrustRow');
    });

    it('keeps the telephone line on the homepage and drops it from the form', function () {
        // An option worth offering where somebody is still deciding; noise on
        // the screen where they are already typing their own number in.
        expect(file_get_contents(resource_path('js/Pages/Public/Startseite.vue')))
            ->toContain("t('hero', 'telefon_titel'");

        expect(file_get_contents(resource_path('js/Pages/Public/Anfrage.vue')))
            ->not->toContain('telefon_titel');
    });

    it('carries a mark in the request header rather than a way out', function () {
        $layout = file_get_contents(resource_path('js/Layouts/RequestFlowLayout.vue'));

        expect($layout)->toContain('ShieldCheck')
            ->and($layout)->not->toContain('canGoBack');
    });

    it('shows the progress bar without spelling out the step number', function () {
        expect(file_get_contents(resource_path('js/Pages/Public/Anfrage.vue')))
            ->toContain('RequestProgress')
            ->and(file_get_contents(resource_path('js/Pages/Public/Anfrage.vue')))
            ->not->toContain('Schritt {{ step }}');
    });

    it('sends somebody straight to the contact step from a service page', function () {
        foreach (['Leistung.vue', 'StadtLeistung.vue'] as $page) {
            expect(file_get_contents(resource_path("js/Pages/Public/{$page}")))
                ->toContain('/anfrage?leistung=${serviceType.slug}')
                ->not->toContain('href="/anfrage"');
        }
    });
});

describe('the size of the homepage picture', function () {
    it('is set from the admin panel', function () {
        expect(ContentBlock::where('page_key', 'startseite')
            ->where('section_key', 'hero')
            ->where('field_key', 'bild_groesse')
            ->exists())->toBeTrue();
    });

    it('is clamped, so nobody can push the rest of the hero off the screen', function () {
        $source = file_get_contents(resource_path('js/Pages/Public/Startseite.vue'));

        expect($source)->toContain('Math.min(140, Math.max(60, typed))');
    });
});

describe('moving to the second step', function () {
    it('counts the step without going through Inertia', function () {
        // The endpoint answers with JSON, and Inertia treats anything that is
        // not an Inertia response as a failure — routing this through its
        // router put an error dialog on screen at the exact moment somebody
        // moved to the second step.
        $source = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

        expect($source)->toContain("axios.post('/anfrage/schritt'")
            ->and($source)->not->toContain("router.post('/anfrage/schritt'");
    });

    it('never lets a failed count interrupt the form', function () {
        expect(file_get_contents(resource_path('js/Pages/Public/Anfrage.vue')))
            ->toContain('.catch(() => {})');
    });

    it('still answers the counter with plain json', function () {
        $this->postJson('/anfrage/schritt', ['step' => 'schritt_2'])
            ->assertOk()
            ->assertExactJson(['ok' => true]);
    });
});

describe('what the operator can reword', function () {
    it('offers every string on the first step', function () {
        foreach (['cta_schritt_1', 'frage_leistung', 'frage_hinweis', 'weiter', 'hinweis_schritt_1'] as $field) {
            expect(ContentBlock::where('page_key', 'anfrage')
                ->where('section_key', 'formular')
                ->where('field_key', $field)
                ->exists())->toBeTrue("anfrage.formular.{$field} fehlt");
        }
    });

    it('reaches the first step rather than sitting unused in the admin panel', function () {
        $source = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

        foreach (['frage_leistung', 'frage_hinweis', 'hinweis_schritt_1', 'frage_plz'] as $field) {
            expect($source)->toContain("'{$field}'");
        }
    });
});

describe('the blog on a telephone', function () {
    it('has no bar pinned over the article', function () {
        foreach (['Ratgeber.vue', 'RatgeberBeitrag.vue'] as $page) {
            expect(file_get_contents(resource_path("js/Pages/Public/{$page}")))
                ->toContain(':sticky-cta="false"');
        }
    });

    it('still offers the request at the end of the piece', function () {
        expect(file_get_contents(resource_path('js/Pages/Public/RatgeberBeitrag.vue')))
            ->toContain('href="/anfrage"');
    });
});
