<?php

namespace App\Console\Commands;

use App\Models\Assessor;
use Illuminate\Console\Command;

/**
 * Clears every stored bank detail.
 *
 * For the operator who decides not to collect them after all: switching the
 * feature off hides the tab but leaves anything already entered in the database,
 * and data held without a purpose is exactly what a data-protection audit asks
 * about. This removes it.
 */
class PurgeBankDetailsCommand extends Command
{
    protected $signature = 'dkgz:purge-bank-details {--force : Ohne Rückfrage ausführen}';

    protected $description = 'Löscht alle gespeicherten Bankverbindungen der Sachverständigen.';

    public function handle(): int
    {
        $affected = Assessor::query()
            ->whereNotNull('bank_iban')
            ->orWhereNotNull('bank_bic')
            ->orWhereNotNull('bank_account_holder')
            ->count();

        if ($affected === 0) {
            $this->info('Es sind keine Bankverbindungen gespeichert.');

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm("{$affected} Bankverbindung(en) werden unwiderruflich gelöscht. Fortfahren?")) {
            $this->warn('Abgebrochen.');

            return self::FAILURE;
        }

        Assessor::query()->update([
            'bank_account_holder' => null,
            'bank_iban' => null,
            'bank_bic' => null,
        ]);

        activity()->log("Bankverbindungen gelöscht ({$affected} Datensätze).");

        $this->info("{$affected} Bankverbindung(en) gelöscht.");

        return self::SUCCESS;
    }
}
