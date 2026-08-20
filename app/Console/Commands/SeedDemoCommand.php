<?php

namespace App\Console\Commands;

use App\Actions\MatchRequestAction;
use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\AssignmentStatusEvent;
use App\Models\Commission;
use App\Models\PostalCode;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Builds a demonstrable state: partners who actually match 40589, orders with
 * history, and live requests whose deadlines fall today.
 *
 * Everything it creates is tagged so `dkgz:reset-demo` can remove exactly this
 * and nothing else. It refuses to run in production without --force, because a
 * seeder that quietly invents partners in a live network would be worse than
 * useless.
 */
class SeedDemoCommand extends Command
{
    protected $signature = 'dkgz:seed-demo {--force : Auch in der Produktionsumgebung ausführen}';

    protected $description = 'Legt vorführbare Demodaten an (Partner, Aufträge, Provisionen, offene Anfragen).';

    public const MARKER = 'demo.dkgz.test';

    private const PASSWORD = 'DkgzDemo2026!';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('In der Produktionsumgebung nur mit --force.');

            return self::FAILURE;
        }

        if (ServiceType::count() === 0) {
            $this->error('Es sind keine Leistungsarten vorhanden. Bitte zuerst den ProductionSeeder ausführen.');

            return self::FAILURE;
        }

        $this->ensurePostalCode('40589', 'Düsseldorf');
        $types = ServiceType::orderBy('sort_order')->get();

        DB::transaction(function () use ($types) {
            $wide = $this->partner(
                'Sachverständigenbüro Nordrhein', 'nordrhein', 'Michael', 'Reinhardt',
                '01000', '99999', 'Bundesweit', $types, available: true,
            );

            $local = $this->partner(
                'Kfz-Prüfstelle Rheinbogen', 'rheinbogen', 'Sabine', 'Brandt',
                '40000', '42999', 'Düsseldorf und Umgebung', $types->take(5), available: true,
            );

            $this->partner(
                'Gutachterbüro Sander & Co.', 'sander', 'Thomas', 'Sander',
                '40000', '41999', 'Düsseldorf Süd', $types->take(4), available: false,
            );

            $this->history($wide, $local, $types);
            $this->openRequests($types);
        });

        $this->report();

        return self::SUCCESS;
    }

    private function partner(
        string $company,
        string $handle,
        string $firstName,
        string $lastName,
        string $from,
        string $to,
        string $label,
        $types,
        bool $available,
    ): Assessor {
        $user = User::updateOrCreate(
            ['email' => "{$handle}@".self::MARKER],
            [
                'name' => "{$firstName} {$lastName}",
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => '+49 211 4470012',
                'password' => Hash::make(self::PASSWORD),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(['assessor']);

        $assessor = Assessor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $company,
                'legal_form' => 'einzelunternehmen',
                'street' => 'Kölner Landstraße',
                'house_number' => '204',
                'postal_code' => '40589',
                'city' => 'Düsseldorf',
                'country' => 'DE',
                'certification_body' => 'dekra',
                'certification_number' => 'DEKRA-'.strtoupper($handle),
                'certification_valid_until' => now()->addYears(2),
                'approval_status' => Assessor::STATUS_APPROVED,
                'approved_at' => now()->subMonths(6),
                'is_available' => $available,
            ],
        );

        $assessor->serviceAreas()->delete();
        $assessor->serviceAreas()->create([
            'postal_code_from' => $from,
            'postal_code_to' => $to,
            'label' => $label,
        ]);

        $assessor->serviceTypes()->sync($types->pluck('id'));

        // Valid cover, so nobody drops out of matching during the demo.
        $assessor->documents()->delete();
        $assessor->documents()->create([
            'type' => 'liability',
            'path' => 'nachweise/demo-haftpflicht.pdf',
            'original_name' => 'Haftpflicht_2026.pdf',
            'size_bytes' => 655_360,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now()->subMonths(6),
            'valid_until' => now()->addYear(),
        ]);

        return $assessor->fresh(['serviceAreas', 'serviceTypes', 'documents', 'user']);
    }

    /** Completed work across two months, so the register and charts hold data. */
    private function history(Assessor $wide, Assessor $local, $types): void
    {
        $fees = [164_000, 42_000, 127_500, 85_000, 29_000, 96_500];
        $rate = Settings::commissionRate();

        foreach ($fees as $index => $feeCents) {
            $assessor = $index % 2 === 0 ? $wide : $local;
            $completedAt = now()->subDays(8 + ($index * 9));

            $request = $this->request(
                $types[$index % $types->count()],
                $index % 2 === 0 ? '40589' : '40210',
                $index % 2 === 0 ? 'Düsseldorf' : 'Düsseldorf',
                $completedAt->copy()->subDays(3),
                ServiceRequest::STATUS_COMPLETED,
            );

            RequestMatch::create([
                'service_request_id' => $request->id,
                'assessor_id' => $assessor->id,
                'outcome' => RequestMatch::OUTCOME_ACCEPTED,
                'notified_at' => $request->created_at,
                'viewed_at' => $request->created_at->copy()->addMinutes(9),
                'responded_at' => $request->created_at->copy()->addMinutes(23),
            ]);

            $assignment = Assignment::create([
                'service_request_id' => $request->id,
                'assessor_id' => $assessor->id,
                'status' => Assignment::STATUS_COMPLETED,
                'accepted_at' => $request->created_at->copy()->addMinutes(23),
                'dkgz_fee_snapshot_cents' => $types[$index % $types->count()]->dkgz_fee_cents,
                'started_at' => $request->created_at->copy()->addHours(4),
                'completed_at' => $completedAt,
                'fee_cents' => $feeCents,
            ]);

            foreach ([
                [Assignment::STATUS_ACCEPTED, $assignment->accepted_at],
                [Assignment::STATUS_IN_PROGRESS, $assignment->started_at],
                [Assignment::STATUS_COMPLETED, $assignment->completed_at],
            ] as [$status, $at]) {
                AssignmentStatusEvent::create([
                    'assignment_id' => $assignment->id,
                    'to_status' => $status,
                    'actor_type' => 'assessor',
                    'created_at' => $at,
                ]);
            }

            // The two oldest rows keep the percentage model they were earned
            // under, so the register demonstrably shows both honestly.
            $isLegacy = $index >= 4;
            $dkgzFee = $types[$index % $types->count()]->dkgz_fee_cents ?? 7_900;

            Commission::create([
                'assignment_id' => $assignment->id,
                'assessor_id' => $assessor->id,
                'fee_type' => $isLegacy ? Commission::TYPE_PERCENTAGE : Commission::TYPE_FIXED,
                'dkgz_fee_cents' => $isLegacy ? null : $dkgzFee,
                'fee_cents' => $feeCents,
                'rate_percent' => $isLegacy ? $rate : null,
                'commission_cents' => $isLegacy ? (int) round($feeCents * $rate / 100) : $dkgzFee,
                'status' => $index < 2 ? Commission::STATUS_OPEN : Commission::STATUS_SETTLED,
                'created_at' => $completedAt,
                'updated_at' => $completedAt,
            ]);
        }
    }

    /** Two live requests at 40589, matched and open — nothing expires now. */
    private function openRequests($types): void
    {
        foreach ([[0, 3], [1, 6]] as [$typeIndex, $hoursAgo]) {
            $request = $this->request(
                $types[$typeIndex],
                '40589',
                'Düsseldorf',
                now()->subHours($hoursAgo),
                ServiceRequest::STATUS_NEW,
            );

            app(MatchRequestAction::class)->execute($request);
        }
    }

    private function request(
        ServiceType $type,
        string $plz,
        string $city,
        $createdAt,
        string $status,
    ): ServiceRequest {
        $this->ensurePostalCode($plz, $city);

        return ServiceRequest::create([
            'reference' => ServiceRequest::nextReference(),
            'service_type_id' => $type->id,
            'postal_code' => $plz,
            'city' => $city,
            'customer_name' => 'Martina Reinhardt',
            'customer_phone' => '+49 211 3300124',
            'customer_email' => 'kundin@'.self::MARKER,
            'vehicle_make' => 'VW',
            'vehicle_model' => 'Passat B8',
            'vehicle_year' => 2019,
            'vehicle_plate' => 'D-AB 1234',
            'description' => 'Heckschaden nach Auffahrunfall, Fahrzeug fahrbereit.',
            'urgency' => 'soon',
            'status' => $status,
            'consent_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function ensurePostalCode(string $code, string $city): void
    {
        PostalCode::firstOrCreate(
            ['code' => $code],
            ['city' => $city, 'state' => 'Nordrhein-Westfalen'],
        );
    }

    private function report(): void
    {
        $this->newLine();
        $this->info('Demodaten angelegt.');
        $this->newLine();
        $this->line('  Partner-Zugänge (alle mit demselben Passwort):');
        $this->line('    nordrhein@'.self::MARKER.'   Sachverständigenbüro Nordrhein · bundesweit · verfügbar');
        $this->line('    rheinbogen@'.self::MARKER.'  Kfz-Prüfstelle Rheinbogen · 40000–42999 · verfügbar');
        $this->line('    sander@'.self::MARKER.'      Gutachterbüro Sander & Co. · nicht verfügbar');
        $this->newLine();
        $this->line('    Passwort: '.self::PASSWORD);
        $this->newLine();
        $this->line('  Eine Anfrage für PLZ 40589 trifft die ersten beiden Partner.');
        $this->line('  Zurücksetzen mit: php artisan dkgz:reset-demo');
        $this->newLine();
    }
}
