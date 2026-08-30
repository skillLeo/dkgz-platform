<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Whether it is still worth working the queue from inside a web request.
 *
 * The queue is drained after each response because this host has no cron. That
 * is fine while jobs succeed. When they stop succeeding it is the opposite of
 * fine: a mail server refusing every connection took nearly twelve seconds per
 * job, and with the drain running after every page view each visitor's PHP
 * process was held that long doing nothing. On shared hosting a handful at once
 * is the entire process pool, which is how a wrong mail password took the whole
 * site down for a day.
 *
 * So failures back the drain off. One failure is noise; several in a row mean
 * something is broken that retrying will not fix, and the right response is to
 * stop until somebody has repaired it — the jobs are safe in the table either
 * way. Any success clears the count, so a transient blip costs one short pause
 * rather than a lasting one.
 */
class QueueHealth
{
    private const FAILURES_KEY = 'dkgz.queue.failures';

    private const PAUSED_UNTIL_KEY = 'dkgz.queue.paused-until';

    /** Below this, a failure is bad luck rather than a broken dependency. */
    private const TOLERATED = 3;

    /** How long to leave it alone once it is clearly broken. */
    private const PAUSE_MINUTES = 15;

    public static function isPaused(): bool
    {
        return (int) Cache::get(self::PAUSED_UNTIL_KEY, 0) > now()->timestamp;
    }

    /** Seconds until draining resumes, or zero when it is not paused. */
    public static function pausedFor(): int
    {
        return max(0, (int) Cache::get(self::PAUSED_UNTIL_KEY, 0) - now()->timestamp);
    }

    /**
     * A job failed.
     *
     * Counted rather than acted on immediately: the point is to notice a broken
     * dependency, and one failure does not distinguish that from a single bad
     * address.
     */
    public static function recordFailure(): void
    {
        $failures = (int) Cache::get(self::FAILURES_KEY, 0) + 1;

        Cache::put(self::FAILURES_KEY, $failures, now()->addHour());

        if ($failures >= self::TOLERATED) {
            Cache::put(
                self::PAUSED_UNTIL_KEY,
                now()->addMinutes(self::PAUSE_MINUTES)->timestamp,
                now()->addMinutes(self::PAUSE_MINUTES + 5)
            );
        }
    }

    /** Something went through, so whatever was broken is working again. */
    public static function recordSuccess(): void
    {
        Cache::forget(self::FAILURES_KEY);
        Cache::forget(self::PAUSED_UNTIL_KEY);
    }

    public static function failures(): int
    {
        return (int) Cache::get(self::FAILURES_KEY, 0);
    }

    /** Used when an operator has fixed the cause and wants the queue moving. */
    public static function resume(): void
    {
        self::recordSuccess();
    }
}
