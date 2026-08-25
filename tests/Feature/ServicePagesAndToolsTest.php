<?php

use App\Jobs\NotifyOfficeOfRequestJob;
use App\Jobs\SendPartnerBroadcastJob;
use App\Models\Assessor;
use App\Models\Faq;
use App\Models\FunnelEvent;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
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

    it('links down to the cities where it is offered', function () {
        $type = ServiceType::factory()->create(['is_active' => true]);
        $city = App\Models\City::create(['name' => 'Köln', 'is_active' => true]);
        $city->serviceTypes()->sync([$type->id]);

        $this->get("/leistungen/{$type->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('cities', 1)
                ->where('cities.0.url', "/kfz-gutachter/koeln/{$type->slug}"));
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

    it('counts the middle steps when the browser reports them', function () {
        $this->post('/anfrage/schritt', ['step' => 'schritt_2'])->assertOk();
        $this->post('/anfrage/schritt', ['step' => 'schritt_3'])->assertOk();

        $funnel = collect(FunnelEvent::funnel(now()->subDay(), now()))->keyBy('step');

        expect($funnel['schritt_2']['count'])->toBe(1)
            ->and($funnel['schritt_3']['count'])->toBe(1);
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
            ->assertInertia(fn ($page) => $page->has('funnel', 4));
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
