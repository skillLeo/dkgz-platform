<?php

namespace App\Jobs;

use App\Models\Commission;
use App\Support\Formatter;
use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCommissionInvoiceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly int $commissionId) {}

    public function handle(): void
    {
        $commission = Commission::with(['assessor.user', 'assignment.serviceRequest'])->find($this->commissionId);
        $user = $commission?->assessor?->user;

        if ($commission === null || $user === null) {
            return;
        }

        Mailer::send($user->email, 'provisionsabrechnung', [
            'eyebrow' => 'Abrechnung',
            'headline' => 'Ihre Provisionsabrechnung liegt vor.',
            'salutation' => 'Guten Tag '.$user->last_name.',',
            'sv_nachname' => $user->last_name,
            'firma' => $commission->assessor->company_name,
            'rechnungsnummer' => $commission->invoice_number,
            'betrag' => Formatter::money($commission->commission_cents),
            'zeitraum' => Formatter::date($commission->created_at),
            'dataTitle' => 'Abrechnung',
            'rows' => [
                ['k' => 'Rechnungsnummer', 'v' => $commission->invoice_number, 'mono' => true],
                ['k' => 'Vorgang', 'v' => $commission->assignment?->serviceRequest?->reference, 'mono' => true],
                ['k' => 'Honorar netto', 'v' => Formatter::money($commission->fee_cents), 'mono' => true],
                ['k' => 'Vermittlungsprovision', 'v' => Formatter::percent((float) $commission->rate_percent)],
                ['k' => 'Provisionsbetrag', 'v' => Formatter::money($commission->commission_cents), 'mono' => true],
            ],
            'cta' => 'Provisionen im Portal ansehen',
            'cta_url' => route('portal.commissions'),
        ], attachments: array_filter([$commission->invoice_path]), related: $commission);
    }
}
