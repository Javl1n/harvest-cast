<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CropImage>
 */
class CropImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $healthStatus = fake()->randomElement(['healthy', 'warning', 'diseased']);

        return [
            'schedule_id' => \App\Models\Schedule::factory(),
            'file_path' => 'crop-images/test/'.fake()->uuid().'.jpg',
            'file_name' => fake()->uuid().'.jpg',
            'ai_analysis' => [
                'health_status' => $healthStatus,
                'diseases' => $healthStatus === 'diseased' ? ['leaf_spot', 'rust'] : [],
                'confidence' => fake()->randomFloat(2, 0.7, 0.99),
                'recommendations' => [fake()->sentence(), fake()->sentence()],
            ],
            'health_status' => $healthStatus,
            'recommendations' => fake()->sentence(),
            'processed' => true,
            'image_date' => now(),
        ];
    }
}
