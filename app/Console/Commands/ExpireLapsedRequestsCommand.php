<?php

namespace App\Console\Commands;

use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Closes requests nobody accepted before the acceptance deadline.
 *
 * A lapsed request is not deleted and not re-matched automatically: it moves to
 * 'expired' so it surfaces in the admin list for a human decision, because the
 * usual reason nobody accepted is that the area is thinly covered, and silently
 * re-sending to the same partners would not change that.
 */
class ExpireLapsedRequestsCommand extends Command
{
    protected $signature = 'dkgz:expire-lapsed-requests';

    protected $description = 'Marks matched requests whose acceptance deadline has passed as expired.';

    public function handle(): int
    {
        $expired = 0;

        ServiceRequest::query()
            ->where('status', ServiceRequest::STATUS_MATCHED)
            ->whereNotNull('accept_deadline_at')
            ->where('accept_deadline_at', '<=', now())
            ->chunkById(100, function ($requests) use (&$expired) {
                foreach ($requests as $request) {
                    DB::transaction(function () use ($request, &$expired) {
                        // Re-read under lock: an acceptance may have landed
                        // between the chunk query and this write.
                        $locked = ServiceRequest::whereKey($request->id)->lockForUpdate()->first();

                        if ($locked === null || $locked->status !== ServiceRequest::STATUS_MATCHED) {
                            return;
                        }

                        RequestMatch::where('service_request_id', $locked->id)
                            ->pending()
                            ->update([
                                'outcome' => RequestMatch::OUTCOME_EXPIRED,
                                'responded_at' => now(),
                                'updated_at' => now(),
                            ]);

                        $locked->update(['status' => ServiceRequest::STATUS_EXPIRED]);
                        $expired++;
                    });
                }
            });

        $this->info("{$expired} Anfrage(n) als abgelaufen markiert.");

        return self::SUCCESS;
    }
}
