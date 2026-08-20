<?php

namespace App\Console\Commands;

use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\AssignmentStatusEvent;
use App\Models\Commission;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes exactly what dkgz:seed-demo created, by its e-mail marker, and
 * nothing else. Real partners and real requests are never touched.
 */
class ResetDemoCommand extends Command
{
    protected $signature = 'dkgz:reset-demo {--force : Ohne Rückfrage ausführen}';

    protected $description = 'Entfernt sämtliche Demodaten.';

    public function handle(): int
    {
        $marker = '%@'.SeedDemoCommand::MARKER;

        $userIds = User::where('email', 'like', $marker)->pluck('id');
        $assessorIds = Assessor::whereIn('user_id', $userIds)->pluck('id');
        $requestIds = ServiceRequest::where('customer_email', 'like', $marker)->pluck('id');

        $total = $userIds->count() + $requestIds->count();

        if ($total === 0) {
            $this->info('Es sind keine Demodaten vorhanden.');

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm("{$userIds->count()} Demo-Konten und {$requestIds->count()} Demo-Anfragen werden gelöscht. Fortfahren?")) {
            $this->warn('Abgebrochen.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($userIds, $assessorIds, $requestIds) {
            $assignmentIds = Assignment::whereIn('assessor_id', $assessorIds)
                ->orWhereIn('service_request_id', $requestIds)
                ->pluck('id');

            Commission::whereIn('assignment_id', $assignmentIds)->delete();
            AssignmentStatusEvent::whereIn('assignment_id', $assignmentIds)->delete();
            Assignment::whereIn('id', $assignmentIds)->forceDelete();

            RequestMatch::whereIn('assessor_id', $assessorIds)
                ->orWhereIn('service_request_id', $requestIds)
                ->delete();

            ServiceRequest::whereIn('id', $requestIds)->forceDelete();

            foreach (Assessor::whereIn('id', $assessorIds)->get() as $assessor) {
                $assessor->serviceAreas()->delete();
                $assessor->serviceTypes()->detach();
                $assessor->documents()->delete();
                $assessor->forceDelete();
            }

            User::whereIn('id', $userIds)->forceDelete();
        });

        $this->info('Demodaten entfernt.');

        return self::SUCCESS;
    }
}
