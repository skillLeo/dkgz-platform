<?php

namespace Database\Factories;

use App\Models\Assessor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessor>
 */
class AssessorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => 'Kfz-Sachverständigenbüro '.fake('de_DE')->lastName(),
            'legal_form' => fake()->randomElement(['einzelunternehmen', 'gmbh', 'ug', 'gbr']),
            'street' => fake('de_DE')->streetName(),
            'house_number' => (string) fake()->numberBetween(1, 180),
            'postal_code' => fake()->numerify('#####'),
            'city' => fake('de_DE')->city(),
            'country' => 'DE',
            'vat_id' => 'DE'.fake()->numerify('#########'),
            'website' => 'www.'.fake()->domainWord().'.de',
            'certification_body' => fake()->randomElement(['tuev', 'dekra', 'gtue', 'kues', 'bvsk']),
            'certification_number' => fake()->bothify('??-#####'),
            'certification_valid_until' => fake()->dateTimeBetween('+6 months', '+4 years'),
            'years_experience' => fake()->numberBetween(2, 30),
            'is_available' => true,
            'approval_status' => Assessor::STATUS_APPROVED,
            'approved_at' => now()->subDays(fake()->numberBetween(1, 400)),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'approval_status' => Assessor::STATUS_PENDING,
            'approved_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'approval_status' => Assessor::STATUS_REJECTED,
            'approved_at' => null,
            'rejection_reason' => 'Der Qualifikationsnachweis war nicht ausreichend.',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'approval_status' => Assessor::STATUS_SUSPENDED,
            'suspended_at' => now()->subDays(3),
            'suspension_reason' => 'Wiederholt keine Rückmeldung auf vermittelte Anfragen.',
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn () => ['is_available' => false]);
    }
}
