<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Works the queue after the response has been sent.
 *
 * The scheduler on this host is a cron entry somebody has to add by hand, and
 * until they do, queued mail simply accumulates — the application reports
 * "sent" while nothing leaves. That failure is invisible from the inside and
 * costs a customer their answer, so the queue no longer depends on it.
 *
 * This runs in terminate(), after the visitor already has their page, and is
 * rate-limited through the cache so concurrent requests cannot pile workers on
 * top of each other. A real cron is still better and the scheduler still runs
 * everything else; this is the safety net under the one thing that must not
 * silently stop.
 */
class DrainQueueAfterResponse
{
    /** At most one drain in this window, however many requests arrive. */
    private const EVERY_SECONDS = 20;

    /** Kept short: this is a top-up between real scheduler runs, not a worker. */
    private const MAX_SECONDS = 10;

    private const LOCK_KEY = 'dkgz.queue-drain';

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('queue.drain_after_response', true)) {
            return;
        }

        // Nothing waiting is the common case; check before taking a lock.
        try {
            if (DB::table('jobs')->doesntExist()) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        $lock = Cache::lock(self::LOCK_KEY, self::MAX_SECONDS + 5);

        if (! $lock->get()) {
            return;
        }

        try {
            if (Cache::get(self::LOCK_KEY.'.last', 0) > now()->timestamp - self::EVERY_SECONDS) {
                return;
            }

            Cache::put(self::LOCK_KEY.'.last', now()->timestamp, self::EVERY_SECONDS * 3);

            Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--max-time' => self::MAX_SECONDS,
                '--tries' => 3,
                '--quiet' => true,
            ]);
        } catch (Throwable $e) {
            // The visitor already has their page; a failure here must never
            // surface to them, but it must not vanish either.
            Log::warning('Warteschlange konnte nach der Antwort nicht geleert werden.', ['exception' => $e]);
        } finally {
            $lock->release();
        }
    }
}
