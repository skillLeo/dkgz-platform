<?php

namespace Database\Factories;

use App\Models\PostalCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostalCode>
 */
class PostalCodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('#####'),
            'city' => fake('de_DE')->city(),
            'state' => fake()->randomElement([
                'Nordrhein-Westfalen', 'Bayern', 'Baden-Württemberg', 'Niedersachsen',
                'Hessen', 'Berlin', 'Hamburg', 'Bremen', 'Sachsen',
            ]),
        ];
    }
}
