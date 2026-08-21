<?php

namespace App\Actions;

use App\Jobs\SendCommissionInvoiceJob;
use App\Models\Commission;
use App\Support\Settings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Issues the DKGZ invoice for one commission and sends it to the assessor.
 *
 * This used to live inside the admin controller, which meant the only way to
 * bill was for somebody to remember to press a button. The assessor now
 * triggers it themselves by confirming the job came about, so the work moved
 * here — one path, whoever walks it.
 *
 * Issuing twice is refused rather than repeated: a second invoice number for
 * the same commission is a bookkeeping error, not a retry.
 */
class IssueCommissionInvoiceAction
{
    public function execute(Commission $commission): Commission
    {
        if (filled($commission->invoice_number)) {
            return $commission;
        }

        $commission->loadMissing(['assessor.user', 'assignment.serviceRequest.serviceType']);

        $number = Commission::nextInvoiceNumber();
        $path = null;

        // Some operators invoice from their own accounting system; then the
        // platform still records the number and the status, but produces no PDF.
        if (Settings::bool('business.generate_commission_invoices', true)) {
            $pdf = Pdf::loadView('pdf.commission-invoice', [
                'commission' => $commission,
                'invoiceNumber' => $number,
                'issuedAt' => now(),
            ])->setPaper('a4');

            $path = "provisionen/{$commission->id}/{$number}.pdf";
            Storage::disk('private')->put($path, $pdf->output());
        }

        $commission->update([
            'status' => Commission::STATUS_INVOICED,
            'invoice_number' => $number,
            'invoice_path' => $path,
            'invoiced_at' => now(),
        ]);

        SendCommissionInvoiceJob::dispatch($commission->id);

        return $commission->refresh();
    }
}
