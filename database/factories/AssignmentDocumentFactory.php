<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\AssignmentDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentDocument>
 */
class AssignmentDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'type' => AssignmentDocument::TYPE_REPORT,
            'path' => 'auftraege/'.fake()->uuid().'.pdf',
            'original_name' => 'Gutachten.pdf',
            'mime' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(180_000, 4_000_000),
            'uploaded_at' => now(),
        ];
    }

    public function report(): static
    {
        return $this->state(fn () => [
            'type' => AssignmentDocument::TYPE_REPORT,
            'original_name' => 'Gutachten.pdf',
        ]);
    }

    public function customerInvoice(): static
    {
        return $this->state(fn () => [
            'type' => AssignmentDocument::TYPE_CUSTOMER_INVOICE,
            'original_name' => 'Rechnung.pdf',
        ]);
    }
}
