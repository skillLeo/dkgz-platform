<?php

use App\Actions\CreateServiceRequestAction;
use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use App\Models\PostalCode;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\AttentionQueue;
use App\Support\Settings;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * A request the office made to watch the flow work.
 *
 * There was no safe way to try the form: every submission goes out to every
 * partner covering that region, so checking a change meant sending real people a
 * job that does not exist.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    $this->seed(ContentBlockSeeder::class);

    $this->type = ServiceType::factory()->create(['is_active' => true]);

    // A partner who covers the whole country, so a real request always matches.
    $user = User::factory()->create(['is_active' => true]);
    $this->assessor = Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
    ]);
    $this->assessor->serviceTypes()->attach($this->type->id);
    AssessorServiceArea::factory()->create([
        'assessor_id' => $this->assessor->id,
        'postal_code_from' => 10000,
        'postal_code_to' => 19999,
    ]);

    PostalCode::create(['code' => '10115', 'city' => 'Berlin']);
});

function submit(string $postalCode): ServiceRequest
{
    return app(CreateServiceRequestAction::class)->execute([
        'service_type_id' => test()->type->id,
        'postal_code' => $postalCode,
        'customer_name' => 'Test Tester',
        'customer_phone' => '0211 1234567',
        'customer_email' => 'test@dkgz.de',
    ]);
}

it('does nothing special while no secret code is set', function () {
    // Empty means the feature is off, and nothing can accidentally be a test.
    expect(Settings::get('features.test_postal_code'))->toBeEmpty();

    $request = submit('10115');

    expect($request->is_test)->toBeFalse();
});

it('marks a request made with the secret code, and tells no partner about it', function () {
    Settings::set('features.test_postal_code', '00000');

    $request = submit('00000');

    expect($request->is_test)->toBeTrue()
        // Matching is the step that puts a job in front of real partners.
        ->and($request->fresh()->matched_count)->toBe(0)
        ->and($request->matches()->count())->toBe(0);
});

it('still matches an ordinary request', function () {
    Settings::set('features.test_postal_code', '00000');

    $request = submit('10115');

    expect($request->is_test)->toBeFalse()
        ->and($request->fresh()->matched_count)->toBeGreaterThan(0);
});

it('lets the secret code through the postal code check', function () {
    Settings::set('features.test_postal_code', '00000');

    // It names no place on purpose, so the ordinary rule would reject it — and
    // then the form the test exists to try could never be submitted.
    $this->post('/anfrage', [
        'service_type_id' => $this->type->id,
        'postal_code' => '00000',
        'customer_name' => 'Test Tester',
        'customer_phone' => '0211 1234567',
        'customer_email' => 'test@dkgz.de',
    ])->assertSessionHasNoErrors();

    expect(ServiceRequest::where('is_test', true)->count())->toBe(1);
});

it('still rejects a postal code that is simply wrong', function () {
    Settings::set('features.test_postal_code', '00000');

    $this->post('/anfrage', [
        'service_type_id' => $this->type->id,
        'postal_code' => '99998',
        'customer_name' => 'Test Tester',
        'customer_phone' => '0211 1234567',
        'customer_email' => 'test@dkgz.de',
    ])->assertSessionHasErrors('postal_code');
});

it('never raises a task for a test', function () {
    Settings::set('features.test_postal_code', '00000');

    submit('00000');

    // It is unmatched on purpose, so without this it would raise "kein Partner
    // im PLZ-Gebiet" the moment it was submitted and never stop.
    expect(collect(AttentionQueue::items(PHP_INT_MAX)))->toBeEmpty();
});

it('says so in the request list', function () {
    Settings::set('features.test_postal_code', '00000');
    submit('00000');

    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/anfragen')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('requests.data.0', fn ($row) => $row['is_test'] === true
            && $row['needs_attention'] === false));

    expect(file_get_contents(resource_path('js/Pages/Admin/Anfragen.vue')))
        ->toContain('row.is_test');
});
