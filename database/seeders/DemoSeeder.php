<?php

namespace Database\Seeders;

use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use App\Models\Assignment;
use App\Models\AssignmentDocument;
use App\Models\Commission;
use App\Models\CustomerReview;
use App\Models\Invitation;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Demo data so every screen and every empty state can be reviewed with
 * realistic records. Never run in production.
 */
class DemoSeeder extends Seeder
{
    /** Real German cities with their actual postal-code neighbourhoods. */
    private const CITIES = [
        ['Düsseldorf', '40210', '40000', '40999', 'Düsseldorf und Umgebung'],
        ['Köln', '50667', '50000', '51999', 'Köln und Rhein-Erft'],
        ['Essen', '45127', '45000', '45999', 'Essen und westliches Ruhrgebiet'],
        ['Dortmund', '44135', '44000', '44999', 'Dortmund und östliches Ruhrgebiet'],
        ['Duisburg', '47051', '47000', '47999', 'Duisburg und Niederrhein'],
        ['Wuppertal', '42103', '42000', '42999', 'Bergisches Land'],
        ['Bonn', '53111', '53000', '53999', 'Bonn und Rhein-Sieg'],
        ['Münster', '48143', '48000', '48999', 'Münsterland'],
        ['Bielefeld', '33602', '33000', '33999', 'Ostwestfalen-Lippe'],
        ['Hamburg', '20095', '20000', '22999', 'Hamburg und Umland'],
        ['Bremen', '28195', '28000', '28999', 'Bremen und Umland'],
        ['Hannover', '30159', '30000', '31999', 'Region Hannover'],
        ['Braunschweig', '38100', '38000', '38999', 'Braunschweig und Harz'],
        ['Berlin', '10115', '10000', '14999', 'Berlin und Brandenburg'],
        ['Leipzig', '04103', '04000', '04999', 'Leipzig und Umland'],
        ['Dresden', '01067', '01000', '01999', 'Dresden und Sächsische Schweiz'],
        ['Frankfurt am Main', '60311', '60000', '61999', 'Rhein-Main'],
        ['Wiesbaden', '65183', '65000', '65999', 'Wiesbaden und Taunus'],
        ['Mannheim', '68159', '68000', '68999', 'Rhein-Neckar'],
        ['Stuttgart', '70173', '70000', '71999', 'Region Stuttgart'],
        ['Karlsruhe', '76133', '76000', '76999', 'Mittlerer Oberrhein'],
        ['Freiburg im Breisgau', '79098', '79000', '79999', 'Südbaden'],
        ['München', '80331', '80000', '82999', 'München und Oberbayern'],
        ['Nürnberg', '90402', '90000', '91999', 'Nürnberg und Mittelfranken'],
        ['Augsburg', '86150', '86000', '86999', 'Augsburg und Schwaben'],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Der Demo-Seeder darf in der Produktion nicht ausgeführt werden.');
        }

        $serviceTypes = ServiceType::orderBy('sort_order')->get();

        if ($serviceTypes->isEmpty()) {
            $this->call(ServiceTypeSeeder::class);
            $serviceTypes = ServiceType::orderBy('sort_order')->get();
        }

        $this->createStaff();
        $assessors = $this->createAssessors($serviceTypes);
        $this->createRequests($serviceTypes, $assessors);
        $this->createInvitations();
    }

    private function createStaff(): void
    {
        $staff = [
            ['admin@dkgz.test', 'Katrin', 'Ahrens', 'admin'],
            ['vermittlung@dkgz.test', 'Jonas', 'Wehner', 'manager'],
            ['support@dkgz.test', 'Miriam', 'Falk', 'support'],
            ['redaktion@dkgz.test', 'Tobias', 'Krengel', 'content_editor'],
        ];

        foreach ($staff as [$email, $first, $last, $role]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'name' => "{$first} {$last}",
                    'password' => Hash::make('Gutachten2026!'),
                    'email_verified_at' => now(),
                    'locale' => 'de',
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$role]);
        }
    }

    /** @return Collection<int, Assessor> */
    private function createAssessors($serviceTypes)
    {
        $assessors = collect();

        foreach (self::CITIES as $index => [$city, $plz, $from, $to, $label]) {
            $number = $index + 1;
            $surname = fake('de_DE')->lastName();

            $user = User::firstOrCreate(
                ['email' => "sv{$number}@dkgz.test"],
                [
                    'first_name' => fake('de_DE')->firstName(),
                    'last_name' => $surname,
                    'name' => fake('de_DE')->firstName()." {$surname}",
                    'password' => Hash::make('Gutachten2026!'),
                    'email_verified_at' => now(),
                    'phone' => '+49 '.fake()->numberBetween(150, 179).' '.fake()->numerify('#######'),
                    'locale' => 'de',
                    'is_active' => true,
                ]
            );

            $user->syncRoles(['assessor']);

            // A spread of states so every admin filter and portal gate has data:
            // 20 approved, 2 pending, 1 rejected, 1 suspended, 1 unavailable.
            $status = match (true) {
                $index >= 23 => Assessor::STATUS_PENDING,
                $index === 22 => Assessor::STATUS_SUSPENDED,
                $index === 21 => Assessor::STATUS_REJECTED,
                default => Assessor::STATUS_APPROVED,
            };

            $assessor = Assessor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => "Kfz-Sachverständigenbüro {$surname}",
                    'legal_form' => fake()->randomElement(['einzelunternehmen', 'gmbh', 'ug', 'gbr']),
                    'street' => fake('de_DE')->streetName(),
                    'house_number' => (string) fake()->numberBetween(1, 180),
                    'postal_code' => $plz,
                    'city' => $city,
                    'country' => 'DE',
                    'vat_id' => 'DE'.fake()->numerify('#########'),
                    'website' => 'www.'.strtolower(preg_replace('/[^a-z]/i', '', $surname)).'-gutachten.de',
                    'certification_body' => fake()->randomElement(['tuev', 'dekra', 'gtue', 'kues', 'bvsk']),
                    'certification_number' => fake()->bothify('??-#####'),
                    'certification_valid_until' => now()->addMonths(fake()->numberBetween(8, 48)),
                    'years_experience' => fake()->numberBetween(3, 28),
                    'is_available' => $index !== 20,
                    'approval_status' => $status,
                    'approved_at' => $status === Assessor::STATUS_APPROVED ? now()->subDays(fake()->numberBetween(10, 500)) : null,
                    'rejection_reason' => $status === Assessor::STATUS_REJECTED ? 'Der eingereichte Qualifikationsnachweis war nicht lesbar.' : null,
                    'suspension_reason' => $status === Assessor::STATUS_SUSPENDED ? 'Wiederholt keine Rückmeldung auf vermittelte Anfragen.' : null,
                    'suspended_at' => $status === Assessor::STATUS_SUSPENDED ? now()->subDays(9) : null,
                ]
            );

            AssessorServiceArea::firstOrCreate(
                ['assessor_id' => $assessor->id, 'postal_code_from' => $from, 'postal_code_to' => $to],
                ['label' => $label]
            );

            $assessor->serviceTypes()->syncWithoutDetaching(
                $serviceTypes->random(fake()->numberBetween(3, 6))->pluck('id')->all()
            );

            $assessors->push($assessor->fresh('serviceTypes', 'serviceAreas'));
        }

        return $assessors;
    }

    /**
     * 60 requests hitting deliberate target volumes: 5 left unmatched so the
     * admin "Nicht vermittelt" panel has records, 30 accepted assignments, and
     * 20 of those carried through to completion so the commission register is
     * populated across all four statuses.
     */
    private function createRequests($serviceTypes, $assessors): void
    {
        $approved = $assessors->filter(fn (Assessor $a) => $a->isApproved() && $a->is_available)->values();
        $rate = 15.00;

        $targetUnmatched = 5;
        $targetAssignments = 30;
        $targetCompletions = 20;

        $accepted = 0;
        $completed = 0;
        $unmatched = 0;

        for ($i = 0; $i < 60; $i++) {
            [$city, $plz] = self::CITIES[$i % count(self::CITIES)];

            // Assessors whose area actually spans this postal code.
            $covering = $approved->filter(
                fn (Assessor $a) => $a->serviceAreas->contains(fn ($area) => $area->covers($plz))
            )->values();

            // The first few requests are steered into postal codes nobody
            // covers, which is the only honest way to produce the zero-match
            // state the dashboard has to surface.
            $forceUnmatched = $unmatched < $targetUnmatched && $i % 11 === 0;

            if ($forceUnmatched || $covering->isEmpty()) {
                ServiceRequest::factory()
                    ->inPostalCode($forceUnmatched ? '99998' : $plz, $forceUnmatched ? 'Musterort' : $city)
                    ->unmatched()
                    ->create([
                        'reference' => ServiceRequest::nextReference(),
                        'service_type_id' => $serviceTypes->random()->id,
                        'created_at' => now()->subDays(fake()->numberBetween(0, 90)),
                    ]);

                $unmatched++;

                continue;
            }

            // Pick a service type at least one covering assessor offers, so the
            // request genuinely matches rather than silently falling through.
            $offered = $covering
                ->flatMap(fn (Assessor $a) => $a->serviceTypes->pluck('id'))
                ->unique()
                ->values();

            $typeId = $offered->isEmpty() ? $serviceTypes->random()->id : $offered->random();

            $request = ServiceRequest::factory()
                ->inPostalCode($plz, $city)
                ->create([
                    'reference' => ServiceRequest::nextReference(),
                    'service_type_id' => $typeId,
                    'created_at' => now()->subDays(fake()->numberBetween(0, 90)),
                ]);

            $matching = $covering->filter(
                fn (Assessor $a) => $a->serviceTypes->contains('id', $typeId)
            )->values();

            foreach ($matching as $assessor) {
                RequestMatch::create([
                    'service_request_id' => $request->id,
                    'assessor_id' => $assessor->id,
                    'outcome' => RequestMatch::OUTCOME_PENDING,
                    'notified_at' => $request->created_at,
                ]);
            }

            $request->update([
                'status' => ServiceRequest::STATUS_MATCHED,
                'matched_count' => $matching->count(),
            ]);

            if ($accepted >= $targetAssignments) {
                // Leave the rest open, and let one cohort be fully declined so
                // the "needs attention" state has records too.
                if ($i % 5 === 0) {
                    RequestMatch::where('service_request_id', $request->id)->update([
                        'outcome' => RequestMatch::OUTCOME_DECLINED,
                        'responded_at' => $request->created_at->copy()->addHours(3),
                        'viewed_at' => $request->created_at->copy()->addHours(2),
                        'decline_reason' => 'Terminlich nicht darstellbar',
                    ]);
                }

                continue;
            }

            $winner = $matching->random();
            $carryToCompletion = $completed < $targetCompletions;

            $this->acceptAndProgress($request, $winner, $rate, $carryToCompletion);

            $accepted++;

            if ($carryToCompletion) {
                $completed++;
            }
        }
    }

    private function acceptAndProgress(ServiceRequest $request, Assessor $winner, float $rate, bool $carryToCompletion): void
    {
        $acceptedAt = $request->created_at->copy()->addHours(fake()->numberBetween(1, 20));

        $assignment = Assignment::create([
            'service_request_id' => $request->id,
            'assessor_id' => $winner->id,
            'status' => Assignment::STATUS_ACCEPTED,
            'accepted_at' => $acceptedAt,
        ]);

        $request->update(['status' => ServiceRequest::STATUS_ASSIGNED, 'assigned_at' => $acceptedAt]);

        RequestMatch::where('service_request_id', $request->id)
            ->where('assessor_id', $winner->id)
            ->update(['outcome' => RequestMatch::OUTCOME_ACCEPTED, 'responded_at' => $acceptedAt, 'viewed_at' => $acceptedAt]);

        RequestMatch::where('service_request_id', $request->id)
            ->where('assessor_id', '!=', $winner->id)
            ->update(['outcome' => RequestMatch::OUTCOME_CLOSED, 'responded_at' => $acceptedAt]);

        $assignment->recordStatusEvent(null, Assignment::STATUS_ACCEPTED, 'assessor', $winner->user_id);

        if (! $carryToCompletion) {
            return;
        }

        $completedAt = $acceptedAt->copy()->addDays(fake()->numberBetween(1, 8));
        $fee = fake()->numberBetween(48_000, 290_000);

        foreach ([AssignmentDocument::TYPE_REPORT, AssignmentDocument::TYPE_CUSTOMER_INVOICE] as $type) {
            AssignmentDocument::create([
                'assignment_id' => $assignment->id,
                'type' => $type,
                'path' => 'auftraege/'.fake()->uuid().'.pdf',
                'original_name' => $type === AssignmentDocument::TYPE_REPORT ? 'Gutachten.pdf' : 'Rechnung.pdf',
                'mime' => 'application/pdf',
                'size_bytes' => fake()->numberBetween(180_000, 3_400_000),
                'uploaded_at' => $completedAt,
            ]);
        }

        $assignment->update([
            'status' => Assignment::STATUS_COMPLETED,
            'started_at' => $acceptedAt->copy()->addHours(6),
            'completed_at' => $completedAt,
            'fee_cents' => $fee,
            'fee_entered_at' => $completedAt,
        ]);

        $assignment->recordStatusEvent(Assignment::STATUS_ACCEPTED, Assignment::STATUS_IN_PROGRESS, 'assessor', $winner->user_id);
        $assignment->recordStatusEvent(Assignment::STATUS_IN_PROGRESS, Assignment::STATUS_DOCUMENTS_UPLOADED, 'assessor', $winner->user_id);
        $assignment->recordStatusEvent(Assignment::STATUS_DOCUMENTS_UPLOADED, Assignment::STATUS_COMPLETED, 'assessor', $winner->user_id);

        $request->update(['status' => ServiceRequest::STATUS_COMPLETED]);

        $commission = Commission::create([
            'assignment_id' => $assignment->id,
            'assessor_id' => $winner->id,
            'fee_cents' => $fee,
            'rate_percent' => $rate,
            'commission_cents' => Commission::calculateCents($fee, $rate),
            'status' => match (Commission::count() % 5) {
                0, 1 => Commission::STATUS_SETTLED,
                2 => Commission::STATUS_INVOICED,
                3 => Commission::STATUS_WAIVED,
                default => Commission::STATUS_OPEN,
            },
            'created_at' => $completedAt,
        ]);

        if (in_array($commission->status, [Commission::STATUS_INVOICED, Commission::STATUS_SETTLED], true)) {
            $commission->update([
                'invoice_number' => Commission::nextInvoiceNumber((int) $completedAt->format('Y')),
                'invoiced_at' => $completedAt->copy()->addDays(2),
                'settled_at' => $commission->status === Commission::STATUS_SETTLED ? $completedAt->copy()->addDays(12) : null,
            ]);
        }

        if ($commission->status === Commission::STATUS_WAIVED) {
            $commission->update(['notes' => 'Kulanz nach Reklamation des Kunden.']);
        }

        $review = CustomerReview::create([
            'assignment_id' => $assignment->id,
            'token' => CustomerReview::generateToken(),
            'expires_at' => $completedAt->copy()->addDays(30),
        ]);

        // Most customers rate well; a few land under the threshold so the
        // internal feedback step has records too.
        if (fake()->boolean(80)) {
            $rating = fake()->boolean(25) ? fake()->numberBetween(4, 7) : fake()->numberBetween(8, 10);

            $review->update([
                'rating' => $rating,
                'submitted_at' => $completedAt->copy()->addDays(3),
                'feedback_category' => $rating < 8 ? 'Terminfindung' : null,
                'feedback' => $rating < 8 ? 'Die Terminabstimmung hat länger gedauert als erwartet.' : null,
            ]);
        }
    }

    private function createInvitations(): void
    {
        $inviter = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->first();

        if ($inviter === null) {
            return;
        }

        foreach ([
            ['j.ohlsen@sv-nordwest.test', now()->addDays(11), null],
            ['m.brenner@kfz-gutachten-sued.test', now()->addDays(6), null],
            ['t.wagner@sv-mitte.test', now()->subDays(3), null],
            ['s.kellner@gutachten-nord.test', now()->addDays(9), now()->subDays(1)],
        ] as [$email, $expires, $accepted]) {
            Invitation::firstOrCreate(
                ['email' => $email],
                [
                    'role' => 'assessor',
                    'token' => Invitation::generateToken(),
                    'invited_by' => $inviter->id,
                    'message' => 'Wir würden Sie gern als Partner für Ihre Region aufnehmen. Ihre Nachweise liegen uns bereits vor.',
                    'expires_at' => $expires,
                    'accepted_at' => $accepted,
                ]
            );
        }
    }
}
