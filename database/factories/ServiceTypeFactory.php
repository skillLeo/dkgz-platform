<?php

namespace Database\Factories;

use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceType>
 */
class ServiceTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Haftpflichtgutachten', 'Kaskogutachten', 'Unfallgutachten', 'Wertgutachten',
            'Oldtimergutachten', 'Gebrauchtwagen-Check', 'Reparaturbestätigung', 'Beweissicherung',
        ]);

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'name_de' => $name,
            'description_de' => 'Begutachtung durch einen geprüften Kfz-Sachverständigen.',
            'icon' => 'file-text',
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
