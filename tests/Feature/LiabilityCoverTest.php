<?php

use App\Console\Commands\CheckLiabilityCoverCommand;
use App\Models\Assessor;
use App\Models\EmailLog;
use App\Models\LiabilityReminder;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

function coveredPartner(?string $validUntil): Assessor
{
    $user = User::factory()->create(['is_active' => true, 'last_name' => 'Reinhardt']);
    $assessor = Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_available' => true,
    ]);

    if ($validUntil !== null) {
        $assessor->documents()->create([
            'type' => 'liability',
            'path' => 'nachweise/h.pdf',
            'original_name' => 'haftpflicht.pdf',
            'size_bytes' => 100,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
            'valid_until' => $validUntil,
        ]);
    }

    return $assessor->fresh(['documents', 'user']);
}

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('warns at thirty days', function () {
    coveredPartner(now()->addDays(30)->toDateString());

    $this->artisan(CheckLiabilityCoverCommand::class)->assertSuccessful();

    expect(EmailLog::where('template_key', 'haftpflicht-laeuft-ab')->count())->toBe(1)
        ->and(LiabilityReminder::first()->days_before)->toBe(30);
});

it('warns again at three days without repeating the thirty-day notice', function () {
    $assessor = coveredPartner(now()->addDays(3)->toDateString());

    $this->artisan(CheckLiabilityCoverCommand::class);
    $this->artisan(CheckLiabilityCoverCommand::class);

    expect(EmailLog::where('template_key', 'haftpflicht-laeuft-ab')->count())->toBe(1)
        ->and(LiabilityReminder::where('assessor_id', $assessor->id)->count())->toBe(1);
});

it('warns on the day the cover lapses', function () {
    coveredPartner(now()->toDateString());

    $this->artisan(CheckLiabilityCoverCommand::class);

    expect(LiabilityReminder::first()->days_before)->toBe(0);
});

it('says nothing to a partner whose cover runs for another year', function () {
    coveredPartner(now()->addYear()->toDateString());

    $this->artisan(CheckLiabilityCoverCommand::class);

    expect(EmailLog::where('template_key', 'haftpflicht-laeuft-ab')->count())->toBe(0);
});

it('says nothing when no cover date is on file', function () {
    coveredPartner(null);

    $this->artisan(CheckLiabilityCoverCommand::class);

    expect(EmailLog::where('template_key', 'haftpflicht-laeuft-ab')->count())->toBe(0);
});

describe('the matching switch', function () {
    it('keeps a lapsed partner matchable when the requirement is switched off', function () {
        $assessor = coveredPartner(now()->subDay()->toDateString());

        expect($assessor->isMatchable())->toBeFalse();

        Setting::where('key', 'business.require_valid_liability_cover')->update(['value' => '']);
        Settings::flush();

        expect($assessor->fresh(['documents', 'user'])->isMatchable())->toBeTrue()
            ->and(Assessor::matchable()->whereKey($assessor->id)->exists())->toBeTrue();
    });

    it('removes a lapsed partner while the requirement is on', function () {
        $assessor = coveredPartner(now()->subDay()->toDateString());

        expect(Assessor::matchable()->whereKey($assessor->id)->exists())->toBeFalse();
    });
});
