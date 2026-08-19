<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_de' => 'Ist die Anfrage kostenfrei?',
            'answer_de' => 'Ja. Die Anfrage und die Vermittlung über DKGZ sind für Sie kostenfrei.',
            'category' => 'Allgemein',
            'sort_order' => fake()->numberBetween(0, 20),
            'is_published' => true,
        ];
    }
}
