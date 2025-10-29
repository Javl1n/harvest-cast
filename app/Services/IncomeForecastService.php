<?php

namespace App\Services;

use App\Models\Schedule;
use Carbon\Carbon;

class IncomeForecastService
{
    public function __construct(
        private YieldForecastService $yieldService,
        private PriceForecastService $priceService
    ) {}

    /**
     * Get comprehensive income forecast for a schedule
     */
    public function getForecast(Schedule $schedule): ?array
    {
        // Only forecast for active plantings
        if ($schedule->actual_harvest_date !== null) {
            return null;
        }

        // Get yield forecast
        $yieldForecast = $this->yieldService->getForecast($schedule);

        if (! $yieldForecast) {
            return null;
        }

        // Get the commodity and its first variant for price forecasting
        // Note: Since schedules don't have variant_id, we use the first variant of the commodity
        $commodity = $schedule->commodity;
        $variant = $commodity->variants()->first();

        if (! $variant) {
            return null;
        }

        // Get price forecast for the expected harvest date
        $harvestDate = $schedule->expected_harvest_date
            ? Carbon::parse($schedule->expected_harvest_date)
            : Carbon::now()->addDays($yieldForecast['days_until_harvest']);

        $priceForecast = $this->priceService->getForecastForDate($variant, $harvestDate);

        if (! $priceForecast) {
            return null;
        }

        // Calculate income scenarios
        $predictedIncome = $yieldForecast['predicted_yield'] * $priceForecast['forecast_price'];
        $optimisticIncome = $yieldForecast['optimistic_yield'] * $priceForecast['optimistic_price'];
        $pessimisticIncome = $yieldForecast['pessimistic_yield'] * $priceForecast['pessimistic_price'];

        // Calculate income per hectare
        $incomePerHectare = $schedule->hectares > 0
            ? $predictedIncome / $schedule->hectares
            : $predictedIncome;

        // Combined confidence (average of yield and price confidence scores)
        $combinedConfidenceScore = ($yieldForecast['confidence_score'] + $priceForecast['confidence_score']) / 2;
        $combinedConfidence = $this->determineConfidenceLevel($combinedConfidenceScore);

        // Calculate variance from expected income
        $varianceFromExpected = null;
        $varianceFromExpectedPercent = null;
        if ($schedule->expected_income && $schedule->expected_income > 0) {
            $varianceFromExpected = $predictedIncome - $schedule->expected_income;
            $varianceFromExpectedPercent = ($varianceFromExpected / $schedule->expected_income) * 100;
        }

        // Get historical income data from past harvests of the same commodity
        $historicalIncome = $this->getHistoricalIncome($schedule);

        return [
            'predicted_income' => round($predictedIncome, 2),
            'optimistic_income' => round($optimisticIncome, 2),
            'pessimistic_income' => round($pessimisticIncome, 2),
            'expected_income' => $schedule->expected_income,
            'income_per_hectare' => round($incomePerHectare, 2),
            'confidence' => $combinedConfidence,
            'confidence_score' => round($combinedConfidenceScore),
            'variance_from_expected' => $varianceFromExpected ? round($varianceFromExpected, 2) : null,
            'variance_from_expected_percent' => $varianceFromExpectedPercent ? round($varianceFromExpectedPercent, 2) : null,
            'harvest_date' => $harvestDate->format('Y-m-d'),
            'days_until_harvest' => $yieldForecast['days_until_harvest'],

            // Breakdown components
            'yield_component' => [
                'predicted_yield' => $yieldForecast['predicted_yield'],
                'optimistic_yield' => $yieldForecast['optimistic_yield'],
                'pessimistic_yield' => $yieldForecast['pessimistic_yield'],
                'confidence' => $yieldForecast['confidence'],
                'confidence_score' => $yieldForecast['confidence_score'],
                'model_type' => $yieldForecast['model_type'],
            ],

            'price_component' => [
                'forecast_price' => $priceForecast['forecast_price'],
                'optimistic_price' => $priceForecast['optimistic_price'],
                'pessimistic_price' => $priceForecast['pessimistic_price'],
                'current_price' => $priceForecast['current_price'],
                'trend' => $priceForecast['trend'],
                'confidence' => $priceForecast['confidence'],
                'confidence_score' => $priceForecast['confidence_score'],
                'price_volatility' => $priceForecast['price_volatility'],
            ],

            // Contributing factors
            'yield_factors' => $yieldForecast['environmental_factors'] ?? [],
            'historical_income' => $historicalIncome,

            // Model metadata
            'calculation_breakdown' => [
                'formula' => 'Predicted Yield × Forecast Price = Predicted Income',
                'yield_kg' => $yieldForecast['predicted_yield'],
                'price_per_kg' => $priceForecast['forecast_price'],
                'result' => round($predictedIncome, 2),
            ],
        ];
    }

    /**
     * Get historical income data from past harvests
     */
    private function getHistoricalIncome(Schedule $schedule): array
    {
        $historicalSchedules = Schedule::where('commodity_id', $schedule->commodity_id)
            ->whereNotNull('actual_harvest_date')
            ->whereNotNull('income')
            ->whereNotNull('yield')
            ->orderBy('actual_harvest_date', 'desc')
            ->limit(10)
            ->get();

        return $historicalSchedules->map(function ($histSchedule) {
            $incomePerHectare = $histSchedule->hectares > 0
                ? $histSchedule->income / $histSchedule->hectares
                : $histSchedule->income;

            return [
                'harvest_date' => $histSchedule->actual_harvest_date->format('Y-m-d'),
                'income' => $histSchedule->income,
                'income_per_hectare' => round($incomePerHectare, 2),
                'yield' => $histSchedule->yield,
                'hectares' => $histSchedule->hectares,
            ];
        })->toArray();
    }

    /**
     * Determine confidence level from confidence score
     */
    private function determineConfidenceLevel(float $score): string
    {
        if ($score >= 70) {
            return 'high';
        } elseif ($score >= 50) {
            return 'medium';
        }

        return 'low';
    }
}
