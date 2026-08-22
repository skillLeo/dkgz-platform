<?php

use App\Models\Assessor;
use App\Models\AssessorDocument;
use App\Models\LiabilityReminder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * The live host includes no cron at all, so nothing in routes/console.php ever
 * fires there. These cover the tasks whose absence is silent: nobody warned
 * before their cover lapses, no backup written, and customer data kept past the
 * retention period — the last of which is a legal problem, not an annoyance.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    Storage::fake('private');
    Cache::flush();
});

it('runs a due maintenance task off an ordinary page visit', function () {
    $this->travelTo(now()->setTime(9, 0));

    $this->get('/')->assertOk();

    expect(Cache::get('dkgz.maintenance.liability'))->toBe(now()->toDateString());
});

it('runs each task at most once a day however much traffic arrives', function () {
    $this->travelTo(now()->setTime(9, 0));

    $assessor = Assessor::factory()->create(['approval_status' => Assessor::STATUS_APPROVED]);

    $assessor->documents()->create([
        'type' => AssessorDocument::TYPE_LIABILITY,
        'path' => 'nachweise/haftpflicht.pdf',
        'original_name' => 'Haftpflicht.pdf',
        'size_bytes' => 1024,
        'mime_type' => 'application/pdf',
        'uploaded_at' => now()->subMonths(6),
        'valid_until' => now()->addDays(14),
    ]);

    for ($visit = 0; $visit < 5; $visit++) {
        $this->get('/')->assertOk();
    }

    // One reminder, not five.
    expect(LiabilityReminder::where('assessor_id', $assessor->id)->count())->toBeLessThanOrEqual(1);
});

it('waits until the hour the task is scheduled for', function () {
    $this->travelTo(now()->setTime(1, 0));

    $this->get('/')->assertOk();

    expect(Cache::get('dkgz.maintenance.liability'))->toBeNull()
        ->and(Cache::get('dkgz.maintenance.backup'))->toBeNull();
});

it('runs the next task on the next visit, never several at once', function () {
    $this->travelTo(now()->setTime(9, 0));

    $this->get('/')->assertOk();
    $first = collect(['liability', 'backup', 'anonymise'])
        ->filter(fn ($key) => Cache::get("dkgz.maintenance.{$key}") !== null);

    expect($first)->toHaveCount(1);

    $this->get('/')->assertOk();
    $second = collect(['liability', 'backup', 'anonymise'])
        ->filter(fn ($key) => Cache::get("dkgz.maintenance.{$key}") !== null);

    expect($second)->toHaveCount(2);
});

it('can be switched off where a real cron exists', function () {
    config(['queue.run_maintenance_after_response' => false]);

    $this->travelTo(now()->setTime(9, 0));

    $this->get('/')->assertOk();

    expect(Cache::get('dkgz.maintenance.liability'))->toBeNull();
});
