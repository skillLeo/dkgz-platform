<?php

use App\Models\Assessor;
use App\Models\EmailLog;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;

/**
 * A request taken over the telephone has to end up exactly where a request
 * typed by the customer ends up — same confirmation, same matching, same
 * partners notified. The office and the public form call one action for that
 * reason, and these hold the two together.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->type = ServiceType::factory()->create(['is_active' => true, 'dkgz_fee_cents' => 7_900]);
});

function telephoneRequest(array $overrides = []): array
{
    return array_merge([
        'service_type_id' => test()->type->id,
        'postal_code' => '40589',
        'city' => 'Düsseldorf',
        'customer_name' => 'Martina Reinhardt',
        'customer_phone' => '+49 211 3300124',
        'customer_email' => 'kundin@beispiel.test',
        'vehicle_make' => 'VW',
        'vehicle_model' => 'Passat B8',
        'urgency' => 'soon',
    ], $overrides);
}

it('creates a request and sends the customer the ordinary confirmation', function () {
    Mail::fake();

    $this->actingAs($this->admin)
        ->post('/admin/anfragen', telephoneRequest())
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $request = ServiceRequest::firstWhere('customer_email', 'kundin@beispiel.test');

    expect($request)->not->toBeNull()
        ->and($request->reference)->not->toBeEmpty()
        ->and($request->consent_at)->not->toBeNull();

    expect(EmailLog::where('recipient', 'kundin@beispiel.test')->exists())->toBeTrue();
});

it('runs the matching engine just as the public form does', function () {
    Mail::fake();

    $assessor = Assessor::factory()->create([
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_available' => true,
    ]);
    $assessor->serviceAreas()->create(['postal_code_from' => '40000', 'postal_code_to' => '41999']);
    $assessor->serviceTypes()->sync([$this->type->id]);

    $this->actingAs($this->admin)->post('/admin/anfragen', telephoneRequest());

    $request = ServiceRequest::firstWhere('customer_email', 'kundin@beispiel.test');

    expect($request->status)->toBe(ServiceRequest::STATUS_MATCHED)
        ->and($request->matches()->where('assessor_id', $assessor->id)->exists())->toBeTrue();
});

it('records no browser fingerprint for a request taken by telephone', function () {
    Mail::fake();

    $this->actingAs($this->admin)->post('/admin/anfragen', telephoneRequest());

    $request = ServiceRequest::firstWhere('customer_email', 'kundin@beispiel.test');

    // The office's own address is not the customer's, and this field exists to
    // answer where the request came from.
    expect($request->ip_address)->toBeNull()
        ->and($request->user_agent)->toBeNull();
});

it('validates the same fields the public form does', function () {
    $this->actingAs($this->admin)
        ->post('/admin/anfragen', telephoneRequest(['customer_email' => 'kein-email', 'postal_code' => '1']))
        ->assertSessionHasErrors(['customer_email', 'postal_code']);
});

it('does not ask an operator to tick a consent box or beat a timer', function () {
    Mail::fake();

    // Neither `consent` nor `rendered_at` is sent, and the request still goes
    // through — those checks exist for strangers, not authenticated staff.
    $this->actingAs($this->admin)
        ->post('/admin/anfragen', telephoneRequest())
        ->assertSessionHasNoErrors();
});

it('keeps the screen behind its own permission', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->get('/admin/anfragen/neu')->assertForbidden();
    $this->actingAs($outsider)->post('/admin/anfragen', telephoneRequest())->assertForbidden();
});
