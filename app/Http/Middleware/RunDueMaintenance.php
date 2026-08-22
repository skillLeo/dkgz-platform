<?php

namespace App\Http\Middleware;

use App\Console\Commands\AnonymiseOldRequestsCommand;
use App\Console\Commands\BackupDatabaseCommand;
use App\Console\Commands\CheckLiabilityCoverCommand;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Runs the daily maintenance the scheduler would have run, off ordinary traffic.
 *
 * This plan includes no cron at all, so nothing in routes/console.php ever
 * fires. The queue already has its own safety net; these three are the ones
 * whose absence is silent and expensive: nobody is warned before their
 * liability cover lapses, no backup is written, and customer data is kept past
 * the retention period the operator configured — the last of which is a legal
 * problem, not an inconvenience.
 *
 * Each task carries its own "not before" stamp in the cache, so a task runs at
 * most once a day however much traffic arrives, and a burst of visitors cannot
 * start a second copy while the first is still going.
 *
 * A real cron is still better. The scheduler remains the source of truth where
 * one exists; this only fills in where it does not.
 */
class RunDueMaintenance
{
    /** Hour of day, server time, after which each task may run. */
    private const TASKS = [
        'liability' => [CheckLiabilityCoverCommand::class, 7],
        'backup' => [BackupDatabaseCommand::class, 2],
        'anonymise' => [AnonymiseOldRequestsCommand::class, 3],
    ];

    /** One task per response at most: a visitor should never wait for three. */
    private const LOCK_SECONDS = 120;

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('queue.run_maintenance_after_response', true)) {
            return;
        }

        foreach (self::TASKS as $key => [$command, $notBeforeHour]) {
            if ($this->run($key, $command, $notBeforeHour)) {
                // Only ever one per request, so the visitor who happens to
                // arrive first in the morning does not pay for all of them.
                return;
            }
        }
    }

    private function run(string $key, string $command, int $notBeforeHour): bool
    {
        $today = now()->toDateString();

        if (now()->hour < $notBeforeHour) {
            return false;
        }

        try {
            if (Cache::get("dkgz.maintenance.{$key}") === $today) {
                return false;
            }

            $lock = Cache::lock("dkgz.maintenance.{$key}.lock", self::LOCK_SECONDS);

            if (! $lock->get()) {
                return false;
            }

            try {
                // Stamped before running, not after: a task that dies halfway
                // must not be retried by every subsequent visitor.
                Cache::put("dkgz.maintenance.{$key}", $today, now()->addDay());

                Artisan::call($command);
            } finally {
                $lock->release();
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Wartungsaufgabe konnte nicht ausgeführt werden.', [
                'aufgabe' => $key,
                'exception' => $e,
            ]);

            return false;
        }
    }
}
