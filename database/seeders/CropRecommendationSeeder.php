<?php

namespace Database\Seeders;

use App\Models\CropRecommendation;
use App\Models\Commodity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CropRecommendationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cropData = [
            'Rice' => [
                'moisture_min' => 80, 'moisture_max' => 100,
                'temperature_min' => 20, 'temperature_max' => 35,
                'seasons' => ['summer', 'spring'],
                'planting_months' => [4, 5, 6, 7],
                'favorable_weather' => ['Clear', 'Clouds', 'Rain'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Requires flooded fields. Plant seedlings in puddled soil. Maintain 2-5cm water depth.',
                'harvest_time' => '120-150 days',
                'harvest_days' => 135,
                'optimal_conditions' => 'Flooded fields with warm temperatures and high humidity',
                'water_requirements' => 'Very High - requires continuous flooding'
            ],
            'Corn' => [
                'moisture_min' => 50, 'moisture_max' => 70,
                'temperature_min' => 18, 'temperature_max' => 32,
                'seasons' => ['summer', 'spring'],
                'planting_months' => [4, 5, 6],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Plant when soil temperature reaches 10°C. Space rows 75cm apart. Plant 2-3cm deep.',
                'harvest_time' => '90-120 days',
                'harvest_days' => 105,
                'optimal_conditions' => 'Well-drained soil with warm temperatures and moderate rainfall',
                'water_requirements' => 'Moderate - requires consistent moisture during grain filling'
            ],
            'Ampalaya' => [
                'moisture_min' => 60, 'moisture_max' => 80,
                'temperature_min' => 22, 'temperature_max' => 32,
                'seasons' => ['summer'],
                'planting_months' => [4, 5, 6, 7],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Warm season vine crop. Provide strong trellises. Plant after last frost.',
                'harvest_time' => '55-75 days',
                'harvest_days' => 65,
                'optimal_conditions' => 'Hot, humid climate with well-draining soil',
                'water_requirements' => 'High - consistent moisture during fruit development'
            ],
            'Eggplant' => [
                'moisture_min' => 65, 'moisture_max' => 85,
                'temperature_min' => 20, 'temperature_max' => 30,
                'seasons' => ['summer'],
                'planting_months' => [4, 5, 6],
                'favorable_weather' => ['Clear'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Heat-loving crop. Start indoors and transplant after danger of frost.',
                'harvest_time' => '70-85 days',
                'harvest_days' => 78,
                'optimal_conditions' => 'Warm temperatures with high humidity and rich soil',
                'water_requirements' => 'High - requires consistent moisture'
            ],
            'Pechay' => [
                'moisture_min' => 70, 'moisture_max' => 85,
                'temperature_min' => 15, 'temperature_max' => 25,
                'seasons' => ['spring', 'autumn', 'winter'],
                'planting_months' => [3, 4, 9, 10, 11],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Cool weather leafy green. Can tolerate light frost. Succession plant.',
                'harvest_time' => '30-45 days',
                'harvest_days' => 38,
                'optimal_conditions' => 'Cool, moist conditions with partial shade in hot weather',
                'water_requirements' => 'High - consistent moisture for tender leaves'
            ],
            'Pechay Baguio' => [
                'moisture_min' => 70, 'moisture_max' => 85,
                'temperature_min' => 10, 'temperature_max' => 20,
                'seasons' => ['spring', 'autumn', 'winter'],
                'planting_months' => [3, 4, 8, 9, 10],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Cool season variety. Prefers cooler temperatures than regular pechay.',
                'harvest_time' => '35-50 days',
                'harvest_days' => 43,
                'optimal_conditions' => 'Cool temperatures with high humidity and rich soil',
                'water_requirements' => 'High - consistent moisture essential'
            ],
            'Pole Sitao' => [
                'moisture_min' => 55, 'moisture_max' => 75,
                'temperature_min' => 20, 'temperature_max' => 30,
                'seasons' => ['summer'],
                'planting_months' => [4, 5, 6, 7],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Climbing variety needs strong support. Plant after soil warms.',
                'harvest_time' => '60-75 days',
                'harvest_days' => 68,
                'optimal_conditions' => 'Warm weather with good air circulation',
                'water_requirements' => 'Moderate - avoid overwatering'
            ],
            'Squash' => [
                'moisture_min' => 60, 'moisture_max' => 80,
                'temperature_min' => 18, 'temperature_max' => 32,
                'seasons' => ['summer'],
                'planting_months' => [4, 5, 6],
                'favorable_weather' => ['Clear'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Warm season crop. Provide plenty of space for vining varieties.',
                'harvest_time' => '50-90 days',
                'harvest_days' => 70,
                'optimal_conditions' => 'Warm temperatures with rich, well-draining soil',
                'water_requirements' => 'High - requires consistent moisture during fruit development'
            ],
            'Tomato' => [
                'moisture_min' => 60, 'moisture_max' => 80,
                'temperature_min' => 18, 'temperature_max' => 26,
                'seasons' => ['spring', 'summer'],
                'planting_months' => [3, 4, 5],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Rain', 'Thunderstorm'],
                'planting_tips' => 'Plant after last frost. Ensure good drainage and support structures.',
                'harvest_time' => '60-80 days',
                'harvest_days' => 70,
                'optimal_conditions' => 'Warm, sunny location with consistent watering',
                'water_requirements' => 'Moderate - consistent moisture, avoid waterlogging'
            ],
            'Bell Pepper' => [
                'moisture_min' => 55, 'moisture_max' => 75,
                'temperature_min' => 20, 'temperature_max' => 30,
                'seasons' => ['summer'],
                'planting_months' => [4, 5, 6],
                'favorable_weather' => ['Clear'],
                'unfavorable_weather' => ['Rain', 'Thunderstorm'],
                'planting_tips' => 'Heat-loving crop. Start indoors and transplant after danger of frost.',
                'harvest_time' => '70-90 days',
                'harvest_days' => 80,
                'optimal_conditions' => 'Warm, sunny location with moderate, consistent watering',
                'water_requirements' => 'Moderate - consistent moisture during fruit development'
            ],
            'Broccoli' => [
                'moisture_min' => 60, 'moisture_max' => 75,
                'temperature_min' => 10, 'temperature_max' => 20,
                'seasons' => ['spring', 'autumn'],
                'planting_months' => [3, 4, 8, 9],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Cool weather crop. Harvest before flowers open for best quality.',
                'harvest_time' => '60-90 days',
                'harvest_days' => 75,
                'optimal_conditions' => 'Cool temperatures with consistent moisture and rich soil',
                'water_requirements' => 'High - requires consistent moisture'
            ],
            'Cauliflower' => [
                'moisture_min' => 65, 'moisture_max' => 80,
                'temperature_min' => 10, 'temperature_max' => 20,
                'seasons' => ['spring', 'autumn'],
                'planting_months' => [3, 4, 8, 9],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Cool season crop. May need blanching for white heads.',
                'harvest_time' => '70-100 days',
                'harvest_days' => 85,
                'optimal_conditions' => 'Cool temperatures with steady moisture',
                'water_requirements' => 'High - consistent moisture essential'
            ],
            'Cabbage' => [
                'moisture_min' => 65, 'moisture_max' => 80,
                'temperature_min' => 10, 'temperature_max' => 22,
                'seasons' => ['spring', 'autumn'],
                'planting_months' => [3, 4, 8, 9],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Cool season crop. Needs consistent moisture for proper head formation.',
                'harvest_time' => '80-100 days',
                'harvest_days' => 90,
                'optimal_conditions' => 'Cool temperatures with steady moisture and rich soil',
                'water_requirements' => 'High - requires consistent moisture'
            ],
            'Carrots' => [
                'moisture_min' => 50, 'moisture_max' => 70,
                'temperature_min' => 15, 'temperature_max' => 25,
                'seasons' => ['spring', 'summer', 'autumn'],
                'planting_months' => [3, 4, 5, 8, 9],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Requires deep, loose soil. Thin seedlings for proper root development.',
                'harvest_time' => '70-80 days',
                'harvest_days' => 75,
                'optimal_conditions' => 'Deep, well-draining soil with moderate moisture',
                'water_requirements' => 'Moderate - consistent but not excessive moisture'
            ],
            'Celery' => [
                'moisture_min' => 75, 'moisture_max' => 90,
                'temperature_min' => 15, 'temperature_max' => 25,
                'seasons' => ['spring', 'autumn'],
                'planting_months' => [3, 4, 8, 9],
                'favorable_weather' => ['Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Requires consistent moisture and rich soil. May need blanching.',
                'harvest_time' => '85-120 days',
                'harvest_days' => 103,
                'optimal_conditions' => 'Cool, moist conditions with rich organic soil',
                'water_requirements' => 'Very High - never allow to dry out'
            ],
            'Chayote' => [
                'moisture_min' => 60, 'moisture_max' => 80,
                'temperature_min' => 18, 'temperature_max' => 28,
                'seasons' => ['summer'],
                'planting_months' => [4, 5, 6],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Perennial vine. Plant whole fruit. Needs strong support structure.',
                'harvest_time' => '120-150 days',
                'harvest_days' => 135,
                'optimal_conditions' => 'Warm, humid climate with well-draining soil',
                'water_requirements' => 'High - consistent moisture needed'
            ],
            'Habichuelas/Baguio Beans' => [
                'moisture_min' => 55, 'moisture_max' => 75,
                'temperature_min' => 15, 'temperature_max' => 25,
                'seasons' => ['spring', 'autumn'],
                'planting_months' => [3, 4, 8, 9],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Cool weather beans. Plant after last frost but before hot weather.',
                'harvest_time' => '50-65 days',
                'harvest_days' => 58,
                'optimal_conditions' => 'Cool temperatures with moderate moisture',
                'water_requirements' => 'Moderate - avoid overwatering'
            ],
            'Lettuce' => [
                'moisture_min' => 70, 'moisture_max' => 85,
                'temperature_min' => 10, 'temperature_max' => 20,
                'seasons' => ['spring', 'autumn'],
                'planting_months' => [3, 4, 9, 10],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Cool weather crop. Plant in partial shade during warmer months.',
                'harvest_time' => '45-65 days',
                'harvest_days' => 55,
                'optimal_conditions' => 'Cool temperatures with consistent moisture',
                'water_requirements' => 'High - requires consistent moisture'
            ],
            'White Potato' => [
                'moisture_min' => 45, 'moisture_max' => 65,
                'temperature_min' => 15, 'temperature_max' => 25,
                'seasons' => ['spring', 'autumn'],
                'planting_months' => [3, 4, 8, 9],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Plant seed potatoes in hills. Hill soil around plants as they grow.',
                'harvest_time' => '70-120 days',
                'harvest_days' => 95,
                'optimal_conditions' => 'Cool temperatures with well-draining soil',
                'water_requirements' => 'Moderate - avoid overwatering to prevent rot'
            ]
        ];

        foreach ($cropData as $commodityName => $data) {
            $commodity = Commodity::where('name', $commodityName)->first();
            
            if ($commodity) {
                CropRecommendation::create(array_merge([
                    'commodity_id' => $commodity->id
                ], $data));
            }
        }
    }
}
