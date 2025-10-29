<?php

namespace App\Services;

use App\Models\CommodityVariant;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PriceForecastService
{
    /**
     * Calculate price forecast for a specific target date
     */
    public function getForecastForDate(CommodityVariant $variant, Carbon $targetDate): ?array
    {
        $prices = $variant->prices()->orderBy('date', 'desc')->get();

        if ($prices->count() < 3) {
            return null;
        }

        $priceData = $prices->reverse()->values();
        $latestDate = Carbon::parse($priceData->last()->date);
        $daysAhead = $latestDate->diffInDays($targetDate);

        $regression = $this->calculateLinearRegression($priceData);
        $n = $priceData->count();

        // Apply dampening factor for distant forecasts
        $dampenedSlope = $this->applyForecastDampening($regression['slope'], $daysAhead);

        // Calculate raw forecast price
        $rawForecastPrice = $regression['intercept'] + $dampenedSlope * ($n + $daysAhead);

        // Get historical price statistics
        $priceStats = $this->calculatePriceStatistics($priceData);

        // Apply price bounds to prevent unrealistic forecasts
        $forecastPrice = $this->applyPriceBounds($rawForecastPrice, $priceStats, $priceData->last()->price);

        // Calculate confidence intervals (±1 standard deviation)
        $stdDev = $this->calculateStandardDeviation($priceData);
        $optimisticPrice = $this->applyPriceBounds($forecastPrice + $stdDev, $priceStats, $priceData->last()->price);
        $pessimisticPrice = max(0, $this->applyPriceBounds($forecastPrice - $stdDev, $priceStats, $priceData->last()->price));

        // Adjust confidence based on forecast distance
        $baseConfidence = $this->calculateConfidenceScore($priceData);
        $adjustedConfidence = $this->adjustConfidenceForDistance($baseConfidence, $daysAhead);

        return [
            'forecast_price' => round($forecastPrice, 2),
            'optimistic_price' => round($optimisticPrice, 2),
            'pessimistic_price' => round($pessimisticPrice, 2),
            'current_price' => $priceData->last()->price,
            'trend' => $this->determineTrend($dampenedSlope),
            'slope' => $dampenedSlope,
            'confidence' => $this->getConfidenceLevel($adjustedConfidence),
            'confidence_score' => $adjustedConfidence,
            'price_volatility' => round($stdDev, 2),
            'target_date' => $targetDate->format('Y-m-d'),
            'days_ahead' => $daysAhead,
            'sample_size' => $prices->count(),
        ];
    }

    /**
     * Calculate forecast for multiple time periods (7, 14, 30 days, 1-3 months)
     */
    public function getMultipleForecast(CommodityVariant $variant): ?array
    {
        $prices = $variant->prices()->orderBy('date', 'desc')->get();

        if ($prices->count() < 3) {
            return null;
        }

        $priceData = $prices->reverse()->values();
        $n = $priceData->count();
        $latestDate = Carbon::parse($priceData->last()->date);

        $regression = $this->calculateLinearRegression($priceData);

        $forecasts = [];

        // Daily forecasts
        foreach ([7, 14, 30] as $days) {
            $forecastDate = $latestDate->copy()->addDays($days);
            $forecastPrice = $regression['intercept'] + $regression['slope'] * ($n + $days);

            $forecasts[] = [
                'date' => $forecastDate->format('Y-m-d'),
                'price' => round($forecastPrice, 2),
                'days_ahead' => $days,
                'type' => 'daily',
            ];
        }

        // Monthly forecasts
        $lastDaysForecast = 30;
        foreach ([1, 2, 3] as $months) {
            $forecastDate = $latestDate->copy()->addMonths($months);
            $approximateDays = $lastDaysForecast + ($months * 30);
            $forecastPrice = $regression['intercept'] + $regression['slope'] * ($n + $approximateDays);

            $forecasts[] = [
                'date' => $forecastDate->format('Y-m-d'),
                'price' => round($forecastPrice, 2),
                'months_ahead' => $months,
                'type' => 'monthly',
            ];
        }

        $priceChangePercent = (($priceData->last()->price - $priceData->first()->price) / $priceData->first()->price) * 100;

        return [
            'trend' => $this->determineTrend($regression['slope']),
            'slope' => $regression['slope'],
            'price_change_percent' => round($priceChangePercent, 2),
            'forecasts' => $forecasts,
            'confidence' => $this->calculateConfidence($priceData),
        ];
    }

    /**
     * Calculate linear regression parameters (slope and intercept)
     */
    private function calculateLinearRegression(Collection $priceData): array
    {
        $n = $priceData->count();
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($priceData as $index => $price) {
            $x = $index + 1;
            $y = $price->price;

            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
        ];
    }

    /**
     * Determine price trend direction based on slope
     */
    private function determineTrend(float $slope): string
    {
        if ($slope > 0.1) {
            return 'increasing';
        } elseif ($slope < -0.1) {
            return 'decreasing';
        }

        return 'stable';
    }

    /**
     * Calculate confidence level based on price volatility
     */
    private function calculateConfidence(Collection $priceData): string
    {
        $prices = $priceData->pluck('price')->toArray();
        $mean = array_sum($prices) / count($prices);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $prices)) / count($prices);
        $stdDev = sqrt($variance);

        $coefficientOfVariation = $stdDev / $mean;

        if ($coefficientOfVariation < 0.1) {
            return 'high';
        } elseif ($coefficientOfVariation < 0.2) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Calculate confidence score as a percentage
     */
    private function calculateConfidenceScore(Collection $priceData): int
    {
        $prices = $priceData->pluck('price')->toArray();
        $mean = array_sum($prices) / count($prices);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $prices)) / count($prices);
        $stdDev = sqrt($variance);

        $coefficientOfVariation = $stdDev / $mean;

        // Convert coefficient of variation to a confidence score (0-100)
        // Lower CV = higher confidence
        $score = max(0, min(100, round((1 - $coefficientOfVariation) * 100)));

        return $score;
    }

    /**
     * Calculate standard deviation of prices
     */
    private function calculateStandardDeviation(Collection $priceData): float
    {
        $prices = $priceData->pluck('price')->toArray();
        $mean = array_sum($prices) / count($prices);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $prices)) / count($prices);

        return sqrt($variance);
    }

    /**
     * Calculate historical price statistics (min, max, average)
     */
    private function calculatePriceStatistics(Collection $priceData): array
    {
        $prices = $priceData->pluck('price')->toArray();

        return [
            'min' => min($prices),
            'max' => max($prices),
            'avg' => array_sum($prices) / count($prices),
        ];
    }

    /**
     * Apply price bounds to prevent unrealistic forecasts
     * Caps prices at ±30% from current price or historical bounds
     */
    private function applyPriceBounds(float $forecastPrice, array $priceStats, float $currentPrice): float
    {
        // Calculate reasonable bounds
        $lowerBound = max(
            $priceStats['min'] * 0.9, // 10% below historical minimum
            $currentPrice * 0.7        // 30% below current price
        );

        $upperBound = min(
            $priceStats['max'] * 1.1,  // 10% above historical maximum
            $currentPrice * 1.3        // 30% above current price
        );

        // Also consider average-based bounds
        $avgLowerBound = $priceStats['avg'] * 0.7; // 30% below average
        $avgUpperBound = $priceStats['avg'] * 1.3; // 30% above average

        // Use the most conservative bounds
        $finalLowerBound = max($lowerBound, $avgLowerBound);
        $finalUpperBound = min($upperBound, $avgUpperBound);

        // Clamp the forecast price within bounds
        return max($finalLowerBound, min($finalUpperBound, $forecastPrice));
    }

    /**
     * Apply dampening to slope for distant forecasts
     * Reduces the influence of linear trends for long-term predictions
     */
    private function applyForecastDampening(float $slope, int $daysAhead): float
    {
        // Dampening factor decreases with distance
        // 0-7 days: 100% (no dampening)
        // 8-30 days: 80% (slight dampening)
        // 31-60 days: 60% (moderate dampening)
        // 61+ days: 40% (heavy dampening)

        if ($daysAhead <= 7) {
            $dampeningFactor = 1.0;
        } elseif ($daysAhead <= 30) {
            $dampeningFactor = 0.8;
        } elseif ($daysAhead <= 60) {
            $dampeningFactor = 0.6;
        } else {
            $dampeningFactor = 0.4;
        }

        return $slope * $dampeningFactor;
    }

    /**
     * Adjust confidence score based on forecast distance
     * Confidence decreases for longer-term forecasts
     */
    private function adjustConfidenceForDistance(int $baseConfidence, int $daysAhead): int
    {
        // Reduce confidence based on how far ahead we're forecasting
        // 0-7 days: no reduction
        // 8-30 days: -10%
        // 31-60 days: -20%
        // 61-90 days: -30%
        // 91+ days: -40%

        if ($daysAhead <= 7) {
            $reductionPercent = 0;
        } elseif ($daysAhead <= 30) {
            $reductionPercent = 10;
        } elseif ($daysAhead <= 60) {
            $reductionPercent = 20;
        } elseif ($daysAhead <= 90) {
            $reductionPercent = 30;
        } else {
            $reductionPercent = 40;
        }

        $adjustedConfidence = $baseConfidence * (1 - $reductionPercent / 100);

        return max(0, min(100, (int) round($adjustedConfidence)));
    }

    /**
     * Convert confidence score to level (high/medium/low)
     */
    private function getConfidenceLevel(int $confidenceScore): string
    {
        if ($confidenceScore >= 70) {
            return 'high';
        } elseif ($confidenceScore >= 50) {
            return 'medium';
        }

        return 'low';
    }
}
