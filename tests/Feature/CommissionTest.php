<?php

use App\Actions\CompleteAssignmentAction;
use App\Models\Assignment;
use App\Models\AssignmentDocument;
use App\Models\Commission;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Support\Money;
use App\Support\Settings;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
});

function assignmentReadyToComplete(?ServiceType $type = null): Assignment
{
    $type ??= ServiceType::factory()->create(['dkgz_fee_cents' => 7_900]);

    $request = ServiceRequest::factory()->create([
        'service_type_id' => $type->id,
        'reference' => ServiceRequest::nextReference(),
    ]);

    $assignment = Assignment::factory()->create([
        'service_request_id' => $request->id,
        'status' => Assignment::STATUS_IN_PROGRESS,
        // Snapshotted at acceptance in the real flow.
        'dkgz_fee_snapshot_cents' => $type->dkgz_fee_cents,
    ]);

    AssignmentDocument::factory()->report()->create(['assignment_id' => $assignment->id]);
    AssignmentDocument::factory()->customerInvoice()->create(['assignment_id' => $assignment->id]);

    return $assignment->fresh(['documents', 'assessor', 'serviceRequest.serviceType']);
}

describe('the arithmetic', function () {
    it('rounds to the nearest cent across a spread of fees', function (int $fee, float $rate, int $expected) {
        expect(Commission::calculateCents($fee, $rate))->toBe($expected);
    })->with([
        // The worked example from the client's brief: 850,00 € at 15 % = 127,50 €
        [85_000, 15.0, 12_750],
        [164_000, 15.0, 24_600],
        [100_000, 15.0, 15_000],
        [5_000, 15.0, 750],
        [5_000_000, 15.0, 750_000],
        // Rounding: 333,33 € at 15 % is 49,9995 € → 50,00 €
        [33_333, 15.0, 5_000],
        [1, 15.0, 0],
        [7, 15.0, 1],
        [85_000, 12.5, 10_625],
        [85_000, 20.0, 17_000],
        [85_000, 0.0, 0],
    ]);

    it('never produces a commission larger than the fee', function () {
        foreach ([5_000, 85_000, 164_000, 999_999, 5_000_000] as $fee) {
            expect(Commission::calculateCents($fee, 15.0))->toBeLessThan($fee);
        }
    });

    it('leaves the assessor the remainder exactly', function () {
        $commission = Commission::factory()->create([
            'fee_cents' => 85_000,
            'rate_percent' => 15.0,
            'commission_cents' => Commission::calculateCents(85_000, 15.0),
        ]);

        expect($commission->assessorShareCents())->toBe(72_250);
    });
});

describe('completion', function () {
    it('is blocked without both documents', function () {
        $assignment = Assignment::factory()->create();

        expect(fn () => app(CompleteAssignmentAction::class)->execute($assignment, 85_000))
            ->toThrow(RuntimeException::class, 'Gutachten und Rechnung');
    });

    it('is blocked with only the report', function () {
        $assignment = Assignment::factory()->create();
        AssignmentDocument::factory()->report()->create(['assignment_id' => $assignment->id]);

        expect(fn () => app(CompleteAssignmentAction::class)->execute($assignment->fresh(), 85_000))
            ->toThrow(RuntimeException::class);
    });

    it('is blocked with only the invoice', function () {
        $assignment = Assignment::factory()->create();
        AssignmentDocument::factory()->customerInvoice()->create(['assignment_id' => $assignment->id]);

        expect(fn () => app(CompleteAssignmentAction::class)->execute($assignment->fresh(), 85_000))
            ->toThrow(RuntimeException::class);
    });

    it('succeeds once both are on file and writes the commission', function () {
        $assignment = assignmentReadyToComplete();

        $commission = app(CompleteAssignmentAction::class)->execute($assignment, 85_000);

        // Superseded by the client's change request: DKGZ now charges a fixed
        // fee per assessment type, not a share of the assessor's own invoice.
        expect($commission->fee_cents)->toBe(85_000)
            ->and($commission->fee_type)->toBe(Commission::TYPE_FIXED)
            ->and($commission->commission_cents)->toBe($assignment->fresh()->dkgz_fee_snapshot_cents ?? 0)
            ->and($commission->rate_percent)->toBeNull()
            ->and($commission->status)->toBe(Commission::STATUS_OPEN);

        $assignment->refresh();

        expect($assignment->status)->toBe(Assignment::STATUS_COMPLETED)
            ->and($assignment->fee_cents)->toBe(85_000)
            ->and($assignment->completed_at)->not->toBeNull()
            ->and($assignment->serviceRequest->fresh()->status)->toBe(ServiceRequest::STATUS_COMPLETED);
    });

    it('records the completion on the timeline', function () {
        $assignment = assignmentReadyToComplete();

        app(CompleteAssignmentAction::class)->execute($assignment, 85_000);

        expect($assignment->statusEvents()->where('to_status', Assignment::STATUS_COMPLETED)->exists())->toBeTrue();
    });

    it('opens a review token when the review flow is on', function () {
        $assignment = assignmentReadyToComplete();

        app(CompleteAssignmentAction::class)->execute($assignment, 85_000);

        expect($assignment->fresh()->review)->not->toBeNull()
            ->and(strlen($assignment->fresh()->review->token))->toBe(64);
    });

    it('opens no review token when the flow is off', function () {
        Settings::set('features.review_flow', false);
        $assignment = assignmentReadyToComplete();

        app(CompleteAssignmentAction::class)->execute($assignment, 85_000);

        expect($assignment->fresh()->review)->toBeNull();
    });

    it('refuses a fee below the floor and above the ceiling', function () {
        $assignment = assignmentReadyToComplete();

        expect(fn () => app(CompleteAssignmentAction::class)->execute($assignment, Money::MIN_FEE_CENTS - 1))
            ->toThrow(RuntimeException::class)
            ->and(fn () => app(CompleteAssignmentAction::class)->execute($assignment, Money::MAX_FEE_CENTS + 1))
            ->toThrow(RuntimeException::class);
    });

    it('accepts an unusually large fee but flags it for review', function () {
        $assignment = assignmentReadyToComplete();

        $commission = app(CompleteAssignmentAction::class)->execute($assignment, 2_000_000);

        expect($commission->needsReview())->toBeTrue();
    });
});

describe('fixed-fee snapshotting', function () {
    it('books the service type fee that was in force when the partner accepted', function () {
        $type = ServiceType::factory()->create(['dkgz_fee_cents' => 7_900]);
        $assignment = assignmentReadyToComplete($type);

        $commission = app(CompleteAssignmentAction::class)->execute($assignment, 85_000);

        expect($commission->fee_type)->toBe(Commission::TYPE_FIXED)
            ->and($commission->dkgz_fee_cents)->toBe(7_900)
            ->and($commission->commission_cents)->toBe(7_900);
    });

    it('keeps the accepted fee when the admin changes the service later', function () {
        $type = ServiceType::factory()->create(['dkgz_fee_cents' => 7_900]);
        $assignment = assignmentReadyToComplete($type);

        // The admin raises the fee after this assignment was already accepted.
        $type->update(['dkgz_fee_cents' => 12_900]);

        $commission = app(CompleteAssignmentAction::class)->execute($assignment->fresh(), 85_000);

        expect($commission->dkgz_fee_cents)->toBe(7_900);
    });

    it('completes without any fee being entered at all', function () {
        $type = ServiceType::factory()->create(['dkgz_fee_cents' => 4_900]);
        $assignment = assignmentReadyToComplete($type);

        $commission = app(CompleteAssignmentAction::class)->execute($assignment, null);

        expect($commission->commission_cents)->toBe(4_900)
            ->and($commission->fee_cents)->toBeNull();
    });

    it('leaves historical percentage commissions exactly as they were', function () {
        $legacy = Commission::factory()->create([
            'fee_type' => Commission::TYPE_PERCENTAGE,
            'fee_cents' => 85_000,
            'rate_percent' => 15.0,
            'commission_cents' => 12_750,
        ]);

        app(CompleteAssignmentAction::class)->execute(assignmentReadyToComplete(), 85_000);

        $legacy->refresh();

        expect($legacy->fee_type)->toBe(Commission::TYPE_PERCENTAGE)
            ->and((float) $legacy->rate_percent)->toBe(15.0)
            ->and($legacy->commission_cents)->toBe(12_750);
    });
});

describe('invoice numbering', function () {
    it('runs sequentially within a year', function () {
        expect(Commission::nextInvoiceNumber(2026))->toBe('DKGZ-RE-2026-0001');

        Commission::factory()->create(['invoice_number' => 'DKGZ-RE-2026-0001']);

        expect(Commission::nextInvoiceNumber(2026))->toBe('DKGZ-RE-2026-0002');
    });

    it('restarts each year', function () {
        Commission::factory()->create(['invoice_number' => 'DKGZ-RE-2026-0042']);

        expect(Commission::nextInvoiceNumber(2027))->toBe('DKGZ-RE-2027-0001');
    });
});

describe('reference numbering', function () {
    it('runs sequentially within the month', function () {
        expect(ServiceRequest::nextReference())->toBe('DKGZ'.now()->format('ym').'0001');

        ServiceRequest::factory()->create(['reference' => 'DKGZ'.now()->format('ym').'0001']);

        expect(ServiceRequest::nextReference())->toBe('DKGZ'.now()->format('ym').'0002');
    });

    it('restarts the sequence in a new month', function () {
        ServiceRequest::factory()->create(['reference' => 'DKGZ'.now()->format('ym').'0042']);

        expect(ServiceRequest::nextReference(now()->addMonthNoOverflow()))
            ->toBe('DKGZ'.now()->addMonthNoOverflow()->format('ym').'0001');
    });

    it('matches the format the client asked for', function () {
        expect(ServiceRequest::nextReference())->toMatch('/^DKGZ\d{4}\d{4}$/');
    });
});
