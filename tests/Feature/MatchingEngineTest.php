<?php

use App\Actions\AcceptAssignmentAction;
use App\Actions\DeclineRequestAction;
use App\Actions\MatchRequestAction;
use App\Exceptions\RequestAlreadyAssignedException;
use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use App\Models\Assignment;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\QueryException;

/** An assessor that provably matches: approved, available, in area, offering. */
function matchableAssessor(ServiceType $type, string $postalCode = '40589'): Assessor
{
    $user = User::factory()->create(['is_active' => true]);
    $assessor = Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_available' => true,
    ]);

    AssessorServiceArea::factory()->covering($postalCode)->create(['assessor_id' => $assessor->id]);
    $assessor->serviceTypes()->attach($type->id);

    return $assessor->fresh(['serviceAreas', 'serviceTypes', 'user']);
}

function matchableRequest(ServiceType $type, string $postalCode = '40589'): ServiceRequest
{
    return ServiceRequest::factory()->inPostalCode($postalCode)->create([
        'service_type_id' => $type->id,
        'reference' => ServiceRequest::nextReference(),
    ]);
}

beforeEach(function () {
    $this->type = ServiceType::factory()->create();
});

describe('the matching rule', function () {
    it('forwards to an assessor that meets every condition', function () {
        $assessor = matchableAssessor($this->type);
        $request = matchableRequest($this->type);

        $count = app(MatchRequestAction::class)->execute($request);

        expect($count)->toBe(1)
            ->and($request->fresh()->status)->toBe(ServiceRequest::STATUS_MATCHED)
            ->and($request->fresh()->matched_count)->toBe(1)
            ->and(RequestMatch::where('assessor_id', $assessor->id)->exists())->toBeTrue();
    });

    it('skips an assessor that is not approved', function () {
        $assessor = matchableAssessor($this->type);
        $assessor->update(['approval_status' => Assessor::STATUS_PENDING]);

        expect(app(MatchRequestAction::class)->execute(matchableRequest($this->type)))->toBe(0);
    });

    it('skips an assessor that is unavailable', function () {
        matchableAssessor($this->type)->update(['is_available' => false]);

        expect(app(MatchRequestAction::class)->execute(matchableRequest($this->type)))->toBe(0);
    });

    it('skips an assessor whose user account is deactivated', function () {
        $assessor = matchableAssessor($this->type);
        $assessor->user->update(['is_active' => false]);

        expect(app(MatchRequestAction::class)->execute(matchableRequest($this->type)))->toBe(0);
    });

    it('skips an assessor whose area does not cover the postal code', function () {
        $assessor = matchableAssessor($this->type);
        $assessor->serviceAreas()->delete();
        AssessorServiceArea::factory()->excluding('40589')->create(['assessor_id' => $assessor->id]);

        expect(app(MatchRequestAction::class)->execute(matchableRequest($this->type)))->toBe(0);
    });

    it('skips an assessor that does not offer the requested service', function () {
        $assessor = matchableAssessor($this->type);
        $assessor->serviceTypes()->detach();

        expect(app(MatchRequestAction::class)->execute(matchableRequest($this->type)))->toBe(0);
    });

    it('matches on the numeric boundaries of the range, inclusive', function () {
        $assessor = matchableAssessor($this->type);
        $assessor->serviceAreas()->delete();
        $assessor->serviceAreas()->create(['postal_code_from' => '40000', 'postal_code_to' => '40999']);

        foreach (['40000', '40500', '40999'] as $inside) {
            expect(app(MatchRequestAction::class)->execute(matchableRequest($this->type, $inside)))
                ->toBe(1, "PLZ {$inside} sollte im Gebiet liegen.");
        }

        foreach (['39999', '41000'] as $outside) {
            expect(app(MatchRequestAction::class)->execute(matchableRequest($this->type, $outside)))
                ->toBe(0, "PLZ {$outside} sollte außerhalb liegen.");
        }
    });

    it('forwards to every matching assessor at once', function () {
        foreach (range(1, 4) as $ignored) {
            matchableAssessor($this->type);
        }

        $request = matchableRequest($this->type);

        expect(app(MatchRequestAction::class)->execute($request))->toBe(4)
            ->and($request->fresh()->matched_count)->toBe(4);
    });

    it('never drops a request that matches nobody', function () {
        $request = matchableRequest($this->type);

        expect(app(MatchRequestAction::class)->execute($request))->toBe(0);

        $request->refresh();

        expect($request->status)->toBe(ServiceRequest::STATUS_NEW)
            ->and($request->isUnmatched())->toBeTrue()
            ->and(ServiceRequest::needsAttention()->pluck('id'))->toContain($request->id);
    });
});

describe('first-accept-wins', function () {
    it('gives the assignment to the accepting assessor and closes the rest', function () {
        $winner = matchableAssessor($this->type);
        $loserA = matchableAssessor($this->type);
        $loserB = matchableAssessor($this->type);

        $request = matchableRequest($this->type);
        app(MatchRequestAction::class)->execute($request);

        $assignment = app(AcceptAssignmentAction::class)->execute($request, $winner);

        expect($assignment->assessor_id)->toBe($winner->id)
            ->and($request->fresh()->status)->toBe(ServiceRequest::STATUS_ASSIGNED)
            ->and($request->fresh()->assigned_at)->not->toBeNull();

        $outcomes = RequestMatch::where('service_request_id', $request->id)->pluck('outcome', 'assessor_id');

        expect($outcomes[$winner->id])->toBe(RequestMatch::OUTCOME_ACCEPTED)
            ->and($outcomes[$loserA->id])->toBe(RequestMatch::OUTCOME_CLOSED)
            ->and($outcomes[$loserB->id])->toBe(RequestMatch::OUTCOME_CLOSED);
    });

    it('records the acceptance on the timeline', function () {
        $assessor = matchableAssessor($this->type);
        $request = matchableRequest($this->type);
        app(MatchRequestAction::class)->execute($request);

        $assignment = app(AcceptAssignmentAction::class)->execute($request, $assessor);

        expect($assignment->statusEvents()->count())->toBe(1)
            ->and($assignment->statusEvents()->first()->to_status)->toBe(Assignment::STATUS_ACCEPTED);
    });

    it('turns a second acceptance into the calm German message', function () {
        $first = matchableAssessor($this->type);
        $second = matchableAssessor($this->type);

        $request = matchableRequest($this->type);
        app(MatchRequestAction::class)->execute($request);

        app(AcceptAssignmentAction::class)->execute($request, $first);

        expect(fn () => app(AcceptAssignmentAction::class)->execute($request, $second))
            ->toThrow(
                RequestAlreadyAssignedException::class,
                'Dieser Auftrag wurde bereits von einem anderen Sachverständigen übernommen.'
            );
    });

    /**
     * The mandatory concurrency test. Both assessors are handed the request in
     * the 'matched' state and both call accept without either seeing the
     * other's write first — exactly one assignment must exist afterwards.
     */
    it('produces exactly one assignment when two accept simultaneously', function () {
        $a = matchableAssessor($this->type);
        $b = matchableAssessor($this->type);

        $request = matchableRequest($this->type);
        app(MatchRequestAction::class)->execute($request);

        // Both hold a model instance captured before either wrote, which is the
        // in-process equivalent of two requests arriving in the same millisecond.
        $snapshotForA = ServiceRequest::find($request->id);
        $snapshotForB = ServiceRequest::find($request->id);

        $action = app(AcceptAssignmentAction::class);
        $results = [];

        foreach ([[$snapshotForA, $a], [$snapshotForB, $b]] as [$snapshot, $assessor]) {
            try {
                $results[] = ['ok', $action->execute($snapshot, $assessor)->id];
            } catch (RequestAlreadyAssignedException $e) {
                $results[] = ['rejected', $e->getMessage()];
            }
        }

        expect(Assignment::where('service_request_id', $request->id)->count())->toBe(1)
            ->and(collect($results)->where(0, 'ok'))->toHaveCount(1)
            ->and(collect($results)->where(0, 'rejected'))->toHaveCount(1);

        expect(RequestMatch::where('service_request_id', $request->id)
            ->where('outcome', RequestMatch::OUTCOME_ACCEPTED)->count())->toBe(1);
    });

    it('is protected by a unique index even if the lock is bypassed', function () {
        $a = matchableAssessor($this->type);
        $b = matchableAssessor($this->type);
        $request = matchableRequest($this->type);
        app(MatchRequestAction::class)->execute($request);

        Assignment::create([
            'service_request_id' => $request->id,
            'assessor_id' => $a->id,
            'status' => Assignment::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        // The request row still says 'matched', so the status guard cannot help
        // here — only the unique index can.
        expect(fn () => Assignment::create([
            'service_request_id' => $request->id,
            'assessor_id' => $b->id,
            'status' => Assignment::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]))->toThrow(QueryException::class);
    });
});

describe('declining', function () {
    it('closes only the declining assessor and leaves the request open', function () {
        $decliner = matchableAssessor($this->type);
        $other = matchableAssessor($this->type);

        $request = matchableRequest($this->type);
        app(MatchRequestAction::class)->execute($request);

        app(DeclineRequestAction::class)->execute($request, $decliner, 'Terminlich nicht darstellbar');

        $outcomes = RequestMatch::where('service_request_id', $request->id)->pluck('outcome', 'assessor_id');

        expect($outcomes[$decliner->id])->toBe(RequestMatch::OUTCOME_DECLINED)
            ->and($outcomes[$other->id])->toBe(RequestMatch::OUTCOME_PENDING)
            ->and($request->fresh()->status)->toBe(ServiceRequest::STATUS_MATCHED);
    });

    it('flags the request for admin attention once everyone has declined', function () {
        $a = matchableAssessor($this->type);
        $b = matchableAssessor($this->type);

        $request = matchableRequest($this->type);
        app(MatchRequestAction::class)->execute($request);

        app(DeclineRequestAction::class)->execute($request, $a);
        app(DeclineRequestAction::class)->execute($request, $b);

        expect($request->fresh()->isFullyDeclined())->toBeTrue()
            ->and(ServiceRequest::needsAttention()->pluck('id'))->toContain($request->id);
    });
});

describe('lapsed liability cover', function () {
    it('takes a partner out of matching once their cover has run out', function () {
        $assessor = matchableAssessor($this->type);
        $assessor->documents()->create([
            'type' => 'liability',
            'path' => 'nachweise/haftpflicht.pdf',
            'original_name' => 'haftpflicht.pdf',
            'size_bytes' => 1000,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now()->subYear(),
            'valid_until' => now()->subDay(),
        ]);

        $request = matchableRequest($this->type);

        expect(app(MatchRequestAction::class)->execute($request))->toBe(0)
            ->and($assessor->fresh()->isMatchable())->toBeFalse();
    });

    it('keeps a partner whose cover is still valid', function () {
        $assessor = matchableAssessor($this->type);
        $assessor->documents()->create([
            'type' => 'liability',
            'path' => 'nachweise/haftpflicht.pdf',
            'original_name' => 'haftpflicht.pdf',
            'size_bytes' => 1000,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
            'valid_until' => now()->addMonth(),
        ]);

        expect(app(MatchRequestAction::class)->execute(matchableRequest($this->type)))->toBe(1)
            ->and($assessor->fresh()->isMatchable())->toBeTrue();
    });

    it('keeps a partner who has no dated cover on file at all', function () {
        matchableAssessor($this->type);

        expect(app(MatchRequestAction::class)->execute(matchableRequest($this->type)))->toBe(1);
    });

    it('keeps a partner whose lapsed cover was replaced by a current one', function () {
        $assessor = matchableAssessor($this->type);

        foreach ([now()->subDay(), now()->addYear()] as $validUntil) {
            $assessor->documents()->create([
                'type' => 'liability',
                'path' => 'nachweise/haftpflicht-'.$validUntil->timestamp.'.pdf',
                'original_name' => 'haftpflicht.pdf',
                'size_bytes' => 1000,
                'mime_type' => 'application/pdf',
                'uploaded_at' => now(),
                'valid_until' => $validUntil,
            ]);
        }

        expect(app(MatchRequestAction::class)->execute(matchableRequest($this->type)))->toBe(1)
            ->and($assessor->fresh()->isMatchable())->toBeTrue();
    });
});
