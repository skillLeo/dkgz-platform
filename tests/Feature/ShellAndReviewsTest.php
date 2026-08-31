<?php

use App\Actions\AcceptAssignmentAction;
use App\Actions\MatchRequestAction;
use App\Jobs\RequestGoogleReviewJob;
use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\ContentBlock;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

describe('the buttons in the shell', function () {
    it('carries all three to every page', function () {
        // They belong to no page, so like the announcement bar above them they
        // travel with the shared props rather than with whatever is loaded.
        $this->get('/faq')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('app.cta.kurz', 'Anfrage')
                ->where('app.cta.lang', 'Anfrage starten')
                ->where('app.cta.sticky', 'Anfrage starten'));
    });

    it('lets the short and the long label differ', function () {
        // The header on a phone has room for a word; the same header on a
        // desktop has room for a sentence.
        ContentBlock::where('page_key', 'layout')->where('field_key', 'kurz')->update(['value' => 'Gutachter']);

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('app.cta.kurz', 'Gutachter')
                ->where('app.cta.lang', 'Anfrage starten'));
    });

    it('reads each of the three from its own field', function () {
        $source = file_get_contents(resource_path('js/Layouts/PublicLayout.vue'));

        expect($source)->toContain('cta.short')
            ->and($source)->toContain('cta.long')
            ->and($source)->toContain('cta.sticky')
            ->and($source)->not->toContain('>Anfrage starten<');
    });
});

describe('the line above the headline', function () {
    it('takes its gold rule with it when emptied', function () {
        // Emptying the wording used to leave the rule floating above the
        // headline with nothing to underline.
        $source = file_get_contents(resource_path('js/Pages/Public/Startseite.vue'));

        expect($source)->toContain("<template v-if=\"t('hero', 'eyebrow')\">");
    });

    it('says so in the admin panel', function () {
        $help = ContentBlock::where('page_key', 'startseite')
            ->where('section_key', 'hero')
            ->where('field_key', 'eyebrow')
            ->value('help_de');

        expect($help)->toContain('Leer lassen');
    });
});

describe('customer voices', function () {
    it('shows only published ones, in order', function () {
        Testimonial::create(['name' => 'Martina R.', 'quote' => 'Sehr schnell.', 'is_published' => true, 'sort_order' => 2]);
        Testimonial::create(['name' => 'Jens K.', 'quote' => 'Alles gut.', 'is_published' => true, 'sort_order' => 1]);
        Testimonial::create(['name' => 'Entwurf', 'quote' => 'Noch nicht.', 'is_published' => false]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('testimonials', 2)
                ->where('testimonials.0.name', 'Jens K.'));
    });

    it('leaves the section out entirely when there are none', function () {
        // An empty band where praise should be reads as the absence of any.
        $this->get('/')->assertInertia(fn ($page) => $page->where('testimonials', []));
    });

    it('falls back to initials when there is no photograph', function () {
        $voice = Testimonial::create(['name' => 'Martina Reinhardt', 'quote' => 'Gut.', 'is_published' => true]);

        expect($voice->initials())->toBe('MR')
            ->and($voice->photoUrl())->toBeNull();
    });

    it('lets the office write one', function () {
        $this->actingAs($this->admin)
            ->post('/admin/kundenstimmen', [
                'name' => 'Martina R.',
                'location' => 'Düsseldorf',
                'quote' => 'Innerhalb eines Tages hatte ich einen Termin.',
                'rating' => 5,
                'is_published' => true,
            ])
            ->assertSessionHasNoErrors();

        expect(Testimonial::firstWhere('name', 'Martina R.')->rating)->toBe(5);
    });

    it('refuses one with no words in it', function () {
        $this->actingAs($this->admin)
            ->post('/admin/kundenstimmen', ['name' => 'Ohne Zitat', 'quote' => ''])
            ->assertSessionHasErrors('quote');
    });
});

describe('the review ask', function () {
    it('is scheduled when a partner takes the job, not when it is finished', function () {
        // A day after acceptance the assessor has been in touch and the
        // customer feels best about it. By completion the moment has passed.
        Queue::fake();

        $type = ServiceType::factory()->create(['is_active' => true, 'dkgz_fee_cents' => 8500]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('assessor');
        $assessor = Assessor::factory()->create([
            'user_id' => $user->id,
            'approval_status' => Assessor::STATUS_APPROVED,
            'is_available' => true,
        ]);
        $assessor->serviceTypes()->sync([$type->id]);
        $assessor->serviceAreas()->create(['postal_code_from' => '40000', 'postal_code_to' => '41999']);

        $request = ServiceRequest::factory()->create([
            'service_type_id' => $type->id,
            'postal_code' => '40589',
            'reference' => ServiceRequest::nextReference(),
        ]);

        app(MatchRequestAction::class)->execute($request);
        app(AcceptAssignmentAction::class)->execute($request, $assessor);

        Queue::assertPushed(RequestGoogleReviewJob::class);
    });

    it('is dispatched from the acceptance itself', function () {
        $source = file_get_contents(app_path('Actions/AcceptAssignmentAction.php'));

        expect($source)->toContain('RequestGoogleReviewJob::dispatch')
            ->and($source)->toContain('google_review_delay_days');
    });

    it('waits the configured number of days', function () {
        expect(Settings::int('business.google_review_delay_days', 1))->toBe(1);
    });

    it('points at the operator\'s own Google profile', function () {
        expect(Settings::get('business.google_review_url'))->toContain('g.page');
    });

    it('sends nothing when no review link is configured', function () {
        Settings::set('business.google_review_url', '');
        Queue::fake();

        $assignment = Assignment::factory()->create();
        (new RequestGoogleReviewJob($assignment->id))->handle();

        // An e-mail asking somebody to review with nowhere to do it is worse
        // than no e-mail.
        expect(EmailLog::where('template_key', 'google-bewertung')->count())->toBe(0);
    });

    it('has a template of its own', function () {
        $template = EmailTemplate::firstWhere('key', 'google-bewertung');

        expect($template)->not->toBeNull()
            ->and($template->subject_de)->toContain('{{ kunde }}');
    });

    it('no longer asks the same customer twice', function () {
        // The internal scale still exists; it is opt-in so that acceptance and
        // completion do not both ask for the same goodwill a week apart.
        expect(Settings::bool('features.internal_review_request', true))->toBeFalse();
    });
});
