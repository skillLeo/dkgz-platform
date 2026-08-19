<?php

namespace App\Actions;

use App\Exceptions\RequestAlreadyAssignedException;
use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * First-accept-wins. The most important code in the project.
 *
 * Two assessors clicking Accept in the same millisecond must never both get the
 * assignment. Two independent guarantees stand behind that:
 *
 *   1. A pessimistic row lock on the service request inside a transaction, so
 *      the second transaction blocks until the first commits and then sees the
 *      status has moved off 'matched'.
 *   2. The UNIQUE index on assignments.service_request_id, which turns any race
 *      that slips past the lock into an integrity violation we convert into the
 *      same exception.
 *
 * SQLite does not implement SELECT … FOR UPDATE, so on that driver the unique
 * index is what actually holds the line — which is precisely why it exists.
 */
class AcceptAssignmentAction
{
    public function execute(ServiceRequest $request, Assessor $assessor): Assignment
    {
        try {
            return DB::transaction(function () use ($request, $assessor) {
                $locked = ServiceRequest::whereKey($request->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status !== ServiceRequest::STATUS_MATCHED) {
                    throw new RequestAlreadyAssignedException;
                }

                $assignment = Assignment::create([
                    'service_request_id' => $locked->id,
                    'assessor_id' => $assessor->id,
                    'status' => Assignment::STATUS_ACCEPTED,
                    'accepted_at' => now(),
                ]);

                $locked->update([
                    'status' => ServiceRequest::STATUS_ASSIGNED,
                    'assigned_at' => now(),
                ]);

                RequestMatch::where('service_request_id', $locked->id)
                    ->where('assessor_id', $assessor->id)
                    ->update([
                        'outcome' => RequestMatch::OUTCOME_ACCEPTED,
                        'responded_at' => now(),
                    ]);

                // Everyone else who was still deciding is closed out in one
                // statement, so the request disappears from their queue.
                RequestMatch::where('service_request_id', $locked->id)
                    ->where('assessor_id', '!=', $assessor->id)
                    ->where('outcome', RequestMatch::OUTCOME_PENDING)
                    ->update([
                        'outcome' => RequestMatch::OUTCOME_CLOSED,
                        'responded_at' => now(),
                    ]);

                $assignment->recordStatusEvent(
                    null,
                    Assignment::STATUS_ACCEPTED,
                    'assessor',
                    $assessor->user_id,
                );

                return $assignment;
            });
        } catch (QueryException $e) {
            // Second line of defence: the unique index fired.
            if ($this->isUniqueViolation($e)) {
                throw new RequestAlreadyAssignedException;
            }

            throw $e;
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        // 23000/23505 across MySQL and Postgres; SQLite reports 23000 too.
        return in_array($e->getCode(), ['23000', '23505'], true)
            || str_contains(strtolower($e->getMessage()), 'unique');
    }
}
