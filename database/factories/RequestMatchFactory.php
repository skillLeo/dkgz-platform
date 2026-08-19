<?php

namespace Database\Factories;

use App\Models\Assessor;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestMatch>
 */
class RequestMatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'assessor_id' => Assessor::factory(),
            'outcome' => RequestMatch::OUTCOME_PENDING,
            'notified_at' => now(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'outcome' => RequestMatch::OUTCOME_ACCEPTED,
            'viewed_at' => now()->subMinutes(20),
            'responded_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn () => [
            'outcome' => RequestMatch::OUTCOME_DECLINED,
            'viewed_at' => now()->subMinutes(30),
            'responded_at' => now(),
            'decline_reason' => fake()->randomElement([
                'Terminlich nicht darstellbar',
                'Außerhalb des tatsächlichen Einsatzgebiets',
                'Kapazität derzeit ausgeschöpft',
            ]),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'outcome' => RequestMatch::OUTCOME_CLOSED,
            'responded_at' => now(),
        ]);
    }
}
