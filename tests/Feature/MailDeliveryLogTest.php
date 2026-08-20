<?php

use App\Models\EmailLog;
use App\Support\Mailer;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('marks the log row sent once the message actually leaves', function () {
    $log = Mailer::send('empfaenger@example.test', 'testmail', ['headline' => 'Test']);

    expect($log->fresh()->status)->toBe(EmailLog::STATUS_SENT)
        ->and($log->fresh()->sent_at)->not->toBeNull();
});

it('leaves the row queued while the worker has not run', function () {
    Queue::fake();

    $log = Mailer::send('empfaenger@example.test', 'testmail', ['headline' => 'Test']);

    expect($log->fresh()->status)->toBe(EmailLog::STATUS_QUEUED)
        ->and($log->fresh()->sent_at)->toBeNull();
});
