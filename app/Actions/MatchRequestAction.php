<?php

namespace App\Actions;

use App\Models\Assessor;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The matching engine. Runs synchronously on submission.
 *
 * The rule is exactly this and nothing more: approved, available, the user
 * account active, the postal code inside one of the assessor's ranges, and the
 * assessor offering that service type. No distance, no ranking, no scoring, no
 * prioritisation — the client excluded all of it deliberately, because DKGZ is
 * not a comparison portal.
 */
class MatchRequestAction
{
    /** @return int the number of assessors the request was forwarded to */
    public function execute(ServiceRequest $request): int
    {
        $assessorIds = $this->matchingAssessorIds($request);

        if ($assessorIds->isEmpty()) {
            // Never silently dropped: the request stays 'new' with a zero
            // count, which is what surfaces it under "Nicht vermittelt".
            $request->update([
                'status' => ServiceRequest::STATUS_NEW,
                'matched_count' => 0,
            ]);

            return 0;
        }

        DB::transaction(function () use ($request, $assessorIds) {
            $now = now();

            $rows = $assessorIds->map(fn (int $id) => [
                'service_request_id' => $request->id,
                'assessor_id' => $id,
                'outcome' => RequestMatch::OUTCOME_PENDING,
                'notified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            // Chunked because a broad postal range can match a great many
            // partners and the host may only allow 128M.
            foreach (array_chunk($rows, 200) as $chunk) {
                RequestMatch::insertOrIgnore($chunk);
            }

            $request->update([
                'status' => ServiceRequest::STATUS_MATCHED,
                'matched_count' => count($rows),
            ]);
        });

        return $assessorIds->count();
    }

    /**
     * @return Collection<int, int>
     */
    public function matchingAssessorIds(ServiceRequest $request)
    {
        return Assessor::query()
            ->matchable()
            ->covering($request->postal_code)
            ->offering($request->service_type_id)
            ->pluck('id');
    }
}
