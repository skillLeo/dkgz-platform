<?php

use App\Models\EmailLog;
use App\Support\Mailer;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('works the queue after a response so mail does not wait for a cron', function () {
    config(['queue.default' => 'database']);

    Mailer::send('wartend@example.test', 'testmail', ['headline' => 'Test']);

    expect(DB::table('jobs')->count())->toBe(1);

    // A visitor loading any page is enough to move it.
    $this->get('/')->assertOk();

    expect(DB::table('jobs')->count())->toBe(0)
        ->and(EmailLog::where('recipient', 'wartend@example.test')->first()->status)
        ->toBe(EmailLog::STATUS_SENT);
});

it('can be switched off where a real cron exists', function () {
    config(['queue.default' => 'database', 'queue.drain_after_response' => false]);

    Mailer::send('bleibt@example.test', 'testmail', ['headline' => 'Test']);
    $this->get('/');

    expect(DB::table('jobs')->count())->toBe(1);
});

it('does nothing at all when the queue is empty', function () {
    config(['queue.default' => 'database']);

    $this->get('/')->assertOk();

    expect(DB::table('jobs')->count())->toBe(0);
});
