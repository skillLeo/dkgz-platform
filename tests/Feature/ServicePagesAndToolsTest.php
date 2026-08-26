<?php

use App\Jobs\NotifyOfficeOfRequestJob;
use App\Jobs\SendPartnerBroadcastJob;
use App\Models\Assessor;
use App\Models\City;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\FunnelEvent;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\FaqSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    $this->seed(ContentBlockSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

describe('the service page', function () {
    it('shows the questions belonging to that service', function () {
        $type = ServiceType::factory()->create([
            'name_de' => 'Wertgutachten',
            'is_active' => true,
            'faqs' => [
                ['frage' => 'Wie lange dauert ein Wertgutachten?', 'antwort' => 'In der Regel zwei Werktage.'],
                ['frage' => 'Was kostet es?', 'antwort' => 'Das legt der Sachverständige fest.'],
            ],
        ]);

        $this->get("/leistungen/{$type->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Leistung')
                ->has('serviceType.faqs', 2)
                ->where('serviceType.faqs.0.frage', 'Wie lange dauert ein Wertgutachten?'));
    });

    it('works for a service with no questions of its own', function () {
        $type = ServiceType::factory()->create(['is_active' => true, 'faqs' => null]);

        $this->get("/leistungen/{$type->slug}")->assertOk();
    });

    it('no longer lists cities on the nationwide page', function () {
        // Those links belong on the city pages. Repeating them here made the
        // sidebar a directory rather than a call to action.
        $type = ServiceType::factory()->create(['is_active' => true]);
        $city = City::create(['name' => 'Köln', 'is_active' => true]);
        $city->serviceTypes()->sync([$type->id]);

        $this->get("/leistungen/{$type->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->missing('cities'));
    });

    it('lets an operator save questions against a service', function () {
        $type = ServiceType::factory()->create(['is_active' => true, 'dkgz_fee_cents' => 7_900]);

        $this->actingAs($this->admin)->post("/admin/leistungsarten/{$type->id}", [
            'name_de' => $type->name_de,
            'is_active' => true,
            'dkgz_fee_cents' => 7_900,
            'icon' => 'euro',
            'faqs' => [['frage' => 'Eine Frage?', 'antwort' => 'Eine Antwort.']],
        ])->assertSessionHasNoErrors();

        expect($type->fresh()->faqs)->toHaveCount(1)
            ->and($type->fresh()->icon)->toBe('euro');
    });
});

describe('the FAQ page', function () {
    it('groups every published question by category', function () {
        Faq::create(['question_de' => 'Kostet das etwas?', 'answer_de' => 'Nein.', 'category' => 'Kosten', 'is_published' => true]);

        $this->get('/faq')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/Faq')->has('groups'));
    });

    it('is in the header, and the partner page is not', function () {
        $source = file_get_contents(resource_path('js/Layouts/PublicLayout.vue'));
        $nav = substr($source, strpos($source, 'const nav = ['), 400);

        expect($nav)->toContain("'/faq'")
            ->and($nav)->not->toContain('fuer-sachverstaendige');

        // Still reachable from the footer.
        expect($source)->toContain('/fuer-sachverstaendige');
    });
});

describe('the office notification', function () {
    it('goes out with every new request', function () {
        Queue::fake();

        $type = ServiceType::factory()->create(['is_active' => true]);

        $this->post('/anfrage', [
            'service_type_id' => $type->id,
            'postal_code' => '40589',
            'city' => 'Düsseldorf',
            'customer_name' => 'Martina Reinhardt',
            'customer_phone' => '+49 211 3300124',
            'customer_email' => 'kundin@beispiel.test',
            'vehicle_make' => 'VW',
            'vehicle_model' => 'Passat',
            'consent' => true,
            'rendered_at' => now()->subMinute()->getTimestampMs(),
        ])->assertRedirect();

        Queue::assertPushed(NotifyOfficeOfRequestJob::class);
    });
});

describe('the request funnel', function () {
    it('counts the form being opened and the request being sent', function () {
        $type = ServiceType::factory()->create(['is_active' => true]);

        $this->get('/anfrage')->assertOk();

        $this->post('/anfrage', [
            'service_type_id' => $type->id,
            'postal_code' => '40589',
            'city' => 'Düsseldorf',
            'customer_name' => 'Martina Reinhardt',
            'customer_phone' => '+49 211 3300124',
            'customer_email' => 'kundin@beispiel.test',
            'vehicle_make' => 'VW',
            'vehicle_model' => 'Passat',
            'consent' => true,
            'rendered_at' => now()->subMinute()->getTimestampMs(),
        ]);

        $funnel = collect(FunnelEvent::funnel(now()->subDay(), now()))->keyBy('step');

        expect($funnel['begonnen']['count'])->toBe(1)
            ->and($funnel['abgesendet']['count'])->toBe(1);
    });

    it('counts the contact step when the browser reports it', function () {
        $this->post('/anfrage/schritt', ['step' => 'schritt_2'])->assertOk();

        $funnel = collect(FunnelEvent::funnel(now()->subDay(), now()))->keyBy('step');

        expect($funnel['schritt_2']['count'])->toBe(1);
    });

    it('ignores the third step, which the form no longer has', function () {
        // Counts recorded against it while it existed stay in the table; the
        // form stops adding to them and the chart stops showing them.
        $this->post('/anfrage/schritt', ['step' => 'schritt_3'])->assertOk();

        expect(FunnelEvent::count())->toBe(0);
        expect(collect(FunnelEvent::funnel(now()->subDay(), now()))->pluck('step')->all())
            ->toBe(['begonnen', 'schritt_2', 'abgesendet']);
    });

    it('ignores a step it does not recognise', function () {
        $this->post('/anfrage/schritt', ['step' => 'schritt_99'])->assertOk();

        expect(FunnelEvent::count())->toBe(0);
    });

    it('records nothing that could identify anybody', function () {
        FunnelEvent::record('begonnen');

        $columns = array_keys(FunnelEvent::first()->getAttributes());

        foreach (['ip', 'ip_address', 'session', 'user_agent', 'user_id'] as $forbidden) {
            expect($columns)->not->toContain($forbidden);
        }
    });

    it('shows the funnel on the admin dashboard', function () {
        FunnelEvent::record('begonnen');

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('funnel', 3));
    });
});

describe('the partner broadcast', function () {
    it('queues one message per approved partner', function () {
        Queue::fake();

        Assessor::factory()->count(3)->create(['approval_status' => Assessor::STATUS_APPROVED]);

        $this->actingAs($this->admin)->post('/admin/partnermail', [
            'audience' => 'approved',
            'subject' => 'Neue Funktion',
            'body' => 'Guten Tag, es gibt Neuigkeiten.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        Queue::assertPushed(SendPartnerBroadcastJob::class);
    });

    it('sends only to the test address when one is given', function () {
        Queue::fake();

        Assessor::factory()->count(3)->create(['approval_status' => Assessor::STATUS_APPROVED]);

        $this->actingAs($this->admin)->post('/admin/partnermail', [
            'audience' => 'approved',
            'subject' => 'Probe',
            'body' => 'Text.',
            'test_email' => 'info@dkgz.de',
        ])->assertRedirect();

        Queue::assertPushed(SendPartnerBroadcastJob::class, fn ($job) => true);
    });

    it('refuses an empty audience rather than sending nothing quietly', function () {
        $this->actingAs($this->admin)->post('/admin/partnermail', [
            'audience' => 'approved',
            'subject' => 'Betreff',
            'body' => 'Text.',
        ])->assertSessionHasErrors('audience');
    });

    it('keeps the screen behind the partner permission', function () {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get('/admin/partnermail')->assertForbidden();
    });
});

describe('the sidebar and questions', function () {
    it('shows the service questions on the city page too', function () {
        $type = ServiceType::factory()->create([
            'name_de' => 'Unfallgutachten',
            'is_active' => true,
            'faqs' => [['frage' => 'Wie lange dauert es?', 'antwort' => 'Zwei Werktage.']],
        ]);

        $city = City::create(['name' => 'Düsseldorf', 'is_active' => true]);
        $city->serviceTypes()->sync([$type->id]);

        $this->get("/kfz-gutachter/duesseldorf/{$type->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('serviceType.faqs', 1)
                ->where('serviceType.faqs.0.frage', 'Wie lange dauert es?'));
    });

    it('carries the reassurance points for both kinds of page', function () {
        $this->get('/leistungen')->assertOk();

        expect(ContentBlock::where('page_key', 'leistungen')->where('field_key', 'punkt_1')->exists())->toBeTrue()
            ->and(ContentBlock::where('page_key', 'staedte')->where('field_key', 'punkt_1')->exists())->toBeTrue();
    });

    it('no longer offers city links on the nationwide service page', function () {
        $source = file_get_contents(resource_path('js/Pages/Public/Leistung.vue'));

        expect($source)->not->toContain('nach Stadt')
            ->and($source)->not->toContain('cities');
    });

    it('uses one short call to action everywhere', function () {
        $ctas = ContentBlock::whereIn('page_key', ['leistungen', 'staedte'])
            ->where('field_key', 'cta')
            ->pluck('value')
            ->unique();

        expect($ctas)->toHaveCount(1)
            ->and($ctas->first())->toBe('Jetzt Gutachter anfragen');
    });

    it('keeps a button on one line whatever is typed into it', function () {
        // An operator editing a label cannot see that their wording broke a
        // button in two on somebody else's screen.
        $source = file_get_contents(resource_path('js/Components/Base/BaseButton.vue'));

        expect($source)->toContain('whitespace-nowrap');
    });
});

describe('FAQ categories', function () {
    it('offers the fixed list when writing a question', function () {
        $this->actingAs($this->admin)
            ->get('/admin/faq')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('categories'));
    });

    it('refuses a category that is not one of them', function () {
        $this->actingAs($this->admin)
            ->post('/admin/faq', [
                'question_de' => 'Eine Frage?',
                'answer_de' => 'Eine Antwort.',
                'category' => 'Erfunden',
            ])
            ->assertSessionHasErrors('category');
    });

    it('accepts one that is', function () {
        $this->actingAs($this->admin)
            ->post('/admin/faq', [
                'question_de' => 'Was kostet die Vermittlung?',
                'answer_de' => 'Nichts.',
                'category' => 'Kosten',
            ])
            ->assertSessionHasNoErrors();

        expect(Faq::firstWhere('question_de', 'Was kostet die Vermittlung?')->category)->toBe('Kosten');
    });
});

describe('which questions reach the homepage', function () {
    it('shows only the ones picked out for it', function () {
        // The homepage used to carry every published question, so growing the
        // FAQ lengthened the front page instead of filling the FAQ page.
        Faq::query()->delete();
        Faq::factory()->onHomepage()->create(['question_de' => 'Ist die Anfrage kostenlos?']);
        Faq::factory()->create(['question_de' => 'Wie lange werden Daten gespeichert?']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('faqs', fn ($faqs) => collect($faqs)->pluck('question_de')->all() === ['Ist die Anfrage kostenlos?'])
            );
    });

    it('still answers all of them on the FAQ page', function () {
        Faq::query()->delete();
        Faq::factory()->onHomepage()->create(['question_de' => 'Ist die Anfrage kostenlos?']);
        Faq::factory()->create(['question_de' => 'Wie lange werden Daten gespeichert?', 'category' => 'Datenschutz']);

        $this->get('/faq')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('groups', fn ($groups) => collect($groups)->flatten(1)->count() === 2)
            );
    });

    it('leaves an unpublished question off the homepage even when it is ticked', function () {
        Faq::query()->delete();
        Faq::factory()->onHomepage()->create(['is_published' => false]);

        $this->get('/')->assertInertia(fn ($page) => $page->where('faqs', []));
    });

    it('lets the admin tick and untick it', function () {
        $this->actingAs($this->admin)
            ->post('/admin/faq', [
                'question_de' => 'Wer zahlt das Gutachten?',
                'answer_de' => 'In der Regel die gegnerische Versicherung.',
                'category' => 'Kosten',
                'show_on_homepage' => true,
            ])
            ->assertSessionHasNoErrors();

        $faq = Faq::firstWhere('question_de', 'Wer zahlt das Gutachten?');
        expect($faq->show_on_homepage)->toBeTrue();

        $this->actingAs($this->admin)
            ->post("/admin/faq/{$faq->id}", [
                'question_de' => $faq->question_de,
                'answer_de' => $faq->answer_de,
                'category' => 'Kosten',
                'show_on_homepage' => false,
                'is_published' => true,
            ])
            ->assertSessionHasNoErrors();

        expect($faq->fresh()->show_on_homepage)->toBeFalse();
    });

    it('keeps the questions that were already on the front page there', function () {
        // Nobody asked for the homepage to empty itself the day this shipped.
        Faq::query()->delete();
        $this->seed(FaqSeeder::class);

        expect(Faq::onHomepage()->count())->toBe(6);
    });
});
