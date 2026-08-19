<?php

namespace App\Console\Commands;

use App\Models\ServiceRequest;
use App\Support\Settings;
use Illuminate\Console\Command;

/**
 * GDPR retention. Requests older than the configured period keep their
 * reference, status and dates — the commission register and the statistics
 * still need those — but lose everything that identifies a person.
 */
class AnonymiseOldRequestsCommand extends Command
{
    protected $signature = 'dkgz:anonymise-requests {--days= : Override the configured retention period}';

    protected $description = 'Anonymisiert Kundendaten in alten Anfragen gemäß der eingestellten Aufbewahrungsfrist.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: Settings::int('business.retention_days', 1095));

        if ($days < 30) {
            $this->error('Die Aufbewahrungsfrist muss mindestens 30 Tage betragen.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $anonymised = 0;

        // Chunked by id: the table grows without bound and the host may only
        // allow 128M.
        ServiceRequest::query()
            ->where('created_at', '<', $cutoff)
            ->where('customer_name', '!=', 'Anonymisiert')
            ->chunkById(100, function ($requests) use (&$anonymised) {
                foreach ($requests as $request) {
                    $request->anonymise();
                    $anonymised++;
                }
            });

        $this->info("{$anonymised} Anfragen wurden anonymisiert (älter als {$days} Tage).");

        return self::SUCCESS;
    }
}
