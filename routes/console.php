<?php

use App\Console\Commands\AnonymiseOldRequestsCommand;
use App\Console\Commands\BackupDatabaseCommand;
use App\Console\Commands\CheckLiabilityCoverCommand;
use Illuminate\Support\Facades\Schedule;

/*
 * There is no supervisor and no daemonised worker on this plan, so the queue is
 * drained by the scheduler: one short-lived worker a minute that exits as soon
 * as the queue is empty or 55 seconds have passed, whichever comes first.
 * withoutOverlapping() keeps a slow job from stacking workers on top of it.
 */
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Failed jobs are surfaced in the admin system page; this only trims the table.
Schedule::command('queue:prune-failed --hours=336')->weekly();

Schedule::command('cache:prune-stale-tags')->hourly();

// Nightly database backup, kept for a fortnight.
Schedule::command(BackupDatabaseCommand::class)->dailyAt('02:30');

// Partners are warned before their liability cover runs out, never after.
Schedule::command(CheckLiabilityCoverCommand::class)->dailyAt('07:00');

// GDPR retention: anonymise customer data on requests past the configured age.
Schedule::command(AnonymiseOldRequestsCommand::class)->dailyAt('03:20');
