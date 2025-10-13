<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\CommodityVariant;
use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class PricingForecastController extends Controller
{
    public function index()
    {
        // Get commodities with their variants and latest prices
        $commodities = Commodity::with([
            'variants' => function ($query) {
                $query->with(['prices' => function ($priceQuery) {
                    $priceQuery->orderBy('date', 'desc');
                }]);
            }
        ])->get();

        // Calculate forecasts for each variant
        $forecastData = [];
        
        foreach ($commodities as $commodity) {
            $commodityData = [
                'commodity' => $commodity,
                'variants' => []
            ];
            
            foreach ($commodity->variants as $variant) {
                $prices = $variant->prices()->orderBy('date', 'desc')->limit(30)->get();
                
                if ($prices->count() >= 3) {
                    $forecast = $this->calculateForecast($prices);
                    $commodityData['variants'][] = [
                        'variant' => $variant,
                        'current_price' => $prices->first(),
                        'price_history' => $prices,
                        'forecast' => $forecast
                    ];
                } else {
                    $commodityData['variants'][] = [
                        'variant' => $variant,
                        'current_price' => $prices->first(),
                        'price_history' => $prices,
                        'forecast' => null
                    ];
                }
            }
            
            $forecastData[] = $commodityData;
        }

        return Inertia::render('pricing-forecast/index', [
            'forecastData' => $forecastData
        ]);
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
        
        // Forecast for next 7, 14, 30 days
        $forecasts = [];
        $latestDate = Carbon::parse($priceData->last()->date);
        
        for ($days = 7; $days <= 30; $days += 7) {
            $forecastDate = $latestDate->copy()->addDays($days);
            $forecastPrice = $intercept + $slope * ($n + $days);
            
            $forecasts[] = [
                'date' => $forecastDate->format('Y-m-d'),
                'price' => round($forecastPrice, 2),
                'days_ahead' => $days
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
            'confidence' => $this->calculateConfidence($priceData)
        ];
    }
    
    private function calculateConfidence($priceData)
    {
        // Simple confidence calculation based on price volatility
        $prices = $priceData->pluck('price')->toArray();
        $mean = array_sum($prices) / count($prices);
        $variance = array_sum(array_map(function($x) use ($mean) { return pow($x - $mean, 2); }, $prices)) / count($prices);
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