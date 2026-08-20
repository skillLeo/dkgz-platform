<?php

use App\Actions\DeclineRequestAction;
use App\Actions\MatchRequestAction;
use App\Console\Commands\ExpireLapsedRequestsCommand;
use App\Jobs\NotifyCustomerNoResponseJob;
use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use App\Models\EmailLog;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

function partnerFor(ServiceType $type): Assessor
{
    $user = User::factory()->create(['is_active' => true]);
    $assessor = Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_available' => true,
    ]);
    AssessorServiceArea::factory()->covering('40589')->create(['assessor_id' => $assessor->id]);
    $assessor->serviceTypes()->attach($type->id);

    return $assessor->fresh(['serviceAreas', 'serviceTypes', 'user']);
}

function openRequest(ServiceType $type): ServiceRequest
{
    $request = ServiceRequest::factory()->inPostalCode('40589')->create([
        'service_type_id' => $type->id,
        'reference' => ServiceRequest::nextReference(),
        'customer_email' => 'kundin@example.test',
        'customer_name' => 'Martina Reinhardt',
    ]);

    app(MatchRequestAction::class)->execute($request);

    return $request->fresh();
}

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    $this->type = ServiceType::factory()->create();
});

it('tells the customer when the acceptance deadline runs out', function () {
    $partner = partnerFor($this->type);
    $request = openRequest($this->type);
    $request->update(['accept_deadline_at' => now()->subMinute()]);

    $this->artisan(ExpireLapsedRequestsCommand::class)->assertSuccessful();
    (new NotifyCustomerNoResponseJob($request->id))->handle();

    expect($request->fresh()->status)->toBe(ServiceRequest::STATUS_EXPIRED)
        ->and($request->fresh()->customer_notified_at)->not->toBeNull()
        ->and(EmailLog::where('template_key', 'anfrage-keine-rueckmeldung')
            ->where('recipient', 'kundin@example.test')->count())->toBe(1);
});

it('tells the customer when the last matched partner declines', function () {
    $partner = partnerFor($this->type);
    $request = openRequest($this->type);

    app(DeclineRequestAction::class)->execute($request, $partner, 'Kein Termin frei');
    (new NotifyCustomerNoResponseJob($request->id))->handle();

    expect($request->fresh()->status)->toBe(ServiceRequest::STATUS_UNANSWERED)
        ->and(EmailLog::where('template_key', 'anfrage-keine-rueckmeldung')->count())->toBe(1);
});

it('leaves the request open while another partner has still not answered', function () {
    $first = partnerFor($this->type);
    $second = partnerFor($this->type);
    $request = openRequest($this->type);

    app(DeclineRequestAction::class)->execute($request, $first);

    expect($request->fresh()->status)->toBe(ServiceRequest::STATUS_MATCHED)
        ->and(EmailLog::where('template_key', 'anfrage-keine-rueckmeldung')->count())->toBe(0);
});

it('never tells a customer whose request was accepted', function () {
    $partner = partnerFor($this->type);
    $request = openRequest($this->type);
    $request->update(['status' => ServiceRequest::STATUS_ASSIGNED]);

    (new NotifyCustomerNoResponseJob($request->id))->handle();

    expect(EmailLog::where('template_key', 'anfrage-keine-rueckmeldung')->count())->toBe(0);
});

it('never sends the same customer mail twice', function () {
    $partner = partnerFor($this->type);
    $request = openRequest($this->type);
    $request->update(['status' => ServiceRequest::STATUS_UNANSWERED]);

    (new NotifyCustomerNoResponseJob($request->id))->handle();
    (new NotifyCustomerNoResponseJob($request->id))->handle();

    expect(EmailLog::where('template_key', 'anfrage-keine-rueckmeldung')->count())->toBe(1);
});
