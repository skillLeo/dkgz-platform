<?php

namespace Database\Factories;

use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory()->assigned(),
            'assessor_id' => Assessor::factory(),
            'status' => Assignment::STATUS_ACCEPTED,
            'accepted_at' => now()->subDays(2),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => Assignment::STATUS_IN_PROGRESS,
            'started_at' => now()->subDay(),
        ]);
    }

    public function documentsUploaded(): static
    {
        return $this->state(fn () => [
            'status' => Assignment::STATUS_DOCUMENTS_UPLOADED,
            'started_at' => now()->subDays(2),
        ]);
    }

    public function completed(int $feeCents = 164_000): static
    {
        return $this->state(fn () => [
            'status' => Assignment::STATUS_COMPLETED,
            'started_at' => now()->subDays(4),
            'completed_at' => now()->subDay(),
            'fee_cents' => $feeCents,
            'fee_entered_at' => now()->subDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => Assignment::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Der Kunde hat den Auftrag zurückgezogen.',
        ]);
    }
}
