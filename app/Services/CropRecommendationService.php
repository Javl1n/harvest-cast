<?php

namespace App\Services;

use App\Models\Weather;
use App\Models\SensorReading;
use Carbon\Carbon;

class CropRecommendationService
{
    /**
     * Get crop recommendations for corn and rice based on soil moisture, weather, and current date
     */
    public function getRecommendations(int $soilMoisture, ?Weather $weather = null, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        $season = $this->getSeason($date);
        $month = $date->month;
        
        // Get weather data using model accessors
        $temperature = $weather?->temperature;
        $humidity = $weather?->humidity;
        $weatherCondition = $weather?->condition;
        
        $recommendations = [];
        
        // Get corn and rice data
        $crops = $this->getCropDatabase();
        
        foreach ($crops as $crop) {
            $score = $this->calculateCropScore($crop, $soilMoisture, $season, $month, $temperature, $humidity, $weatherCondition);
            
            $recommendations[] = [
                'crop' => $crop['name'],
                'variety' => $crop['variety'],
                'score' => $score,
                'suitability' => $this->getSuitabilityLevel($score),
                'reasons' => $this->getRecommendationReasons($crop, $soilMoisture, $season, $temperature),
                'planting_tips' => $crop['planting_tips'],
                'harvest_time' => $crop['harvest_time'],
                'harvest_days' => $crop['harvest_days'],
                'optimal_conditions' => $crop['optimal_conditions'],
                'water_requirements' => $crop['water_requirements']
            ];
        }
        
        // Sort by score (highest first)
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return $recommendations;
    }
    
    /**
     * Calculate a suitability score for a crop (0-100)
     */
    private function calculateCropScore(array $crop, int $soilMoisture, string $season, int $month, ?float $temperature, ?int $humidity, ?string $weatherCondition): int
    {
        $score = 0;
        
        // Soil moisture scoring (40 points max)
        $moistureScore = $this->scoreMoisture($soilMoisture, $crop['moisture_range']);
        $score += $moistureScore * 0.4;
        
        // Season scoring (30 points max)
        $seasonScore = in_array($season, $crop['seasons']) ? 30 : 0;
        if (in_array($month, $crop['planting_months'])) {
            $seasonScore += 10; // Bonus for optimal planting month
        }
        $score += $seasonScore;
        
        // Temperature scoring (20 points max)
        if ($temperature !== null) {
            $tempScore = $this->scoreTemperature($temperature, $crop['temperature_range']);
            $score += $tempScore * 0.2;
        } else {
            $score += 15; // Default score if no temperature data
        }
        
        // Weather condition bonus/penalty (10 points max)
        if ($weatherCondition !== null) {
            if (in_array($weatherCondition, $crop['favorable_weather'])) {
                $score += 10;
            } elseif (in_array($weatherCondition, $crop['unfavorable_weather'])) {
                $score -= 5;
            } else {
                $score += 5; // Neutral weather
            }
        } else {
            $score += 5; // Default if no weather data
        }
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Score moisture level (0-100)
     */
    private function scoreMoisture(int $soilMoisture, array $moistureRange): int
    {
        $min = $moistureRange['min'];
        $max = $moistureRange['max'];
        
        if ($soilMoisture >= $min && $soilMoisture <= $max) {
            return 100; // Perfect moisture
        }
        
        if ($soilMoisture < $min) {
            $deficit = $min - $soilMoisture;
            return max(0, 100 - ($deficit * 2)); // Penalty for low moisture
        }
        
        if ($soilMoisture > $max) {
            $excess = $soilMoisture - $max;
            return max(0, 100 - ($excess * 1.5)); // Penalty for excess moisture
        }
        
        return 0;
    }
    
    /**
     * Score temperature (0-100)
     */
    private function scoreTemperature(float $temperature, array $tempRange): int
    {
        $min = $tempRange['min'];
        $max = $tempRange['max'];
        
        if ($temperature >= $min && $temperature <= $max) {
            return 100;
        }
        
        if ($temperature < $min) {
            $deficit = $min - $temperature;
            return max(0, 100 - ($deficit * 3));
        }
        
        if ($temperature > $max) {
            $excess = $temperature - $max;
            return max(0, 100 - ($excess * 3));
        }
        
        return 0;
    }
    
    /**
     * Get current season based on date
     */
    private function getSeason(Carbon $date): string
    {
        $month = $date->month;
        
        return match(true) {
            in_array($month, [12, 1, 2]) => 'winter',
            in_array($month, [3, 4, 5]) => 'spring',
            in_array($month, [6, 7, 8]) => 'summer',
            in_array($month, [9, 10, 11]) => 'autumn',
            default => 'spring'
        };
    }
    
    /**
     * Get suitability level based on score
     */
    private function getSuitabilityLevel(int $score): string
    {
        return match(true) {
            $score >= 80 => 'excellent',
            $score >= 60 => 'good',
            $score >= 40 => 'fair',
            $score >= 20 => 'poor',
            default => 'unsuitable'
        };
    }
    
    /**
     * Get reasons for recommendation
     */
    private function getRecommendationReasons(array $crop, int $soilMoisture, string $season, ?float $temperature): array
    {
        $reasons = [];
        
        $moistureMin = $crop['moisture_range']['min'];
        $moistureMax = $crop['moisture_range']['max'];
        
        if ($soilMoisture >= $moistureMin && $soilMoisture <= $moistureMax) {
            $reasons[] = "Ideal soil moisture level ({$soilMoisture}%)";
        } elseif ($soilMoisture < $moistureMin) {
            $reasons[] = "Soil moisture low - requires additional irrigation";
        } else {
            $reasons[] = "High soil moisture - ensure proper drainage";
        }
        
        if (in_array($season, $crop['seasons'])) {
            $reasons[] = "Perfect season for planting ({$season})";
        } else {
            $reasons[] = "Off-season planting - consider greenhouse or climate control";
        }
        
        if ($temperature !== null) {
            $tempMin = $crop['temperature_range']['min'];
            $tempMax = $crop['temperature_range']['max'];
            
            if ($temperature >= $tempMin && $temperature <= $tempMax) {
                $reasons[] = "Optimal temperature range ({$temperature}°C)";
            } elseif ($temperature < $tempMin) {
                $reasons[] = "Temperature below optimal - may need warming";
            } else {
                $reasons[] = "Temperature above optimal - provide shade or cooling";
            }
        }
        
        return $reasons;
    }
    
    /**
     * Crop database for corn and rice only
     */
    private function getCropDatabase(): array
    {
        return [
            [
                'name' => 'Rice',
                'variety' => 'Lowland Rice',
                'moisture_range' => ['min' => 80, 'max' => 100],
                'temperature_range' => ['min' => 20, 'max' => 35],
                'seasons' => ['summer', 'spring'],
                'planting_months' => [4, 5, 6, 7],
                'favorable_weather' => ['Clear', 'Clouds', 'Rain'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Requires flooded fields. Plant seedlings in puddled soil. Maintain 2-5cm water depth.',
                'harvest_time' => '120-150 days',
                'harvest_days' => 135, // Average of 120-150
                'optimal_conditions' => 'Flooded fields with warm temperatures and high humidity',
                'water_requirements' => 'Very High - requires continuous flooding'
            ],
            [
                'name' => 'Corn',
                'variety' => 'Field Corn (Dent)',
                'moisture_range' => ['min' => 50, 'max' => 70],
                'temperature_range' => ['min' => 18, 'max' => 32],
                'seasons' => ['summer', 'spring'],
                'planting_months' => [4, 5, 6],
                'favorable_weather' => ['Clear', 'Clouds'],
                'unfavorable_weather' => ['Thunderstorm'],
                'planting_tips' => 'Plant when soil temperature reaches 10°C. Space rows 75cm apart. Plant 2-3cm deep.',
                'harvest_time' => '90-120 days',
                'harvest_days' => 105, // Average of 90-120
                'optimal_conditions' => 'Well-drained soil with warm temperatures and moderate rainfall',
                'water_requirements' => 'Moderate - requires consistent moisture during grain filling'
            ]
        ];
    }
}