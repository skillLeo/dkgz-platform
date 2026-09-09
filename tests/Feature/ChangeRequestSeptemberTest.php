<?php

use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\City;
use App\Models\ServiceRequest;
use App\Models\ContentBlock;
use App\Models\FunnelEvent;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\AttentionQueue;
use App\Support\Content;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);
});

/** An approved, listed partner with nothing in their service list. */
function barePartner(): Assessor
{
    $user = User::factory()->create(['is_active' => true]);

    return Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_listed' => true,
        'company_name' => 'Sachverständigenbüro Ohne',
        'city' => 'Kassel',
    ]);
}

describe('the footer', function () {
    it('belongs to the shell rather than to the homepage', function () {
        // Stored under "startseite" it reached only the homepage, because every
        // page hands its own block set to Inertia.
        expect(ContentBlock::where('page_key', 'startseite')->where('section_key', 'fuss')->count())->toBe(0)
            ->and(Content::page('layout')['fuss']['rechtshinweis'] ?? null)->not->toBeEmpty();
    });

    it('reaches a page that is not the homepage', function () {
        foreach (['/', '/anfrage', '/ueber-uns', '/kontakt'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertInertia(fn ($page) => $page->where(
                    'app.fuss.rechtshinweis',
                    'DKGZ ist eine Vermittlungsstelle. Das Gutachten erstellt der vermittelte Sachverständige.',
                ));
        }
    });

    it("follows the operator's own wording everywhere once they edit it", function () {
        ContentBlock::where('page_key', 'layout')
            ->where('section_key', 'fuss')
            ->where('field_key', 'rechtshinweis')
            ->first()
            ->update(['value' => 'Eigener Hinweis.']);

        Content::flush('layout');

        $this->get('/kontakt')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('app.fuss.rechtshinweis', 'Eigener Hinweis.'));
    });
});

describe('a partner who has set no services', function () {
    it('can still be sent a request, because the form has something to choose', function () {
        ServiceType::factory()->create(['name_de' => 'Unfallgutachten', 'is_active' => true]);
        ServiceType::factory()->create(['name_de' => 'Wertgutachten', 'is_active' => true]);

        $assessor = barePartner();

        $this->get("/sachverstaendige/{$assessor->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('assessor.services', [])
                // Empty here was a dropdown with nothing in it, so the visitor
                // could not choose, could not send, and the page was a dead end.
                ->where('requestServiceTypes', fn ($types) => count($types) === 2));
    });

    it('is described without the sentence that lists services', function () {
        $source = file_get_contents(resource_path('js/Pages/Public/Sachverstaendiger.vue'));

        // A placeholder with nothing behind it is left standing on purpose, so
        // the profile showed a literal "{leistungen}".
        expect($source)->toContain("t('profil', 'beschreibung_ohne_leistungen'")
            ->and($source)->toContain("|| 'Kfz-Gutachten'")
            ->and(ContentBlock::where('page_key', 'verzeichnis')
                ->where('field_key', 'beschreibung_ohne_leistungen')->exists())->toBeTrue();
    });

    it('keeps naming its services when it has some', function () {
        $type = ServiceType::factory()->create(['name_de' => 'Unfallgutachten', 'is_active' => true]);
        $assessor = barePartner();
        $assessor->serviceTypes()->attach($type->id);

        $this->get("/sachverstaendige/{$assessor->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('requestServiceTypes', fn ($types) => count($types) === 1));
    });
});

describe('the visitor count', function () {
    it('counts somebody who arrives on a public page', function () {
        $this->get('/')->assertOk();

        expect(FunnelEvent::where('step', 'besucher')->sum('count'))->toBe(1);
    });

    it('counts a visit once rather than once per page', function () {
        foreach (['/', '/leistungen', '/ueber-uns', '/kontakt'] as $url) {
            $this->get($url)->assertOk();
        }

        expect(FunnelEvent::where('step', 'besucher')->sum('count'))->toBe(1);
    });

    it('does not count staff moving around the admin panel', function () {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin')->assertOk();

        expect(FunnelEvent::where('step', 'besucher')->sum('count'))->toBe(0);
    });

    it('leaves out anything that announces itself as a crawler', function () {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])
            ->get('/')
            ->assertOk();

        expect(FunnelEvent::where('step', 'besucher')->sum('count'))->toBe(0);
    });

    it('is the number the dashboard leads with', function () {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        FunnelEvent::create(['step' => 'besucher', 'day' => today(), 'count' => 200]);
        FunnelEvent::create(['step' => 'begonnen', 'day' => today(), 'count' => 50]);
        FunnelEvent::create(['step' => 'abgesendet', 'day' => today(), 'count' => 10]);

        $this->actingAs($admin)
            ->get('/admin?zeitraum=heute')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('funnel', function ($rows) {
                $rows = collect($rows);

                // Both steps of the request are charted separately: the office
                // wants to know how many reach the second screen, not only how
                // many open the first.
                return $rows->pluck('step')->all() === ['besucher', 'begonnen', 'schritt_2', 'abgesendet']
                    // Measured against the top of the funnel, which is now the
                    // visitors rather than the people who opened the form.
                    && (int) $rows->firstWhere('step', 'abgesendet')['share'] === 5;
            }));
    });
});

describe('the attention list', function () {
    it('no longer chases a Gutachten nobody is asked for', function () {
        // Completion stopped requiring the report, but this kept counting the
        // days since a file nobody asks for — so every accepted job turned into
        // a warning a week later and the list filled with rows nobody could act
        // on. A month-old job with no documents at all must raise nothing.
        $type = ServiceType::factory()->create(['is_active' => true]);
        $assessor = barePartner();

        $request = ServiceRequest::factory()->create([
            'service_type_id' => $type->id,
            'status' => ServiceRequest::STATUS_ASSIGNED,
            'matched_count' => 1,
        ]);

        $assignment = Assignment::factory()->create([
            'assessor_id' => $assessor->id,
            'service_request_id' => $request->id,
            'status' => Assignment::STATUS_IN_PROGRESS,
            'accepted_at' => now()->subDays(30),
        ]);

        expect($assignment->documents()->count())->toBe(0)
            ->and(collect(AttentionQueue::items(PHP_INT_MAX))->pluck('matter')->implode(' '))
            ->not->toContain('Gutachten');
    });
});

describe('the order of the contact fields', function () {
    it('is a switch, and asks for the telephone number first by default', function () {
        expect(Content::bool('anfrage.formular.telefon_zuerst'))->toBeTrue()
            ->and(ContentBlock::where('page_key', 'anfrage')
                ->where('field_key', 'telefon_zuerst')->value('type'))->toBe('boolean');
    });

    it('actually reorders the two fields', function () {
        $source = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

        expect($source)->toContain("isOn(props.content, 'formular', 'telefon_zuerst')")
            ->and($source)->toContain(":class=\"phoneFirst ? 'order-1' : 'order-2'\"")
            ->and($source)->toContain(":class=\"phoneFirst ? 'order-2' : 'order-1'\"");
    });
});

describe('the passage on a city page', function () {
    it('sits below the questions rather than above the services', function () {
        // It used to sit above the services, where it pushed what a visitor came
        // for below the fold behind a paragraph they had not asked to read.
        $source = file_get_contents(resource_path('js/Pages/Public/Stadt.vue'));

        expect(strpos($source, 'faq_ueberschrift'))->toBeLessThan(strpos($source, 'city.body'));
    });

    it('is shown only where somebody has written one', function () {
        $city = City::create(['name' => 'Kassel', 'slug' => 'kassel', 'is_active' => true]);

        // A city offering nothing has no page at all, so it needs one service
        // before there is anything to look at.
        $city->serviceTypes()->attach(ServiceType::factory()->create(['is_active' => true])->id);

        $this->get("/kfz-gutachter/{$city->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('city.body', null));

        $city->update(['body' => '<p>Etwas über Kassel.</p>']);

        $this->get("/kfz-gutachter/{$city->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('city.body', '<p>Etwas über Kassel.</p>'));
    });
});
