<?php

namespace App\Services;

use App\Models\Weather;

class CropCareRecommendationService
{
    public function getCareRecommendations($soilMoisture, $weather, $commodity, $daysSincePlanting = null)
    {
        $recommendations = [];

        if (! $commodity) {
            return $recommendations;
        }

        $cropName = strtolower($commodity->name);
        $temperature = $weather?->temperature ?? 25;
        $weatherCondition = $weather?->condition ?? 'clear';
        $humidity = $weather?->humidity ?? 60;

        // Watering recommendations based on soil moisture
        $recommendations[] = $this->getWateringRecommendation($soilMoisture, $cropName, $weatherCondition);

        // Weather-based care recommendations
        $recommendations = array_merge($recommendations, $this->getWeatherBasedRecommendations($weatherCondition, $temperature, $humidity, $cropName));

        // Growth stage recommendations
        if ($daysSincePlanting) {
            $recommendations = array_merge($recommendations, $this->getGrowthStageRecommendations($daysSincePlanting, $cropName));
        }

        // General care based on soil moisture and temperature
        $recommendations = array_merge($recommendations, $this->getGeneralCareRecommendations($soilMoisture, $temperature, $cropName));

        // Filter out null recommendations and add priority
        $recommendations = array_filter($recommendations, function ($rec) {
            return $rec !== null;
        });

        // Sort by priority (high, medium, low)
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];

            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });

        return $recommendations;
    }

    private function getWateringRecommendation($soilMoisture, $cropName, $weatherCondition)
    {
        if ($soilMoisture < 30) {
            return [
                'action' => 'Water Immediately',
                'description' => 'Soil moisture is critically low. Water deeply to prevent plant stress.',
                'icon' => 'droplets',
                'priority' => 'high',
                'category' => 'watering',
            ];
        } elseif ($soilMoisture < 50) {
            $urgency = $weatherCondition === 'sunny' ? 'today' : 'within 1-2 days';

            return [
                'action' => 'Water Soon',
                'description' => "Soil moisture is below optimal. Plan to water {$urgency}.",
                'icon' => 'droplets',
                'priority' => 'medium',
                'category' => 'watering',
            ];
        } elseif ($soilMoisture > 80) {
            return [
                'action' => 'Check Drainage',
                'description' => 'Soil moisture is very high. Ensure proper drainage to prevent root rot.',
                'icon' => 'alert-triangle',
                'priority' => 'medium',
                'category' => 'drainage',
            ];
        }

        return [
            'action' => 'Moisture Optimal',
            'description' => 'Soil moisture levels are good. Continue current watering schedule.',
            'icon' => 'check-circle',
            'priority' => 'low',
            'category' => 'watering',
        ];
    }

    private function getWeatherBasedRecommendations($weatherCondition, $temperature, $humidity, $cropName)
    {
        $recommendations = [];

        // Temperature-based recommendations
        if ($temperature > 35) {
            $recommendations[] = [
                'action' => 'Heat Protection',
                'description' => 'High temperatures detected. Provide shade cloth and increase watering frequency.',
                'icon' => 'thermometer',
                'priority' => 'high',
                'category' => 'weather',
            ];
        } elseif ($temperature < 10) {
            $recommendations[] = [
                'action' => 'Frost Protection',
                'description' => 'Low temperatures may damage plants. Cover sensitive crops overnight.',
                'icon' => 'snowflake',
                'priority' => 'high',
                'category' => 'weather',
            ];
        }

        // Weather condition recommendations
        switch ($weatherCondition) {
            case 'rainy':
            case 'stormy':
                $recommendations[] = [
                    'action' => 'Storm Preparation',
                    'description' => 'Secure plant supports and ensure drainage. Avoid fertilizing during heavy rain.',
                    'icon' => 'cloud-rain',
                    'priority' => 'high',
                    'category' => 'weather',
                ];
                break;
            case 'sunny':
                if ($temperature > 30) {
                    $recommendations[] = [
                        'action' => 'Sun Protection',
                        'description' => 'Hot sunny weather requires more frequent watering. Check soil moisture twice daily.',
                        'icon' => 'sun',
                        'priority' => 'medium',
                        'category' => 'weather',
                    ];
                }
                break;
            case 'windy':
                $recommendations[] = [
                    'action' => 'Wind Protection',
                    'description' => 'Strong winds can damage plants and increase water evaporation. Stake tall plants.',
                    'icon' => 'wind',
                    'priority' => 'medium',
                    'category' => 'weather',
                ];
                break;
        }

        return $recommendations;
    }

    private function getGrowthStageRecommendations($daysSincePlanting, $cropName)
    {
        $recommendations = [];

        // Get growth stages based on common crop cycles
        $stages = $this->getCropGrowthStages($cropName);

        foreach ($stages as $stage) {
            if ($daysSincePlanting >= $stage['start_day'] && $daysSincePlanting <= $stage['end_day']) {
                $recommendations[] = [
                    'action' => $stage['action'],
                    'description' => $stage['description'],
                    'icon' => $stage['icon'],
                    'priority' => $stage['priority'],
                    'category' => 'growth_stage',
                ];
                break;
            }
        }

        return $recommendations;
    }

    private function getGeneralCareRecommendations($soilMoisture, $temperature, $cropName)
    {
        $recommendations = [];

        // Fertilizer recommendations
        if ($temperature > 20 && $temperature < 30 && $soilMoisture > 40) {
            $recommendations[] = [
                'action' => 'Fertilize',
                'description' => 'Good growing conditions. Consider applying balanced fertilizer for optimal growth.',
                'icon' => 'leaf',
                'priority' => 'low',
                'category' => 'nutrition',
            ];
        }

        // Pest monitoring
        if ($temperature > 25 && $soilMoisture > 60) {
            $recommendations[] = [
                'action' => 'Monitor Pests',
                'description' => 'Warm, humid conditions favor pest activity. Check plants regularly for signs of damage.',
                'icon' => 'bug',
                'priority' => 'medium',
                'category' => 'pest_control',
            ];
        }

        return $recommendations;
    }

    private function getCropGrowthStages($cropName)
    {
        $stages = [
            'tomato' => [
                ['start_day' => 0, 'end_day' => 14, 'action' => 'Germination Care', 'description' => 'Keep soil consistently moist but not waterlogged. Provide warmth (20-25°C).', 'icon' => 'sprout', 'priority' => 'high'],
                ['start_day' => 15, 'end_day' => 45, 'action' => 'Seedling Care', 'description' => 'Transplant when 2-3 true leaves appear. Provide support stakes.', 'icon' => 'tree-pine', 'priority' => 'medium'],
                ['start_day' => 46, 'end_day' => 75, 'action' => 'Flowering Stage', 'description' => 'First flowers appear. Ensure consistent watering and consider calcium supplements.', 'icon' => 'flower', 'priority' => 'medium'],
                ['start_day' => 76, 'end_day' => 120, 'action' => 'Fruit Development', 'description' => 'Fruits are forming. Maintain consistent moisture and harvest when ripe.', 'icon' => 'apple', 'priority' => 'high'],
            ],
            'corn' => [
                ['start_day' => 0, 'end_day' => 10, 'action' => 'Germination', 'description' => 'Keep soil temperature above 15°C. Ensure adequate moisture for germination.', 'icon' => 'sprout', 'priority' => 'high'],
                ['start_day' => 11, 'end_day' => 50, 'action' => 'Vegetative Growth', 'description' => 'Plants are growing rapidly. Apply nitrogen fertilizer and ensure deep watering.', 'icon' => 'leaf', 'priority' => 'medium'],
                ['start_day' => 51, 'end_day' => 80, 'action' => 'Tasseling', 'description' => 'Critical pollination period. Maintain consistent moisture and avoid stress.', 'icon' => 'wheat', 'priority' => 'high'],
                ['start_day' => 81, 'end_day' => 120, 'action' => 'Grain Fill', 'description' => 'Kernels are developing. Continue consistent watering until maturity.', 'icon' => 'grain', 'priority' => 'medium'],
            ],
        ];

        // Default stages for unknown crops
        $defaultStages = [
            ['start_day' => 0, 'end_day' => 14, 'action' => 'Early Growth', 'description' => 'Monitor germination and early growth. Keep soil consistently moist.', 'icon' => 'sprout', 'priority' => 'medium'],
            ['start_day' => 15, 'end_day' => 60, 'action' => 'Active Growth', 'description' => 'Plants are establishing. Provide regular care and monitoring.', 'icon' => 'leaf', 'priority' => 'medium'],
            ['start_day' => 61, 'end_day' => 120, 'action' => 'Maturation', 'description' => 'Crops are maturing. Prepare for harvest and maintain plant health.', 'icon' => 'wheat', 'priority' => 'medium'],
        ];

        return $stages[$cropName] ?? $defaultStages;
    }
}
