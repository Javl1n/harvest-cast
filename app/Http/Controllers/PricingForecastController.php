<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\Price;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class PricingForecastController extends Controller
{
    public function index()
    {
        return Inertia::render('pricing-forecast/index', [
            'forecastData' => Inertia::defer(fn () => Cache::remember(
                'pricing-forecast-index',
                now()->addMinutes(10),
                fn () => $this->getForecastData(10)
            )),
        ]);
    }

    public function show(Commodity $commodity)
    {
        $period = request()->query('period', '90days');

        return Inertia::render('pricing-forecast/show', [
            'forecastData' => Inertia::defer(fn () => Cache::remember(
                "pricing-forecast-show-{$commodity->id}-{$period}",
                now()->addMinutes(10),
                fn () => $this->getCommodityForecastData($commodity, $period)
            )),
            'selectedPeriod' => $period,
        ]);
    }

    private function getForecastData(?int $limit = null): array
    {
        // Get commodities with their variants and latest prices (optimized eager loading)
        $commodities = Commodity::with([
            'variants.prices' => function ($query) use ($limit) {
                $query->orderBy('date', 'desc');
                if ($limit) {
                    $query->limit($limit);
                }
            },
        ])->get();

        // Calculate forecasts for each variant
        $forecastData = [];

        foreach ($commodities as $commodity) {
            $commodityData = [
                'commodity' => $commodity,
                'variants' => [],
            ];

            foreach ($commodity->variants as $variant) {
                // Use already loaded prices from eager loading
                $prices = $variant->prices;

                if ($prices->count() >= 3) {
                    $forecast = $this->calculateForecast($prices);
                    $commodityData['variants'][] = [
                        'variant' => $variant,
                        'current_price' => $prices->first(),
                        'price_history' => $prices,
                        'forecast' => $forecast,
                    ];
                } else {
                    $commodityData['variants'][] = [
                        'variant' => $variant,
                        'current_price' => $prices->first(),
                        'price_history' => $prices,
                        'forecast' => null,
                    ];
                }
            }

            if (! empty($commodityData['variants'])) {
                $forecastData[] = $commodityData;
            }
        }

        return $forecastData;
    }

    private function getCommodityForecastData(Commodity $commodity, string $period = '90days'): array
    {
        // Determine date range based on period
        $dateFilter = match ($period) {
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '6months' => now()->subMonths(6),
            'year' => now()->startOfYear(),
            'all' => null,
            default => now()->subDays(90),
        };

        // Get commodity with its variants and prices based on selected period
        $commodity->load([
            'variants.prices' => function ($query) use ($dateFilter) {
                $query->orderBy('date', 'desc');
                if ($dateFilter) {
                    $query->where('date', '>=', $dateFilter);
                }
            },
        ]);

        $commodityData = [
            'commodity' => $commodity,
            'variants' => [],
        ];

        foreach ($commodity->variants as $variant) {
            // Use already loaded prices from eager loading
            $prices = $variant->prices;

            if ($prices->count() >= 3) {
                $forecast = $this->calculateForecast($prices);
                $commodityData['variants'][] = [
                    'variant' => $variant,
                    'current_price' => $prices->first(),
                    'price_history' => $prices,
                    'forecast' => $forecast,
                ];
            } else {
                $commodityData['variants'][] = [
                    'variant' => $variant,
                    'current_price' => $prices->first(),
                    'price_history' => $prices,
                    'forecast' => null,
                ];
            }
        }

        return $commodityData;
    }

    private function calculateForecast($prices)
    {
        if ($prices->count() < 3) {
            return null;
        }

        // Simple linear regression for trend analysis
        $priceData = $prices->reverse()->values();
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

        // Forecast for next 7, 14, 30 days and 1, 2, 3 months
        $forecasts = [];
        $latestDate = Carbon::parse($priceData->last()->date);

        // Daily forecasts
        for ($days = 7; $days <= 30; $days += 7) {
            $forecastDate = $latestDate->copy()->addDays($days);
            $forecastPrice = $intercept + $slope * ($n + $days);

            $forecasts[] = [
                'date' => $forecastDate->format('Y-m-d'),
                'price' => round($forecastPrice, 2),
                'days_ahead' => $days,
                'type' => 'daily',
            ];
        }

        // Monthly forecasts (approximate as 30-day intervals from the last daily forecast)
        $lastDaysForecast = 30;
        for ($months = 1; $months <= 3; $months++) {
            $forecastDate = $latestDate->copy()->addMonths($months);
            // Calculate approximate position in the regression line
            $approximateDays = $lastDaysForecast + ($months * 30);
            $forecastPrice = $intercept + $slope * ($n + $approximateDays);

            $forecasts[] = [
                'date' => $forecastDate->format('Y-m-d'),
                'price' => round($forecastPrice, 2),
                'months_ahead' => $months,
                'type' => 'monthly',
            ];
        }

        // Calculate trend direction
        $trend = 'stable';
        if ($slope > 0.1) {
            $trend = 'increasing';
        } elseif ($slope < -0.1) {
            $trend = 'decreasing';
        }

        // Calculate price change percentage from first to last
        $priceChange = (($priceData->last()->price - $priceData->first()->price) / $priceData->first()->price) * 100;

        return [
            'trend' => $trend,
            'slope' => $slope,
            'price_change_percent' => round($priceChange, 2),
            'forecasts' => $forecasts,
            'confidence' => $this->calculateConfidence($priceData),
        ];
    }

    private function calculateConfidence($priceData)
    {
        // Simple confidence calculation based on price volatility
        $prices = $priceData->pluck('price')->toArray();
        $mean = array_sum($prices) / count($prices);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $prices)) / count($prices);
        $stdDev = sqrt($variance);

        // Lower standard deviation = higher confidence
        $coefficientOfVariation = $stdDev / $mean;

        if ($coefficientOfVariation < 0.1) {
            return 'high';
        } elseif ($coefficientOfVariation < 0.2) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}
