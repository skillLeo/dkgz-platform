<?php

namespace App\Actions;

use App\Jobs\NotifyAssignmentCompletedJob;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\CustomerReview;
use App\Models\ServiceRequest;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

/**
 * Completion and the DKGZ fee in one transaction.
 *
 * What DKGZ is owed was decided when the partner accepted and snapshotted onto
 * the assignment then, so editing the price list afterwards can never rewrite
 * a job that is already running. That snapshot is the only figure this reads.
 */
class CompleteAssignmentAction
{
    /**
     * Completes an assignment and books the DKGZ fee.
     *
     * Completion used to require the report and the customer's invoice on file
     * and the fee the assessor charged. None of that is asked for any more: the
     * assessor confirms the report is finished and the job closes. What DKGZ is
     * owed was fixed the moment the partner accepted and snapshotted then, so
     * nothing here depends on a figure the assessor types.
     */
    public function execute(Assignment $assignment, ?string $notes = null): Commission
    {
        $commission = DB::transaction(function () use ($assignment, $notes) {
            $previousStatus = $assignment->status;

            $assignment->update([
                'status' => Assignment::STATUS_COMPLETED,
                'completed_at' => now(),
                'assessor_notes' => $notes ?? $assignment->assessor_notes,
            ]);

            $assignment->recordStatusEvent(
                $previousStatus,
                Assignment::STATUS_COMPLETED,
                'assessor',
                $assignment->assessor->user_id,
            );

            $assignment->serviceRequest->update(['status' => ServiceRequest::STATUS_COMPLETED]);

            // The amount owed was decided at acceptance. Falling back to the
            // service type's current fee only covers assignments accepted
            // before snapshotting existed.
            $dkgzFee = $assignment->dkgz_fee_snapshot_cents
                ?? $assignment->serviceRequest?->serviceType?->dkgz_fee_cents
                ?? 0;

            $commission = Commission::firstOrCreate(
                ['assignment_id' => $assignment->id],
                [
                    'assessor_id' => $assignment->assessor_id,
                    'fee_type' => Commission::TYPE_FIXED,
                    'dkgz_fee_cents' => $dkgzFee,
                    'commission_cents' => $dkgzFee,
                    'rate_percent' => null,
                    'status' => Commission::STATUS_OPEN,
                ]
            );

            if (Settings::bool('features.review_flow', true)) {
                CustomerReview::firstOrCreate(
                    ['assignment_id' => $assignment->id],
                    [
                        'token' => CustomerReview::generateToken(),
                        'expires_at' => now()->addDays(30),
                    ]
                );
            }

            return $commission;
        });

        // Outside the transaction: a queue write must never be able to roll the
        // completion back.
        NotifyAssignmentCompletedJob::dispatch($assignment->id);

        return $commission;
    }
}
