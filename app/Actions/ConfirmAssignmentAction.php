<?php

namespace App\Actions;

use App\Models\Assignment;
use App\Models\Commission;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The assessor confirms the job actually came about — "Zustande gekommen".
 *
 * Accepting a request only says the assessor wants it; the customer still has
 * to engage them. This is the moment the work is real, and it is therefore the
 * moment DKGZ has earned its fee. Until now that fee was only booked at
 * completion and only invoiced when an admin remembered to press a button, so
 * money sat unbilled for as long as a job took. Confirming now does all three
 * things at once: the job moves into "In Bearbeitung", the fee is booked, and
 * the invoice goes out.
 *
 * The amount is the one snapshotted at acceptance, never the current setting —
 * changing the price list must not rewrite what somebody already agreed to.
 */
class ConfirmAssignmentAction
{
    public function execute(Assignment $assignment, ?string $note = null): Commission
    {
        if ($assignment->status !== Assignment::STATUS_ACCEPTED) {
            throw new RuntimeException(
                'Dieser Auftrag wurde bereits bestätigt oder befindet sich nicht mehr im Status "Angenommen".'
            );
        }

        $commission = DB::transaction(function () use ($assignment, $note) {
            $previous = $assignment->status;

            $assignment->update([
                'status' => Assignment::STATUS_IN_PROGRESS,
                'started_at' => $assignment->started_at ?? now(),
                'confirmed_at' => now(),
            ]);

            $assignment->recordStatusEvent(
                $previous,
                Assignment::STATUS_IN_PROGRESS,
                'assessor',
                $assignment->assessor->user_id,
                $note,
            );

            $dkgzFee = $assignment->dkgz_fee_snapshot_cents
                ?? $assignment->serviceRequest?->serviceType?->dkgz_fee_cents
                ?? 0;

            return Commission::firstOrCreate(
                ['assignment_id' => $assignment->id],
                [
                    'assessor_id' => $assignment->assessor_id,
                    'fee_type' => Commission::TYPE_FIXED,
                    'dkgz_fee_cents' => $dkgzFee,
                    'commission_cents' => $dkgzFee,
                    'fee_cents' => $assignment->fee_cents,
                    'rate_percent' => null,
                    'status' => Commission::STATUS_OPEN,
                ]
            );
        });

        // Outside the transaction: a PDF write and a queue push must never be
        // able to roll back a confirmation the assessor already saw succeed.
        return app(IssueCommissionInvoiceAction::class)->execute($commission);
    }
}
