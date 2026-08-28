<?php

use App\Actions\IssueCommissionInvoiceAction;
use App\Models\Commission;
use App\Support\Settings;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Queue;

/**
 * Tax on the referral fee.
 *
 * Nineteen per cent on top of what DKGZ charges, shown on its own line so the
 * partner's bookkeeping gets the split rather than a total to reverse-engineer.
 *
 * The rate is written onto the row when the invoice is issued and read from
 * there afterwards. A rate is a fact about a date: if the German rate ever
 * changes, every invoice already sent must go on saying what it said, because
 * that is what the partner was billed and what both sides have in their books.
 */
beforeEach(function () {
    Queue::fake();

    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

describe('the arithmetic', function () {
    it('adds nineteen per cent to the fee', function () {
        $commission = Commission::factory()->create(['commission_cents' => 10000]);

        $amounts = $commission->amounts();

        expect($amounts['net'])->toBe(10000)
            ->and($amounts['percent'])->toBe(19.0)
            ->and($amounts['vat'])->toBe(1900)
            ->and($amounts['gross'])->toBe(11900);
    });

    it('rounds the tax to the cent rather than truncating it', function () {
        // 79,00 € at 19 % is 15,01 €, not 15,00 €.
        $commission = Commission::factory()->create(['commission_cents' => 7900]);

        expect($commission->amounts()['vat'])->toBe(1501)
            ->and($commission->amounts()['gross'])->toBe(9401);
    });

    it('follows the rate the operator has set', function () {
        Settings::set('business.vat_percent', 7);

        expect(Commission::factory()->create(['commission_cents' => 10000])->amounts()['vat'])
            ->toBe(700);
    });
});

describe('issuing the invoice', function () {
    it('writes the rate, the tax and the total onto the commission', function () {
        $commission = Commission::factory()->create([
            'commission_cents' => 8500,
            'status' => Commission::STATUS_OPEN,
            'invoice_number' => null,
        ]);

        app(IssueCommissionInvoiceAction::class)->execute($commission);

        $fresh = $commission->fresh();

        expect((float) $fresh->vat_percent)->toBe(19.0)
            ->and((int) $fresh->vat_cents)->toBe(1615)
            ->and((int) $fresh->gross_cents)->toBe(10115);
    });

    it('keeps the rate an issued invoice was charged at', function () {
        $commission = Commission::factory()->create([
            'commission_cents' => 10000,
            'status' => Commission::STATUS_OPEN,
            'invoice_number' => null,
        ]);

        app(IssueCommissionInvoiceAction::class)->execute($commission);

        // The rate changes afterwards. The invoice already sent must not.
        Settings::set('business.vat_percent', 25);

        expect($commission->fresh()->amounts())
            ->toMatchArray(['percent' => 19.0, 'vat' => 1900, 'gross' => 11900]);
    });

    it('leaves the net fee alone, so nothing that summed it changes', function () {
        $commission = Commission::factory()->create([
            'commission_cents' => 8500,
            'status' => Commission::STATUS_OPEN,
            'invoice_number' => null,
        ]);

        app(IssueCommissionInvoiceAction::class)->execute($commission);

        expect((int) $commission->fresh()->commission_cents)->toBe(8500);
    });
});

describe('what the invoice shows', function () {
    it('breaks the total into fee, tax and sum', function () {
        $commission = Commission::factory()->create([
            'commission_cents' => 10000,
            'status' => Commission::STATUS_OPEN,
            'invoice_number' => null,
        ]);

        app(IssueCommissionInvoiceAction::class)->execute($commission);

        $html = view('pdf.commission-invoice', [
            'commission' => $commission->fresh()->load(['assessor.user', 'assignment.serviceRequest.serviceType']),
            'invoiceNumber' => 'DKGZ-TEST-1',
            'issuedAt' => now(),
        ])->render();

        expect($html)->toContain('100,00')      // the fee
            ->and($html)->toContain('19,00')    // the tax
            ->and($html)->toContain('119,00')   // what is owed
            ->and($html)->toContain('Umsatzsteuer');
    });
});
