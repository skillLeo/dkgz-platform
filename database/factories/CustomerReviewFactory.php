<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\CustomerReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerReview>
 */
class CustomerReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory()->completed(),
            'token' => CustomerReview::generateToken(),
            'rating' => null,
            'expires_at' => now()->addDays(30),
        ];
    }

    public function submitted(int $rating = 9): static
    {
        return $this->state(fn () => [
            'rating' => $rating,
            'submitted_at' => now(),
        ]);
    }

    /** A low score, which routes into the internal feedback step. */
    public function critical(): static
    {
        return $this->state(fn () => [
            'rating' => fake()->numberBetween(1, 7),
            'submitted_at' => now(),
            'feedback_category' => fake()->randomElement([
                'Erreichbarkeit des Sachverständigen',
                'Terminfindung',
                'Dauer bis zum Gutachten',
                'Qualität der Unterlagen',
                'Kommunikation',
                'Kosten',
                'Sonstiges',
            ]),
            'feedback' => 'Die Rückmeldung hat länger gedauert als angekündigt.',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }
}
