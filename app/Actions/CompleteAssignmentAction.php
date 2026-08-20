<?php

namespace App\Actions;

use App\Jobs\NotifyAssignmentCompletedJob;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\CustomerReview;
use App\Models\ServiceRequest;
use App\Support\Money;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Completion, fee capture and commission calculation in one transaction.
 *
 * Two rules are enforced here rather than in a controller, so no caller can
 * route around them:
 *   · completion is blocked unless the report and the customer invoice are both
 *     on file — the client's fraud control;
 *   · the commission rate is read from settings once and snapshotted onto the
 *     row, so editing the rate later never rewrites history.
 */
class CompleteAssignmentAction
{
    /**
     * Completes an assignment and books the DKGZ fee.
     *
     * The fee the assessor charged their customer is now optional record-keeping
     * — it drives no calculation. What DKGZ is owed was fixed the moment the
     * partner accepted, and was snapshotted onto the assignment then.
     */
    public function execute(Assignment $assignment, ?int $feeCents = null, ?string $notes = null): Commission
    {
        if (! $assignment->hasRequiredDocuments()) {
            throw new RuntimeException(
                'Der Auftrag kann erst abgeschlossen werden, wenn Gutachten und Rechnung hinterlegt sind.'
            );
        }

        if ($feeCents !== null && ! Money::isValidFee($feeCents)) {
            throw new RuntimeException('Das eingegebene Honorar liegt außerhalb des zulässigen Bereichs.');
        }

        $commission = DB::transaction(function () use ($assignment, $feeCents, $notes) {
            $previousStatus = $assignment->status;

            $assignment->update([
                'status' => Assignment::STATUS_COMPLETED,
                'completed_at' => now(),
                'fee_cents' => $feeCents ?? $assignment->fee_cents,
                'fee_entered_at' => $feeCents === null ? $assignment->fee_entered_at : now(),
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

            $commission = Commission::updateOrCreate(
                ['assignment_id' => $assignment->id],
                [
                    'assessor_id' => $assignment->assessor_id,
                    'fee_type' => Commission::TYPE_FIXED,
                    'dkgz_fee_cents' => $dkgzFee,
                    'commission_cents' => $dkgzFee,
                    'fee_cents' => $feeCents ?? $assignment->fee_cents,
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
