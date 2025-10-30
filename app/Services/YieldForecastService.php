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

        // Check if we have enough historical data for ML (need at least 5 for reliable training)
        if ($historicalSchedules->count() >= 5) {
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

        // Make prediction (yield per acre)
        $predictedYieldPerAcre = $this->model->predict($features);

        // If model didn't train properly (returns 0 or coefficients are empty), fall back to basic forecast
        if ($predictedYieldPerAcre <= 0 || $this->model->getRSquared() == 0) {
            return $this->getBasicForecast($schedule);
        }

        $predictedYield = $predictedYieldPerAcre * $schedule->acres;

        // Apply yield bounds if user has provided expected_yield to prevent unrealistic predictions
        if ($schedule->expected_yield && $schedule->expected_yield > 0) {
            $predictedYield = $this->applyYieldBounds($predictedYield, $schedule->expected_yield, $historicalSchedules);
        }

        // Calculate confidence
        $confidence = $this->model->getConfidence($historicalSchedules->count());
        $confidenceLevel = $this->getConfidenceLevel($confidence);

        // Calculate prediction intervals (±1.96 * std for 95% CI)
        // yieldStdDev is per acre, so multiply once to get total yield std dev
        $yieldStdDev = $this->calculateYieldStdDev($historicalSchedules);
        $totalYieldStdDev = $yieldStdDev * $schedule->acres;
        $optimisticYield = $predictedYield + 1.96 * $totalYieldStdDev;
        $pessimisticYield = $predictedYield - 1.96 * $totalYieldStdDev;

        // Also apply bounds to optimistic/pessimistic if needed
        if ($schedule->expected_yield && $schedule->expected_yield > 0) {
            $optimisticYield = min($optimisticYield, $schedule->expected_yield * 1.5);
            $pessimisticYield = max($pessimisticYield, $schedule->expected_yield * 0.5);
        }

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
            'yield_per_acre' => round($predictedYieldPerAcre, 2),
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
        $expectedYield = $schedule->expected_yield ?? ($schedule->acres * 1.21); // ~3000 kg per hectare = ~1214 kg per acre

        return [
            'predicted_yield' => $expectedYield,
            'expected_yield' => $schedule->expected_yield ?? $expectedYield,
            'optimistic_yield' => round($expectedYield * 1.15, 2),
            'pessimistic_yield' => round($expectedYield * 0.85, 2),
            'yield_per_acre' => round($expectedYield / max($schedule->acres, 0.01), 2),
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
        $kgPerAcre = $features[2];
        $factors[] = [
            'factor' => 'Planting Density',
            'impact' => number_format($kgPerAcre, 2).' kg/acre',
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
                'yield_per_acre' => round($schedule->yield / max($schedule->acres, 0.01), 2),
                'acres' => $schedule->acres,
            ];
        })->reverse()->values()->toArray();
    }

    /**
     * Calculate standard deviation of historical yields.
     */
    private function calculateYieldStdDev(Collection $historicalSchedules): float
    {
        $yieldsPerAcre = $historicalSchedules->map(function ($schedule) {
            return $schedule->yield / max($schedule->acres, 0.01);
        })->toArray();

        $mean = array_sum($yieldsPerAcre) / count($yieldsPerAcre);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $yieldsPerAcre)) / count($yieldsPerAcre);

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

    /**
     * Apply yield bounds to prevent unrealistic predictions
     * Caps ML predictions to be within reasonable range of farmer's expectation
     */
    private function applyYieldBounds(float $predictedYield, float $expectedYield, Collection $historicalSchedules): float
    {
        // Calculate historical average for context
        $historicalAvg = $historicalSchedules->avg('yield');

        // If ML prediction is more than 50% higher than expected, cap it
        // This respects farmer knowledge while still showing ML can be optimistic
        $maxAllowedYield = $expectedYield * 1.5; // 50% above expected
        $minAllowedYield = $expectedYield * 0.5; // 50% below expected

        // If we have good historical data, also consider that
        if ($historicalAvg > 0) {
            // Don't allow predictions to exceed historical average by more than 30%
            $maxAllowedYield = min($maxAllowedYield, $historicalAvg * 1.3);
            // Don't allow predictions below 70% of historical average
            $minAllowedYield = max($minAllowedYield, $historicalAvg * 0.7);
        }

        // Clamp the prediction within bounds
        return max($minAllowedYield, min($maxAllowedYield, $predictedYield));
    }
}
