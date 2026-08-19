<?php

use App\Actions\CompleteAssignmentAction;
use App\Models\Assignment;
use App\Models\AssignmentDocument;
use App\Models\Commission;
use App\Models\ServiceRequest;
use App\Support\Money;
use App\Support\Settings;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
});

function assignmentReadyToComplete(): Assignment
{
    $assignment = Assignment::factory()->create(['status' => Assignment::STATUS_IN_PROGRESS]);

    AssignmentDocument::factory()->report()->create(['assignment_id' => $assignment->id]);
    AssignmentDocument::factory()->customerInvoice()->create(['assignment_id' => $assignment->id]);

    return $assignment->fresh(['documents', 'assessor', 'serviceRequest']);
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

        expect($commission->fee_cents)->toBe(85_000)
            ->and($commission->commission_cents)->toBe(12_750)
            ->and((float) $commission->rate_percent)->toBe(15.0)
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

describe('rate snapshotting', function () {
    it('reads the rate from settings rather than a constant', function () {
        Settings::set('business.commission_rate', '12.5');

        $commission = app(CompleteAssignmentAction::class)->execute(assignmentReadyToComplete(), 85_000);

        expect((float) $commission->rate_percent)->toBe(12.5)
            ->and($commission->commission_cents)->toBe(10_625);
    });

    it('never rewrites a historical commission when the rate later changes', function () {
        $first = app(CompleteAssignmentAction::class)->execute(assignmentReadyToComplete(), 85_000);

        expect((float) $first->rate_percent)->toBe(15.0)
            ->and($first->commission_cents)->toBe(12_750);

        Settings::set('business.commission_rate', '25.0');

        $second = app(CompleteAssignmentAction::class)->execute(assignmentReadyToComplete(), 85_000);

        // The new record uses the new rate; the old one is untouched.
        expect((float) $second->rate_percent)->toBe(25.0)
            ->and($second->commission_cents)->toBe(21_250);

        $first->refresh();

        expect((float) $first->rate_percent)->toBe(15.0)
            ->and($first->commission_cents)->toBe(12_750);
    });

    it('has 15.00 as the seeded default', function () {
        expect(Settings::commissionRate())->toBe(15.0);
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
    it('runs sequentially within a year', function () {
        $year = (int) now()->format('Y');

        expect(ServiceRequest::nextReference($year))->toBe(sprintf('DKGZ-%d-00001', $year));

        ServiceRequest::factory()->create(['reference' => sprintf('DKGZ-%d-00001', $year)]);

        expect(ServiceRequest::nextReference($year))->toBe(sprintf('DKGZ-%d-00002', $year));
    });
});
