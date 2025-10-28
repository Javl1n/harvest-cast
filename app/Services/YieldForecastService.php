<?php

namespace App\Services;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class YieldForecastService
{
    public function __construct(
        private YieldPredictionModel $model
    ) {}

    /**
     * Get comprehensive yield forecast for a schedule using ML prediction.
     */
    public function getForecast(Schedule $schedule): ?array
    {
        // Don't forecast for already harvested crops
        if ($schedule->actual_harvest_date) {
            return null;
        }

        // Get historical data for training
        $historicalSchedules = $this->getHistoricalSchedules($schedule);

        // Check if we have enough historical data for ML
        if ($historicalSchedules->count() >= 3) {
            return $this->getMLForecast($schedule, $historicalSchedules);
        }

        // Fallback to basic forecast if insufficient data
        return $this->getBasicForecast($schedule);
    }

    /**
     * Get ML-based forecast with comprehensive analysis.
     */
    private function getMLForecast(Schedule $schedule, Collection $historicalSchedules): array
    {
        // Train the model on historical data
        $this->model->train($historicalSchedules);

        // Extract features for current schedule
        $features = $this->model->extractFeatures($schedule);

        if ($features === null) {
            return $this->getBasicForecast($schedule);
        }

        // Make prediction (yield per hectare)
        $predictedYieldPerHectare = $this->model->predict($features);
        $predictedYield = $predictedYieldPerHectare * $schedule->hectares;

        // Calculate confidence
        $confidence = $this->model->getConfidence($historicalSchedules->count());
        $confidenceLevel = $this->getConfidenceLevel($confidence);

        // Calculate prediction intervals (±1.96 * std for 95% CI)
        $yieldStdDev = $this->calculateYieldStdDev($historicalSchedules);
        $optimisticYield = $predictedYield + (1.96 * $yieldStdDev * $schedule->hectares);
        $pessimisticYield = $predictedYield - (1.96 * $yieldStdDev * $schedule->hectares);

        // Calculate environmental factors
        $environmentalFactors = $this->analyzeEnvironmentalFactors($schedule, $features);

        // Get historical yield data for charts
        $historicalYieldData = $this->getHistoricalYieldData($historicalSchedules);

        // Calculate growth progress
        $growthProgress = $this->getGrowthProgress($schedule);

        // Calculate variance from expected
        $varianceFromExpected = 0;
        if ($schedule->expected_yield && $schedule->expected_yield > 0) {
            $varianceFromExpected = (($predictedYield - $schedule->expected_yield) / $schedule->expected_yield) * 100;
        }

        return [
            'predicted_yield' => round($predictedYield, 2),
            'expected_yield' => $schedule->expected_yield,
            'optimistic_yield' => round(max(0, $optimisticYield), 2),
            'pessimistic_yield' => round(max(0, $pessimisticYield), 2),
            'yield_per_hectare' => round($predictedYieldPerHectare, 2),
            'confidence' => $confidenceLevel,
            'confidence_score' => round($confidence * 100, 1),
            'r_squared' => round($this->model->getRSquared(), 3),
            'variance_from_expected_percent' => round($varianceFromExpected, 2),
            'environmental_factors' => $environmentalFactors,
            'historical_yields' => $historicalYieldData,
            'growth_progress_percent' => $growthProgress,
            'days_until_harvest' => $this->getDaysUntilHarvest($schedule),
            'model_type' => 'ml_regression',
            'sample_size' => $historicalSchedules->count(),
        ];
    }

    /**
     * Get basic forecast when insufficient data for ML.
     */
    private function getBasicForecast(Schedule $schedule): array
    {
        $expectedYield = $schedule->expected_yield ?? ($schedule->hectares * 3);

        return [
            'predicted_yield' => $expectedYield,
            'expected_yield' => $schedule->expected_yield ?? $expectedYield,
            'optimistic_yield' => round($expectedYield * 1.15, 2),
            'pessimistic_yield' => round($expectedYield * 0.85, 2),
            'yield_per_hectare' => round($expectedYield / max($schedule->hectares, 0.01), 2),
            'confidence' => 'low',
            'confidence_score' => 30,
            'r_squared' => 0,
            'variance_from_expected_percent' => 0,
            'environmental_factors' => [
                [
                    'factor' => 'Data Availability',
                    'impact' => 'Insufficient historical data for ML prediction',
                    'weight' => 100,
                ],
            ],
            'historical_yields' => [],
            'growth_progress_percent' => $this->getGrowthProgress($schedule),
            'days_until_harvest' => $this->getDaysUntilHarvest($schedule),
            'model_type' => 'basic_estimate',
            'sample_size' => 0,
        ];
    }

    /**
     * Get historical schedules for the same commodity.
     */
    private function getHistoricalSchedules(Schedule $schedule): Collection
    {
        return Schedule::where('commodity_id', $schedule->commodity_id)
            ->whereNotNull('actual_harvest_date')
            ->whereNotNull('yield')
            ->where('yield', '>', 0)
            ->where('id', '!=', $schedule->id)
            ->with(['sensor.readings', 'commodity'])
            ->orderBy('actual_harvest_date', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * Analyze environmental factors affecting the forecast.
     */
    private function analyzeEnvironmentalFactors(Schedule $schedule, array $features): array
    {
        $factors = [];

        // Soil moisture analysis
        $avgMoisture = $features[0]; // avg_moisture
        if ($avgMoisture < 30) {
            $factors[] = [
                'factor' => 'Soil Moisture',
                'impact' => 'Low moisture detected - may reduce yield by 15-20%',
                'weight' => 35,
                'status' => 'warning',
            ];
        } elseif ($avgMoisture > 80) {
            $factors[] = [
                'factor' => 'Soil Moisture',
                'impact' => 'High moisture - potential waterlogging risk',
                'weight' => 25,
                'status' => 'warning',
            ];
        } else {
            $factors[] = [
                'factor' => 'Soil Moisture',
                'impact' => 'Optimal levels maintained (40-70%)',
                'weight' => 40,
                'status' => 'good',
            ];
        }

        // Planting density analysis
        $seedsPerHectare = $features[2];
        $factors[] = [
            'factor' => 'Planting Density',
            'impact' => number_format($seedsPerHectare, 0).' seeds/ha',
            'weight' => 25,
            'status' => 'info',
        ];

        // Growing days analysis
        $daysToHarvest = $features[1];
        if ($daysToHarvest < 60) {
            $factors[] = [
                'factor' => 'Growth Period',
                'impact' => 'Short growth period - early harvest variety',
                'weight' => 20,
                'status' => 'info',
            ];
        } elseif ($daysToHarvest > 150) {
            $factors[] = [
                'factor' => 'Growth Period',
                'impact' => 'Extended growth period - late harvest variety',
                'weight' => 20,
                'status' => 'info',
            ];
        } else {
            $factors[] = [
                'factor' => 'Growth Period',
                'impact' => 'Standard growth period ('.$daysToHarvest.' days)',
                'weight' => 15,
                'status' => 'good',
            ];
        }

        // Historical performance
        $factors[] = [
            'factor' => 'Historical Data',
            'impact' => 'ML model trained on past harvests',
            'weight' => 100 - array_sum(array_column($factors, 'weight')),
            'status' => 'good',
        ];

        return $factors;
    }

    /**
     * Get historical yield data for charts.
     */
    private function getHistoricalYieldData(Collection $historicalSchedules): array
    {
        return $historicalSchedules->map(function ($schedule) {
            return [
                'date' => $schedule->actual_harvest_date->format('Y-m-d'),
                'yield' => round($schedule->yield, 2),
                'yield_per_hectare' => round($schedule->yield / max($schedule->hectares, 0.01), 2),
                'hectares' => $schedule->hectares,
            ];
        })->reverse()->values()->toArray();
    }

    /**
     * Calculate standard deviation of historical yields.
     */
    private function calculateYieldStdDev(Collection $historicalSchedules): float
    {
        $yieldsPerHectare = $historicalSchedules->map(function ($schedule) {
            return $schedule->yield / max($schedule->hectares, 0.01);
        })->toArray();

        $mean = array_sum($yieldsPerHectare) / count($yieldsPerHectare);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $yieldsPerHectare)) / count($yieldsPerHectare);

        return sqrt($variance);
    }

    /**
     * Get confidence level label.
     */
    private function getConfidenceLevel(float $confidence): string
    {
        if ($confidence >= 0.7) {
            return 'high';
        } elseif ($confidence >= 0.4) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get growth progress as percentage.
     */
    private function getGrowthProgress(Schedule $schedule): float
    {
        if (! $schedule->date_planted || ! $schedule->expected_harvest_date) {
            return 0;
        }

        $plantDate = Carbon::parse($schedule->date_planted);
        $harvestDate = Carbon::parse($schedule->expected_harvest_date);
        $now = Carbon::now();

        $totalDays = $plantDate->diffInDays($harvestDate);
        $daysPassed = $plantDate->diffInDays($now);

        if ($totalDays <= 0) {
            return 0;
        }

        return round(($daysPassed / $totalDays) * 100, 1);
    }

    /**
     * Get days until expected harvest.
     */
    private function getDaysUntilHarvest(Schedule $schedule): int
    {
        if (! $schedule->expected_harvest_date) {
            return 0;
        }

        $now = Carbon::now();
        $harvestDate = Carbon::parse($schedule->expected_harvest_date);

        return max(0, (int) $now->diffInDays($harvestDate, false));
    }
}
