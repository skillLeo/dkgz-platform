<?php

use App\Support\AttentionQueue;
use App\Support\QueueHealth;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\DB;

/**
 * What happens when mail stops working.
 *
 * A changed mailbox password stopped every e-mail for a day, and took the site
 * with it. The queue is drained from inside web requests because this host has
 * no cron, and a mail server refusing every connection took nearly twelve
 * seconds a job — so each visitor's PHP process was held that long doing
 * nothing, until the process pool ran out. One wrong password, two outages.
 *
 * The jobs themselves were never in danger. What had to change is that a broken
 * dependency can no longer hold visitors hostage, and that nobody has to notice
 * it by accident.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);

    QueueHealth::resume();
});

describe('the drain stops itself', function () {
    it('tolerates a failure or two, because one is bad luck', function () {
        QueueHealth::recordFailure();
        QueueHealth::recordFailure();

        expect(QueueHealth::isPaused())->toBeFalse();
    });

    it('pauses once failures are clearly not bad luck', function () {
        QueueHealth::recordFailure();
        QueueHealth::recordFailure();
        QueueHealth::recordFailure();

        expect(QueueHealth::isPaused())->toBeTrue()
            ->and(QueueHealth::pausedFor())->toBeGreaterThan(0);
    });

    it('forgets the run of failures the moment something succeeds', function () {
        QueueHealth::recordFailure();
        QueueHealth::recordFailure();
        QueueHealth::recordSuccess();
        QueueHealth::recordFailure();

        expect(QueueHealth::isPaused())->toBeFalse()
            ->and(QueueHealth::failures())->toBe(1);
    });

    it('starts again when the operator has fixed the cause', function () {
        foreach (range(1, 5) as $ignored) {
            QueueHealth::recordFailure();
        }

        expect(QueueHealth::isPaused())->toBeTrue();

        QueueHealth::resume();

        expect(QueueHealth::isPaused())->toBeFalse();
    });

    it('holds the middleware back while it is paused', function () {
        $source = file_get_contents(app_path('Http/Middleware/DrainQueueAfterResponse.php'));

        expect($source)->toContain('QueueHealth::isPaused()')
            ->and($source)->toContain('QueueHealth::recordFailure()')
            ->and($source)->toContain('QueueHealth::recordSuccess()');
    });
});

describe('the transport cannot hang a page', function () {
    it('has a bounded timeout', function () {
        // Twelve seconds a job, after every page view, is the outage.
        expect(config('mail.mailers.smtp.timeout'))->not->toBeNull()
            ->and((int) config('mail.mailers.smtp.timeout'))->toBeLessThanOrEqual(10);
    });
});

describe('somebody is told', function () {
    it('says nothing while the mail is going out', function () {
        $mail = collect(AttentionQueue::items())->firstWhere('reference', 'E-Mail');

        expect($mail)->toBeNull();
    });

    it('raises undelivered mail on the dashboard', function () {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'TransportException: authentication failed',
            'failed_at' => now()->subHours(3),
        ]);

        $mail = collect(AttentionQueue::items())->firstWhere('reference', 'E-Mail');

        expect($mail)->not->toBeNull()
            ->and($mail['matter'])->toContain('1 E-Mails konnten nicht versendet werden');
    });

    it('says so when the queue has stopped itself as well', function () {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'TransportException',
            'failed_at' => now(),
        ]);

        foreach (range(1, 3) as $ignored) {
            QueueHealth::recordFailure();
        }

        $mail = collect(AttentionQueue::items())->firstWhere('reference', 'E-Mail');

        expect($mail['matter'])->toContain('pausiert');
    });
});
