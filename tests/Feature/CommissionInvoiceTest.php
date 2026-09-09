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

it('numbers invoices consecutively, starting well past one', function () {
    // Consecutive, as an invoice number has to be, but it does not start at
    // one: the old scheme announced to every partner exactly how many jobs DKGZ
    // had ever placed.
    expect(Commission::nextInvoiceNumber())->toBe('DKGZRE-82191');

    billableCommission()->update(['invoice_number' => 'DKGZRE-82191']);
    expect(Commission::nextInvoiceNumber())->toBe('DKGZRE-82192');

    billableCommission()->update(['invoice_number' => 'DKGZRE-82199']);
    expect(Commission::nextInvoiceNumber())->toBe('DKGZRE-82200');
});

it('runs on across the year rather than restarting', function () {
    // A restart each January makes the yearly volume readable again, which is
    // the thing the new scheme exists to avoid.
    billableCommission()->update(['invoice_number' => 'DKGZRE-82233']);

    $this->travelTo(now()->addYear());

    expect(Commission::nextInvoiceNumber())->toBe('DKGZRE-82234');
});

it('writes the PDF privately and marks the commission invoiced', function () {
    $commission = billableCommission();

    $this->actingAs($this->admin)
        ->post("/admin/provisionen/{$commission->id}/rechnung")
        ->assertSessionHasNoErrors();

    $fresh = $commission->fresh();

    expect($fresh->status)->toBe(Commission::STATUS_INVOICED)
        ->and($fresh->invoice_number)->toStartWith('DKGZRE-')
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
        ->and($fresh->invoice_number)->toStartWith('DKGZRE-')
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
