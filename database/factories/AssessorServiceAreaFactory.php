<?php

namespace Database\Factories;

use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessorServiceArea>
 */
class AssessorServiceAreaFactory extends Factory
{
    public function definition(): array
    {
        $from = fake()->numberBetween(10000, 98000);

        return [
            'assessor_id' => Assessor::factory(),
            'postal_code_from' => str_pad((string) $from, 5, '0', STR_PAD_LEFT),
            'postal_code_to' => str_pad((string) ($from + 999), 5, '0', STR_PAD_LEFT),
            'label' => null,
        ];
    }

    /** A range that provably covers the given postal code. */
    public function covering(string $postalCode): static
    {
        $numeric = (int) $postalCode;

        return $this->state(fn () => [
            'postal_code_from' => str_pad((string) max(0, $numeric - 50), 5, '0', STR_PAD_LEFT),
            'postal_code_to' => str_pad((string) min(99999, $numeric + 50), 5, '0', STR_PAD_LEFT),
        ]);
    }

    /** A range that provably does not cover the given postal code. */
    public function excluding(string $postalCode): static
    {
        $numeric = (int) $postalCode;
        $from = $numeric < 50000 ? 90000 : 10000;

        return $this->state(fn () => [
            'postal_code_from' => str_pad((string) $from, 5, '0', STR_PAD_LEFT),
            'postal_code_to' => str_pad((string) ($from + 500), 5, '0', STR_PAD_LEFT),
        ]);
    }
}
