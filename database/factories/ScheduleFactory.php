<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $datePlanted = fake()->dateTimeBetween('-60 days', '-30 days');
        $expectedHarvestDate = (clone $datePlanted)->modify('+90 days');

        $commodity = \App\Models\Commodity::first() ?? \App\Models\Commodity::create([
            'name' => 'Test Crop',
        ]);

        return [
            'commodity_id' => $commodity->id,
            'sensor_id' => \App\Models\Sensor::factory(),
            'acres' => fake()->randomFloat(2, 0.5, 3),
            'seed_weight_kg' => fake()->randomFloat(2, 1, 50),
            'date_planted' => $datePlanted,
            'expected_harvest_date' => $expectedHarvestDate,
            'actual_harvest_date' => null,
            'yield' => null,
            'expected_yield' => fake()->randomFloat(2, 100, 1000),
            'expected_income' => fake()->randomFloat(2, 5000, 50000),
            'income' => null,
        ];
    }
}
