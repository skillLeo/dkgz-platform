<?php

use App\Actions\ConfirmAssignmentAction;
use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * "Zustande gekommen" is the moment DKGZ has earned its fee, so these guard the
 * three things that must happen together — status, booking and invoice — and
 * the one thing that must not: charging twice for the same job.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    Storage::fake('private');
});

function acceptedAssignment(int $dkgzFeeCents = 7_900): Assignment
{
    $type = ServiceType::factory()->create(['dkgz_fee_cents' => $dkgzFeeCents]);
    $assessor = Assessor::factory()->create(['approval_status' => Assessor::STATUS_APPROVED]);

    $request = ServiceRequest::factory()->create([
        'service_type_id' => $type->id,
        'status' => ServiceRequest::STATUS_ASSIGNED,
    ]);

    return Assignment::factory()->create([
        'assessor_id' => $assessor->id,
        'service_request_id' => $request->id,
        'status' => Assignment::STATUS_ACCEPTED,
        'dkgz_fee_snapshot_cents' => $dkgzFeeCents,
        'confirmed_at' => null,
    ]);
}

it('moves the job into progress, books the fee and issues the invoice', function () {
    $assignment = acceptedAssignment(9_900);

    $commission = app(ConfirmAssignmentAction::class)->execute($assignment);

    expect($assignment->fresh()->status)->toBe(Assignment::STATUS_IN_PROGRESS)
        ->and($assignment->fresh()->confirmed_at)->not->toBeNull()
        ->and($commission->commission_cents)->toBe(9_900)
        ->and($commission->status)->toBe(Commission::STATUS_INVOICED)
        ->and($commission->invoice_number)->not->toBeEmpty();
});

it('bills the fee snapshotted at acceptance, not the current price list', function () {
    $assignment = acceptedAssignment(7_900);

    // The operator raises the price after this job was accepted.
    $assignment->serviceRequest->serviceType->update(['dkgz_fee_cents' => 19_900]);

    $commission = app(ConfirmAssignmentAction::class)->execute($assignment);

    expect($commission->commission_cents)->toBe(7_900);
});

it('refuses to confirm a job that is not merely accepted', function () {
    $assignment = acceptedAssignment();
    $assignment->update(['status' => Assignment::STATUS_IN_PROGRESS]);

    expect(fn () => app(ConfirmAssignmentAction::class)->execute($assignment))
        ->toThrow(RuntimeException::class);
});

it('never issues a second invoice number for the same job', function () {
    $assignment = acceptedAssignment();

    $first = app(ConfirmAssignmentAction::class)->execute($assignment);

    // Completion books against the same commission; the invoice must survive.
    $assignment->fresh()->commission->refresh();

    expect(Commission::where('assignment_id', $assignment->id)->count())->toBe(1)
        ->and($first->fresh()->invoice_number)->toBe($first->invoice_number);
});

it('lets the assessor confirm from the portal', function () {
    $assignment = acceptedAssignment();

    $this->actingAs($assignment->assessor->user)
        ->post("/portal/auftraege/{$assignment->id}/zustande-gekommen")
        ->assertRedirect();

    expect($assignment->fresh()->status)->toBe(Assignment::STATUS_IN_PROGRESS);
});

it('records the assessor\'s own invoice to the customer or insurer', function () {
    $assignment = acceptedAssignment();

    $this->actingAs($assignment->assessor->user)
        ->post("/portal/auftraege/{$assignment->id}/kundenrechnung", [
            'customer_invoice_cents' => 84_500,
            'customer_invoice_recipient' => 'versicherung',
            'customer_invoice_number' => 'RE-2026-0042',
        ])
        ->assertRedirect();

    $assignment->refresh();

    expect($assignment->customer_invoice_cents)->toBe(84_500)
        ->and($assignment->customer_invoice_recipient)->toBe('versicherung')
        ->and($assignment->customer_invoice_number)->toBe('RE-2026-0042');
});

it('rejects an invoice recipient it does not recognise', function () {
    $assignment = acceptedAssignment();

    $this->actingAs($assignment->assessor->user)
        ->post("/portal/auftraege/{$assignment->id}/kundenrechnung", [
            'customer_invoice_recipient' => 'schwiegermutter',
        ])
        ->assertSessionHasErrors('customer_invoice_recipient');
});

it('shows the DKGZ invoice inside the job it belongs to', function () {
    $assignment = acceptedAssignment();
    app(ConfirmAssignmentAction::class)->execute($assignment);

    $this->actingAs($assignment->assessor->user)
        ->get("/portal/auftraege/{$assignment->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('dkgzInvoices', 1));
});
