<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\AssignmentStatusEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentStatusEvent>
 */
class AssignmentStatusEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'from_status' => Assignment::STATUS_ACCEPTED,
            'to_status' => Assignment::STATUS_IN_PROGRESS,
            'actor_type' => 'assessor',
            'created_at' => now(),
        ];
    }
}
