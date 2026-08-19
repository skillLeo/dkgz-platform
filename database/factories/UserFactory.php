<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        $first = fake('de_DE')->firstName();
        $last = fake('de_DE')->lastName();

        return [
            'first_name' => $first,
            'last_name' => $last,
            'name' => "{$first} {$last}",
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Gutachten2026!'),
            'phone' => '+49 '.fake()->numberBetween(150, 179).' '.fake()->numerify('#######'),
            'locale' => 'de',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
