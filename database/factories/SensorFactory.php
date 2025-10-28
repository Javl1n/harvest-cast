<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sensor>
 */
class SensorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mac_address' => fake()->unique()->macAddress(),
        ];
    }

    public function configure(): static
    {
        return $this->has(
            \App\Models\SensorReading::factory()->count(5),
            'readings'
        );
    }
}
