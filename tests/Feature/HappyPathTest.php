<?php

use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\CustomerReview;
use App\Models\EmailLog;
use App\Models\PostalCode;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\Formatter;
use App\Support\Settings;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * The whole business model in one test: submit → match → accept → upload →
 * complete → commission → review → redirect.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ServiceTypeSeeder::class);
    $this->seed(ContentBlockSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    PostalCode::create(['code' => '40589', 'city' => 'Düsseldorf', 'state' => 'Nordrhein-Westfalen']);

    Storage::fake('private');
    Mail::fake();

    $this->type = ServiceType::where('slug', 'unfallgutachten')->first();

    // Two partners cover the postal code and offer the service; one will win.
    $this->winner = makePartner($this->type, 'Kfz-SV-Büro Reinhardt');
    $this->loser = makePartner($this->type, 'Kfz-SV-Büro Ohlsen');
});

function makePartner(ServiceType $type, string $company): Assessor
{
    $user = User::factory()->create(['is_active' => true, 'last_name' => 'Reinhardt']);
    $user->assignRole('assessor');

    $assessor = Assessor::factory()->create([
        'user_id' => $user->id,
        'company_name' => $company,
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_available' => true,
    ]);

    AssessorServiceArea::factory()->covering('40589')->create(['assessor_id' => $assessor->id]);
    $assessor->serviceTypes()->attach($type->id);

    return $assessor->fresh(['user', 'serviceAreas', 'serviceTypes']);
}

it('carries a request from the public form all the way to a settled review', function () {
    // ---- 1. The customer submits, with no account ----------------------
    $response = $this->post('/anfrage', [
        'service_type_id' => $this->type->id,
        'postal_code' => '40589',
        'city' => 'Düsseldorf-Wersten',
        'customer_name' => 'Martina Reinhardt',
        'customer_phone' => '+49 179 4480169',
        'customer_email' => 'm.reinhardt@web.test',
        'vehicle_make' => 'VW',
        'vehicle_model' => 'Passat B8',
        'vehicle_year' => 2019,
        'vehicle_plate' => 'D-AB 1234',
        'description' => 'Heckschaden nach Auffahrunfall, Fahrzeug ist fahrbereit.',
        'urgency' => 'soon',
        'consent' => true,
        'rendered_at' => (microtime(true) * 1000) - 10_000,
    ]);

    $request = ServiceRequest::firstWhere('customer_email', 'm.reinhardt@web.test');

    expect($request)->not->toBeNull()
        ->and($request->reference)->toMatch('/^DKGZ\d{4}\d{4}$/')
        ->and($request->consent_at)->not->toBeNull();

    $response->assertRedirect(route('request.confirmation', $request->reference));

    // ---- 2. Matching ran synchronously ---------------------------------
    $request->refresh();

    expect($request->status)->toBe(ServiceRequest::STATUS_MATCHED)
        ->and($request->matched_count)->toBe(2)
        ->and(RequestMatch::where('service_request_id', $request->id)->pending()->count())->toBe(2);

    // The customer sees their reference on the confirmation screen.
    $this->get(route('request.confirmation', $request->reference))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('request.reference', $request->reference));

    // ---- 3. Before acceptance, neither partner sees the contact data ----
    foreach ([$this->winner, $this->loser] as $partner) {
        $this->actingAs($partner->user)
            ->get("/portal/anfragen/{$request->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('request.contact_released', false))
            ->assertDontSee('Martina Reinhardt')
            ->assertDontSee('m.reinhardt@web.test');
    }

    // ---- 4. The winner accepts ------------------------------------------
    $this->actingAs($this->winner->user)
        ->post("/portal/anfragen/{$request->id}/annehmen")
        ->assertRedirect();

    $assignment = Assignment::where('service_request_id', $request->id)->firstOrFail();
    $request->refresh();

    expect($request->status)->toBe(ServiceRequest::STATUS_ASSIGNED)
        ->and($assignment->assessor_id)->toBe($this->winner->id)
        ->and(Assignment::where('service_request_id', $request->id)->count())->toBe(1);

    // The loser's row is closed and they still cannot see the customer.
    expect(RequestMatch::where('service_request_id', $request->id)
        ->where('assessor_id', $this->loser->id)->value('outcome'))->toBe(RequestMatch::OUTCOME_CLOSED);

    $this->actingAs($this->loser->user)
        ->get("/portal/anfragen/{$request->id}")
        ->assertDontSee('Martina Reinhardt');

    // The winner now sees the contact details.
    $this->actingAs($this->winner->user)
        ->get("/portal/auftraege/{$assignment->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('request.contact_released', true)
            ->where('request.customer.name', 'Martina Reinhardt'));

    // ---- 5. Completion is refused until both documents are on file ------
    $this->actingAs($this->winner->user)
        ->post("/portal/auftraege/{$assignment->id}/abschliessen", ['fee_cents' => 164_000])
        ->assertForbidden();

    $this->actingAs($this->winner->user)->post("/portal/auftraege/{$assignment->id}/dokumente", [
        'type' => 'report',
        'document' => UploadedFile::fake()->create('Gutachten.pdf', 400, 'application/pdf'),
    ])->assertRedirect();

    // Still refused with only the report.
    $this->actingAs($this->winner->user)
        ->post("/portal/auftraege/{$assignment->id}/abschliessen", ['fee_cents' => 164_000])
        ->assertForbidden();

    $this->actingAs($this->winner->user)->post("/portal/auftraege/{$assignment->id}/dokumente", [
        'type' => 'customer_invoice',
        'document' => UploadedFile::fake()->create('Rechnung.pdf', 120, 'application/pdf'),
    ])->assertRedirect();

    expect($assignment->fresh()->hasRequiredDocuments())->toBeTrue()
        ->and($assignment->fresh()->status)->toBe(Assignment::STATUS_DOCUMENTS_UPLOADED);

    // ---- 6. Completion with the actual fee ------------------------------
    $this->actingAs($this->winner->user)
        ->post("/portal/auftraege/{$assignment->id}/abschliessen", [
            'fee_cents' => 164_000,
            'notes' => 'Gutachten per Post versendet.',
        ])->assertRedirect();

    $assignment->refresh();
    $request->refresh();

    expect($assignment->status)->toBe(Assignment::STATUS_COMPLETED)
        ->and($assignment->fee_cents)->toBe(164_000)
        ->and($request->status)->toBe(ServiceRequest::STATUS_COMPLETED);

    // ---- 7. The DKGZ fee, fixed at acceptance ---------------------------
    $commission = Commission::where('assignment_id', $assignment->id)->firstOrFail();

    // Superseded by the client's change request: a fixed fee per assessment
    // type, snapshotted when the partner accepted, rather than a percentage of
    // whatever the assessor happened to invoice.
    $expectedFee = $assignment->fresh()->dkgz_fee_snapshot_cents;

    expect($commission->fee_cents)->toBe(164_000)
        ->and($commission->fee_type)->toBe(Commission::TYPE_FIXED)
        ->and($commission->rate_percent)->toBeNull()
        ->and($commission->dkgz_fee_cents)->toBe($expectedFee)
        ->and($commission->commission_cents)->toBe($expectedFee)
        ->and($commission->status)->toBe(Commission::STATUS_OPEN);

    // German money output, both sides of the wire.
    expect(Formatter::money($commission->commission_cents))->toBe(Formatter::money($expectedFee))
        ->and(Formatter::money($commission->fee_cents))->toBe('1.640,00 €');

    // ---- 8. The review token, and the rating ----------------------------
    $review = CustomerReview::where('assignment_id', $assignment->id)->firstOrFail();

    expect($review->isUsable())->toBeTrue();

    $this->get("/bewertung/{$review->token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/Bewertung')
            ->where('context.reference', $request->reference));

    $this->post("/bewertung/{$review->token}", ['rating' => 9])
        ->assertRedirect(route('review.thanks', $review->token));

    $review->refresh();

    expect($review->rating)->toBe(9)
        ->and($review->submitted_at)->not->toBeNull();

    // ---- 9. A high rating redirects to the public review page -----------
    Settings::set('business.review_redirect_url', 'https://bewertungen.example.test/dkgz');

    $this->get("/bewertung/{$review->token}/danke")
        ->assertRedirect('https://bewertungen.example.test/dkgz');

    expect($review->fresh()->redirected_at)->not->toBeNull();

    // The redirect happens once; afterwards the thank-you page is shown.
    $this->get("/bewertung/{$review->token}/danke")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/BewertungDanke'));

    // ---- 10. Every send was logged --------------------------------------
    expect(EmailLog::count())->toBeGreaterThan(0);
});

it('routes a low rating into the internal feedback step', function () {
    $assignment = Assignment::factory()->completed()->create(['assessor_id' => $this->winner->id]);
    $review = CustomerReview::factory()->create(['assignment_id' => $assignment->id]);

    $this->post("/bewertung/{$review->token}", ['rating' => 5])
        ->assertRedirect(route('review.show', $review->token))
        ->assertSessionHas('feedback_step', true);

    $this->post("/bewertung/{$review->token}/feedback", [
        'feedback_category' => 'Terminfindung',
        'feedback' => 'Die Abstimmung hat länger gedauert als angekündigt.',
        'may_contact' => true,
    ])->assertRedirect(route('review.thanks', $review->token));

    expect($review->fresh()->feedback_category)->toBe('Terminfindung');

    // A low rating is never sent on to the public review page.
    Settings::set('business.review_redirect_url', 'https://bewertungen.example.test/dkgz');

    $this->get("/bewertung/{$review->token}/danke")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/BewertungDanke'));
});

it('refuses an expired review link', function () {
    $assignment = Assignment::factory()->completed()->create(['assessor_id' => $this->winner->id]);
    $review = CustomerReview::factory()->expired()->create(['assignment_id' => $assignment->id]);

    $this->get("/bewertung/{$review->token}")->assertStatus(410);
});

describe('the public form guards', function () {
    it('rejects a submission that fills the honeypot', function () {
        $this->post('/anfrage', [
            'service_type_id' => $this->type->id,
            'postal_code' => '40589',
            'city' => 'Düsseldorf',
            'customer_name' => 'Bot',
            'customer_phone' => '+49 179 0000000',
            'customer_email' => 'bot@example.test',
            'vehicle_make' => 'VW',
            'vehicle_model' => 'Golf',
            'consent' => true,
            'website' => 'https://spam.example',
            'rendered_at' => (microtime(true) * 1000) - 10_000,
        ])->assertSessionHasErrors('website');

        expect(ServiceRequest::count())->toBe(0);
    });

    it('rejects a submission that arrives too fast to have been typed', function () {
        $this->post('/anfrage', [
            'service_type_id' => $this->type->id,
            'postal_code' => '40589',
            'city' => 'Düsseldorf',
            'customer_name' => 'Zu schnell',
            'customer_phone' => '+49 179 0000000',
            'customer_email' => 'schnell@example.test',
            'vehicle_make' => 'VW',
            'vehicle_model' => 'Golf',
            'consent' => true,
            'rendered_at' => microtime(true) * 1000,
        ])->assertSessionHasErrors('customer_name');

        expect(ServiceRequest::count())->toBe(0);
    });

    it('requires the consent checkbox', function () {
        $this->post('/anfrage', [
            'service_type_id' => $this->type->id,
            'postal_code' => '40589',
            'city' => 'Düsseldorf',
            'customer_name' => 'Ohne Einwilligung',
            'customer_phone' => '+49 179 0000000',
            'customer_email' => 'ohne@example.test',
            'vehicle_make' => 'VW',
            'vehicle_model' => 'Golf',
            'consent' => false,
            'rendered_at' => (microtime(true) * 1000) - 10_000,
        ])->assertSessionHasErrors('consent');
    });
});

it('flags a request nobody covers instead of dropping it', function () {
    PostalCode::create(['code' => '99998', 'city' => 'Musterort', 'state' => 'Thüringen']);

    $this->post('/anfrage', [
        'service_type_id' => $this->type->id,
        'postal_code' => '99998',
        'city' => 'Musterort',
        'customer_name' => 'Niemand Zuständig',
        'customer_phone' => '+49 179 0000000',
        'customer_email' => 'niemand@example.test',
        'vehicle_make' => 'VW',
        'vehicle_model' => 'Golf',
        'consent' => true,
        'rendered_at' => (microtime(true) * 1000) - 10_000,
    ])->assertRedirect();

    $request = ServiceRequest::firstWhere('customer_email', 'niemand@example.test');

    expect($request->status)->toBe(ServiceRequest::STATUS_NEW)
        ->and($request->matched_count)->toBe(0)
        ->and($request->isUnmatched())->toBeTrue()
        ->and(ServiceRequest::needsAttention()->pluck('id'))->toContain($request->id);
});
