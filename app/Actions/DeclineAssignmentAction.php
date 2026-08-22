<?php

namespace App\Actions;

use App\Jobs\NotifyRequestCancelledJob;
use App\Models\Assignment;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The assessor reports that the job did not come about after all.
 *
 * Accepting a request only says the assessor wants it; the customer still has
 * to engage them, and sometimes does not. Recording that honestly matters more
 * than it looks: it is the only way to tell a job that quietly died from one
 * that was settled privately outside the platform, which is what the reasons
 * are for.
 *
 * The request is cancelled and the customer is told. No fee is booked and no
 * invoice goes out — nothing was earned.
 */
class DeclineAssignmentAction
{
    /** Why a confirmed assessment never happened. */
    public const REASONS = [
        'kunde_nicht_erreichbar' => 'Kunde war nicht erreichbar',
        'kunde_abgesagt' => 'Kunde hat abgesagt',
        'anderweitig_vergeben' => 'Kunde hat anderweitig vergeben',
        'fahrzeug_nicht_verfuegbar' => 'Fahrzeug stand nicht zur Verfügung',
        'kein_gutachten_noetig' => 'Ein Gutachten war doch nicht nötig',
        'sonstiges' => 'Anderer Grund',
    ];

    public function execute(Assignment $assignment, string $reason, ?string $note = null): Assignment
    {
        if ($assignment->status !== Assignment::STATUS_ACCEPTED) {
            throw new RuntimeException(
                'Dieser Auftrag lässt sich nicht mehr zurückgeben — er wurde bereits bestätigt oder abgeschlossen.'
            );
        }

        if (! array_key_exists($reason, self::REASONS)) {
            throw new RuntimeException('Bitte wählen Sie einen Grund aus.');
        }

        $assignment = DB::transaction(function () use ($assignment, $reason, $note) {
            $previous = $assignment->status;

            $assignment->update([
                'status' => Assignment::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => self::REASONS[$reason],
                'assessor_notes' => $note ?? $assignment->assessor_notes,
            ]);

            $assignment->recordStatusEvent(
                $previous,
                Assignment::STATUS_CANCELLED,
                'assessor',
                $assignment->assessor->user_id,
                $note,
            );

            // The request is closed, not quietly reopened. A customer whose
            // assessment fell through is owed an answer, and an office that
            // wants to place it again can rematch it deliberately.
            $assignment->serviceRequest?->update([
                'status' => ServiceRequest::STATUS_CANCELLED,
                'assigned_at' => null,
            ]);

            return $assignment;
        });

        // Outside the transaction: the customer is told what happened, because
        // silence after handing over their details is the worst outcome here.
        NotifyRequestCancelledJob::dispatch($assignment->service_request_id);

        return $assignment;
    }
}
