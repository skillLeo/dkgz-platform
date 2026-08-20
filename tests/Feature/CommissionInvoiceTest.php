<?php

use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;

function billableCommission(): Commission
{
    $assessor = Assessor::factory()->create(['approval_status' => Assessor::STATUS_APPROVED]);
    $assignment = Assignment::factory()->create(['assessor_id' => $assessor->id]);

    return Commission::factory()->create([
        'assessor_id' => $assessor->id,
        'assignment_id' => $assignment->id,
        'status' => Commission::STATUS_OPEN,
    ]);
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    Storage::fake('private');

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
});

it('numbers invoices sequentially within the year', function () {
    expect(Commission::nextInvoiceNumber(2026))->toBe('DKGZ-RE-2026-0001');

    billableCommission()->update(['invoice_number' => 'DKGZ-RE-2026-0001']);
    expect(Commission::nextInvoiceNumber(2026))->toBe('DKGZ-RE-2026-0002');

    billableCommission()->update(['invoice_number' => 'DKGZ-RE-2026-0009']);
    expect(Commission::nextInvoiceNumber(2026))->toBe('DKGZ-RE-2026-0010');
});

it('restarts the sequence in a new year', function () {
    billableCommission()->update(['invoice_number' => 'DKGZ-RE-2026-0042']);

    expect(Commission::nextInvoiceNumber(2027))->toBe('DKGZ-RE-2027-0001');
});

it('writes the PDF privately and marks the commission invoiced', function () {
    $commission = billableCommission();

    $this->actingAs($this->admin)
        ->post("/admin/provisionen/{$commission->id}/rechnung")
        ->assertSessionHasNoErrors();

    $fresh = $commission->fresh();

    expect($fresh->status)->toBe(Commission::STATUS_INVOICED)
        ->and($fresh->invoice_number)->toStartWith('DKGZ-RE-')
        ->and($fresh->invoice_path)->not->toBeNull();

    Storage::disk('private')->assertExists($fresh->invoice_path);
});

it('records the number but writes no PDF when generation is switched off', function () {
    Setting::where('key', 'business.generate_commission_invoices')->update(['value' => '']);
    Settings::flush();

    $commission = billableCommission();

    $this->actingAs($this->admin)->post("/admin/provisionen/{$commission->id}/rechnung");

    $fresh = $commission->fresh();

    expect($fresh->status)->toBe(Commission::STATUS_INVOICED)
        ->and($fresh->invoice_number)->toStartWith('DKGZ-RE-')
        ->and($fresh->invoice_path)->toBeNull()
        ->and(Storage::disk('private')->files('provisionen'))->toBe([]);
});

it('never serves an invoice over the public web', function () {
    $commission = billableCommission();
    $this->actingAs($this->admin)->post("/admin/provisionen/{$commission->id}/rechnung");

    $path = $commission->fresh()->invoice_path;

    expect((string) $path)->toStartWith('provisionen/')
        ->and(Storage::disk('private')->exists($path))->toBeTrue();
});
