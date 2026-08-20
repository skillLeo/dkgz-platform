<?php

use App\Actions\MatchRequestAction;
use App\Console\Commands\ExpireLapsedRequestsCommand;
use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings;

/** A request that has been matched to one qualifying assessor. */
function matchedRequest(): ServiceRequest
{
    $type = ServiceType::factory()->create();

    $user = User::factory()->create(['is_active' => true]);
    $assessor = Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_available' => true,
    ]);
    AssessorServiceArea::factory()->covering('40589')->create(['assessor_id' => $assessor->id]);
    $assessor->serviceTypes()->attach($type->id);

    $request = ServiceRequest::factory()->inPostalCode('40589')->create([
        'service_type_id' => $type->id,
        'reference' => ServiceRequest::nextReference(),
    ]);

    app(MatchRequestAction::class)->execute($request);

    return $request->fresh();
}

describe('the acceptance deadline', function () {
    it('is set from the configured window when a request is matched', function () {
        Setting::create([
            'group' => 'business',
            'key' => 'business.request_expiry_hours',
            'type' => 'integer',
            'value' => '6',
            'label_de' => 'Frist zur Annahme in Stunden',
        ]);
        Settings::flush();

        $request = matchedRequest();

        expect($request->accept_deadline_at)->not->toBeNull()
            ->and($request->accept_deadline_at->timestamp)
            ->toEqualWithDelta(now()->addHours(6)->timestamp, 5);
    });

    it('expires a request nobody accepted and closes its pending matches', function () {
        $request = matchedRequest();
        $request->update(['accept_deadline_at' => now()->subMinute()]);

        $this->artisan(ExpireLapsedRequestsCommand::class)->assertSuccessful();

        expect($request->fresh()->status)->toBe(ServiceRequest::STATUS_EXPIRED)
            ->and(RequestMatch::where('service_request_id', $request->id)->pending()->count())->toBe(0)
            ->and(RequestMatch::where('service_request_id', $request->id)
                ->where('outcome', RequestMatch::OUTCOME_EXPIRED)->count())->toBeGreaterThan(0);
    });

    it('leaves a request alone while the deadline is still ahead', function () {
        $request = matchedRequest();
        $request->update(['accept_deadline_at' => now()->addHour()]);

        $this->artisan(ExpireLapsedRequestsCommand::class)->assertSuccessful();

        expect($request->fresh()->status)->toBe(ServiceRequest::STATUS_MATCHED);
    });

    it('never expires a request that was already accepted', function () {
        $request = matchedRequest();
        $request->update([
            'status' => ServiceRequest::STATUS_ASSIGNED,
            'accept_deadline_at' => now()->subDay(),
        ]);

        $this->artisan(ExpireLapsedRequestsCommand::class)->assertSuccessful();

        expect($request->fresh()->status)->toBe(ServiceRequest::STATUS_ASSIGNED);
    });
});
