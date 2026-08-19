<?php

namespace App\Console\Commands;

use App\Models\Assessor;
use App\Models\CustomerReview;
use App\Models\EmailLog;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * GDPR subject access: everything held about one e-mail address, as JSON.
 * Written to the private disk so the export is never web-reachable.
 */
class ExportPersonalDataCommand extends Command
{
    protected $signature = 'dkgz:export-personal-data {email : Die E-Mail-Adresse}';

    protected $description = 'Exportiert alle zu einer E-Mail-Adresse gespeicherten Daten als JSON.';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        $data = [
            'exportiert_am' => now()->toIso8601String(),
            'adresse' => $email,
            'anfragen' => ServiceRequest::withTrashed()
                ->where('customer_email', $email)
                ->get()
                ->map(fn (ServiceRequest $r) => $r->only([
                    'reference', 'status', 'postal_code', 'city', 'customer_name',
                    'customer_phone', 'customer_email', 'vehicle_make', 'vehicle_model',
                    'vehicle_year', 'vehicle_plate', 'vehicle_vin', 'description',
                    'urgency', 'consent_at', 'ip_address', 'created_at',
                ])),
            'benutzerkonto' => User::withTrashed()->where('email', $email)->get()
                ->map(fn (User $u) => $u->only([
                    'first_name', 'last_name', 'email', 'phone', 'locale',
                    'is_active', 'last_login_at', 'last_login_ip', 'created_at',
                ])),
            'sachverstaendigenprofil' => Assessor::withTrashed()
                ->whereHas('user', fn ($q) => $q->where('email', $email))
                ->get()
                ->map(fn (Assessor $a) => $a->only([
                    'company_name', 'legal_form', 'street', 'house_number',
                    'postal_code', 'city', 'vat_id', 'website', 'certification_body',
                    'certification_number', 'approval_status', 'created_at',
                ])),
            'bewertungen' => CustomerReview::whereHas(
                'assignment.serviceRequest',
                fn ($q) => $q->where('customer_email', $email)
            )->get()->map(fn (CustomerReview $r) => $r->only([
                'rating', 'feedback_category', 'feedback', 'submitted_at',
            ])),
            'email_protokoll' => EmailLog::where('recipient', $email)->get()
                ->map(fn (EmailLog $l) => $l->only(['template_key', 'subject', 'status', 'sent_at'])),
        ];

        $path = 'exporte/dsgvo-'.md5($email).'-'.now()->format('Ymd-His').'.json';

        Storage::disk('private')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('Export abgelegt unter: '.Storage::disk('private')->path($path));

        return self::SUCCESS;
    }
}
