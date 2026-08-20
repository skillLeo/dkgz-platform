<?php

namespace App\Actions;

use App\Jobs\NotifyCustomerNoResponseJob;
use App\Jobs\NotifyNoAssessorsFoundJob;
use App\Models\Assessor;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;

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

            $this->closeIfNobodyLeft($request);
        }

        return $match->fresh();
    }

    /**
     * The moment the last matched partner declines, the request is finished
     * without an assignment. It becomes 'unanswered' rather than 'expired':
     * everyone answered, they simply all said no, which is a coverage problem
     * rather than a speed problem and needs a different response from the office.
     */
    private function closeIfNobodyLeft(ServiceRequest $request): void
    {
        DB::transaction(function () use ($request) {
            $locked = ServiceRequest::whereKey($request->id)->lockForUpdate()->first();

            if ($locked === null || $locked->status !== ServiceRequest::STATUS_MATCHED) {
                return;
            }

            $stillOpen = RequestMatch::where('service_request_id', $locked->id)
                ->pending()
                ->exists();

            if ($stillOpen) {
                return;
            }

            $locked->update(['status' => ServiceRequest::STATUS_UNANSWERED]);

            NotifyNoAssessorsFoundJob::dispatch($locked->id);
            NotifyCustomerNoResponseJob::dispatch($locked->id);
        });
    }
}
