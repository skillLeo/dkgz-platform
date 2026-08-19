<?php

namespace App\Actions;

use App\Models\Assessor;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;

/**
 * Declining touches exactly one match row. The request stays open for everyone
 * else, and a decline carries no penalty in the distribution — the client was
 * explicit about that, and the partner page says so in writing.
 */
class DeclineRequestAction
{
    public function execute(ServiceRequest $request, Assessor $assessor, ?string $reason = null): RequestMatch
    {
        $match = RequestMatch::where('service_request_id', $request->id)
            ->where('assessor_id', $assessor->id)
            ->firstOrFail();

        if ($match->outcome === RequestMatch::OUTCOME_PENDING) {
            $match->update([
                'outcome' => RequestMatch::OUTCOME_DECLINED,
                'responded_at' => now(),
                'decline_reason' => $reason,
            ]);
        }

        return $match->fresh();
    }
}
