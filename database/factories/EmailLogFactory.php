<?php

namespace Database\Factories;

use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailLog>
 */
class EmailLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'template_key' => 'anfrage-eingegangen',
            'recipient' => fake()->safeEmail(),
            'subject' => 'Ihre Anfrage ist eingegangen',
            'status' => EmailLog::STATUS_SENT,
            'sent_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => EmailLog::STATUS_FAILED,
            'sent_at' => null,
            'error' => 'Connection could not be established with host smtp.example.de',
        ]);
    }
}
