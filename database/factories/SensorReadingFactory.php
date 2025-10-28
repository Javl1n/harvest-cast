<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SensorReading>
 */
class SensorReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseLat = 6.293678393893611;
        $baseLon = 124.96897686553639;

        // Generate coordinates within a rotated rectangle (45 degrees)
        // Rectangle dimensions: ~400m x ~200m
        $x = fake()->randomFloat(6, -0.0018, 0.0018); // ~400m width
        $y = fake()->randomFloat(6, -0.0009, 0.0009); // ~200m height

        // Rotate by 45 degrees
        $angle = deg2rad(45);
        $rotatedX = $x * cos($angle) - $y * sin($angle);
        $rotatedY = $x * sin($angle) + $y * cos($angle);

        return [
            'latitude' => $baseLat + $rotatedY,
            'longitude' => $baseLon + $rotatedX,
            'moisture' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
