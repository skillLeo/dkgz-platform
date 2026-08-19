<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group' => 'business',
            'key' => 'business.'.fake()->unique()->word(),
            'value' => (string) fake()->numberBetween(1, 100),
            'type' => 'string',
            'is_encrypted' => false,
            'label_de' => 'Einstellung',
            'sort_order' => 0,
        ];
    }

    public function encrypted(): static
    {
        return $this->state(fn () => ['type' => 'encrypted', 'is_encrypted' => true]);
    }
}
