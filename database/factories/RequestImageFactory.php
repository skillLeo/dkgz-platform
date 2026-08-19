<?php

namespace Database\Factories;

use App\Models\RequestImage;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestImage>
 */
class RequestImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'path' => 'anfragen/'.fake()->uuid().'.webp',
            'original_name' => 'Schaden.jpg',
            'mime' => 'image/webp',
            'size_bytes' => fake()->numberBetween(120_000, 4_500_000),
        ];
    }
}
