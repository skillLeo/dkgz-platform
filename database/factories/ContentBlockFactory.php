<?php

namespace Database\Factories;

use App\Models\ContentBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentBlock>
 */
class ContentBlockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'page_key' => 'startseite',
            'section_key' => fake()->unique()->word(),
            'field_key' => 'ueberschrift',
            'type' => 'text',
            'value' => 'Kfz-Sachverständigen finden',
            'label_de' => 'Überschrift',
            'sort_order' => 0,
        ];
    }
}
