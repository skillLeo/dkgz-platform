<?php

namespace Database\Factories;

use App\Models\ServiceRequest;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        $first = fake('de_DE')->firstName();
        $last = fake('de_DE')->lastName();
        $makes = [
            'VW' => ['Passat B8', 'Golf VII', 'Tiguan', 'Polo'],
            'BMW' => ['320d', 'X3', '118i', '530e'],
            'Mercedes-Benz' => ['C 220 d', 'A 180', 'GLC 300', 'Vito'],
            'Audi' => ['A4 Avant', 'Q5', 'A3 Sportback'],
            'Opel' => ['Astra K', 'Corsa F', 'Insignia'],
            'Ford' => ['Focus', 'Kuga', 'Transit'],
        ];
        $make = fake()->randomElement(array_keys($makes));

        return [
            'reference' => 'DKGZ'.now()->format('ym').str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'service_type_id' => ServiceType::factory(),
            'postal_code' => fake()->numerify('#####'),
            'city' => fake('de_DE')->city(),
            'customer_name' => "{$first} {$last}",
            'customer_phone' => '+49 '.fake()->numberBetween(150, 179).' '.fake()->numerify('#######'),
            'customer_email' => fake()->unique()->safeEmail(),
            'vehicle_make' => $make,
            'vehicle_model' => fake()->randomElement($makes[$make]),
            'vehicle_year' => fake()->numberBetween(2005, (int) now()->format('Y')),
            'vehicle_plate' => strtoupper(fake()->lexify('??')).'-'.strtoupper(fake()->lexify('??')).' '.fake()->numerify('####'),
            'vehicle_vin' => null,
            'description' => fake()->randomElement([
                'Heckschaden nach Auffahrunfall, Fahrzeug ist fahrbereit.',
                'Parkrempler an der Beifahrertür, Lack und Blech betroffen.',
                'Frontschaden nach Wildunfall, Kühler undicht.',
                'Hagelschaden am gesamten Dach und an der Motorhaube.',
                null,
            ]),
            'urgency' => fake()->randomElement(['normal', 'soon', 'urgent', null]),
            'status' => ServiceRequest::STATUS_NEW,
            'matched_count' => 0,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'consent_at' => now(),
        ];
    }

    public function matched(int $count = 3): static
    {
        return $this->state(fn () => [
            'status' => ServiceRequest::STATUS_MATCHED,
            'matched_count' => $count,
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn () => [
            'status' => ServiceRequest::STATUS_ASSIGNED,
            'matched_count' => 3,
            'assigned_at' => now()->subDays(2),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ServiceRequest::STATUS_COMPLETED,
            'matched_count' => 3,
            'assigned_at' => now()->subDays(6),
        ]);
    }

    /** No assessor covered the postal code — the "Nicht vermittelt" state. */
    public function unmatched(): static
    {
        return $this->state(fn () => [
            'status' => ServiceRequest::STATUS_NEW,
            'matched_count' => 0,
        ]);
    }

    public function inPostalCode(string $postalCode, ?string $city = null): static
    {
        return $this->state(fn () => [
            'postal_code' => $postalCode,
            'city' => $city ?? fake('de_DE')->city(),
        ]);
    }
}
