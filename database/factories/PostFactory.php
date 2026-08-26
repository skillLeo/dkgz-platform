<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Wer zahlt das Gutachten nach einem Unfall?',
            'category' => 'Unfall und Schaden',
            'excerpt' => 'Nach einem unverschuldeten Unfall trägt in der Regel die gegnerische Haftpflichtversicherung die Kosten des Gutachtens.',
            'body' => '<p>Nach einem unverschuldeten Unfall haben Sie das Recht, einen eigenen Sachverständigen zu beauftragen.</p>',
            'author' => 'DKGZ-Redaktion',
            'is_published' => false,
            'published_at' => null,
        ];
    }

    /** Live now, which is what most tests mean by a post. */
    public function published(): static
    {
        return $this->state(['is_published' => true, 'published_at' => now()->subDay()]);
    }

    /** Ticked, but dated ahead: written and waiting. */
    public function scheduled(): static
    {
        return $this->state(['is_published' => true, 'published_at' => now()->addWeek()]);
    }
}
