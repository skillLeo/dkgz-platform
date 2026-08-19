<?php

namespace Database\Factories;

use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\Commission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commission>
 */
class CommissionFactory extends Factory
{
    public function definition(): array
    {
        $fee = fake()->numberBetween(45_000, 320_000);
        $rate = 15.00;

        return [
            'assignment_id' => Assignment::factory()->completed($fee),
            'assessor_id' => Assessor::factory(),
            'fee_cents' => $fee,
            'rate_percent' => $rate,
            'commission_cents' => Commission::calculateCents($fee, $rate),
            'status' => Commission::STATUS_OPEN,
        ];
    }

    public function invoiced(): static
    {
        return $this->state(fn () => [
            'status' => Commission::STATUS_INVOICED,
            'invoice_number' => sprintf('DKGZ-RE-%d-%04d', now()->year, fake()->unique()->numberBetween(1, 9999)),
            'invoiced_at' => now()->subDays(5),
        ]);
    }

    public function settled(): static
    {
        return $this->state(fn () => [
            'status' => Commission::STATUS_SETTLED,
            'invoice_number' => sprintf('DKGZ-RE-%d-%04d', now()->year, fake()->unique()->numberBetween(1, 9999)),
            'invoiced_at' => now()->subDays(20),
            'settled_at' => now()->subDays(3),
        ]);
    }

    public function waived(): static
    {
        return $this->state(fn () => [
            'status' => Commission::STATUS_WAIVED,
            'notes' => 'Kulanz nach Reklamation des Kunden.',
        ]);
    }
}
