<?php

namespace App\Actions;

use App\Models\Assignment;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The assessor reports that the job did not come about after all.
 *
 * Accepting a request only says the assessor wants it; the customer still has
 * to engage them, and sometimes does not. Recording that honestly matters more
 * than it looks: it releases the request so somebody else can take it, and it
 * is the only way to tell a job that quietly died from one that was settled
 * privately outside the platform — which is what the reasons are for.
 *
 * No fee is booked and no invoice goes out. Nothing was earned.
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

        return DB::transaction(function () use ($assignment, $reason, $note) {
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

            // Back into placement rather than closed: the customer still wants
            // an assessment, and the office can send it out again.
            $assignment->serviceRequest?->update([
                'status' => ServiceRequest::STATUS_NEW,
                'assigned_at' => null,
            ]);

            return $assignment;
        });
    }
}
