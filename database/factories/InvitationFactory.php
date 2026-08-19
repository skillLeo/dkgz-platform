<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'role' => 'assessor',
            'token' => Invitation::generateToken(),
            'invited_by' => User::factory(),
            'message' => 'Wir würden Sie gern als Partner in unser Netz aufnehmen.',
            'expires_at' => now()->addDays(14),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['accepted_at' => now()->subDay()]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }
}
