<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name_de' => 'Vorlage',
            'subject_de' => 'Ihre Anfrage ist eingegangen — {{ referenz }}',
            'preheader_de' => 'Wir vermitteln Ihre Anfrage jetzt an geeignete Sachverständige.',
            'body_html' => '<p>Guten Tag {{ nachname }},</p>',
            'available_variables' => ['nachname', 'referenz'],
            'is_active' => true,
        ];
    }
}
