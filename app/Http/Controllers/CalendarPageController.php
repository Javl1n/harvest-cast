<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\Schedule;
use App\Models\Sensor;
use App\Models\Weather;
use App\Services\CropRecommendationService;
use App\Services\YieldForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CalendarPageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return inertia()->render('sensors/index', [
            'sensors' => Sensor::with([
                'latestReading',
                'latestSchedule.commodity',
            ])->get(),
            'commodities' => Commodity::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Sensor $sensor, CropRecommendationService $cropService)
    {
        $latestReading = $sensor->latestReading;
        $recommendations = [];
        $currentConditions = null;

        if ($latestReading) {
            // Get latest weather with caching
            $latestWeather = Cache::remember('latest_weather', 300, function () {
                return Weather::latest()->first();
            });

            // Cache recommendations for 10 minutes
            $cacheKey = "crop_rec_create_{$sensor->id}_{$latestReading->id}_".($latestWeather?->id ?? 'no_weather');

            $recommendations = Cache::remember($cacheKey, 600, function () use ($cropService, $latestReading, $latestWeather) {
                return $cropService->getRecommendations(
                    $latestReading->moisture,
                    $latestWeather
                );
            });

            $currentConditions = [
                'soil_moisture' => $latestReading->moisture,
                'temperature' => $latestWeather?->temperature,
                'weather_condition' => $latestWeather?->condition,
                'humidity' => $latestWeather?->humidity,
                'reading_date' => $latestReading->created_at,
                'weather_date' => $latestWeather?->created_at,
            ];
        }

        return inertia()->render('crops/create', [
            'sensor' => $sensor->load('latestReading', 'readings'),
            'commodities' => Commodity::with('variants')->get(),
            'cropRecommendations' => $recommendations,
            'currentConditions' => $currentConditions,
            'hasRecommendations' => ! empty($recommendations),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'commodity_id' => 'required|exists:commodities,id',
            'sensor_id' => 'required|exists:sensors,id',
            'hectares' => 'required|numeric|min:0.01',
            'seeds_planted' => 'required|integer|min:1',
            'date_planted' => 'required|date',
            'expected_harvest_date' => 'required|date|after:date_planted',
        ]);

        $schedule = Schedule::create($validated);

        return redirect()->route('sensors.show', $schedule->sensor_id)
            ->with('message', 'Crop schedule created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Sensor $sensor, YieldForecastService $yieldForecastService)
    {
        $latestReading = $sensor->latestReading;
        $latestSchedule = $sensor->latestSchedule;
        $careRecommendations = [];
        $currentConditions = null;
        $yieldForecast = null;

        if ($latestReading) {
            // Get latest weather with caching
            $latestWeather = Cache::remember('latest_weather', 300, function () {
                return Weather::latest()->first();
            });

            $currentConditions = [
                'soil_moisture' => $latestReading->moisture,
                'temperature' => $latestWeather?->temperature,
                'weather_condition' => $latestWeather?->condition,
                'humidity' => $latestWeather?->humidity,
                'reading_date' => $latestReading->created_at,
                'weather_date' => $latestWeather?->created_at,
            ];

            // Get care recommendations if there's an active planting
            if ($latestSchedule && ! $latestSchedule->actual_harvest_date) {
                $daysSincePlanting = $latestSchedule->date_planted
                    ? now()->diffInDays($latestSchedule->date_planted)
                    : null;

                $careService = app(\App\Services\CropCareRecommendationService::class);
                $careRecommendations = $careService->getCareRecommendations(
                    $latestReading->moisture,
                    $latestWeather,
                    $latestSchedule->commodity,
                    $daysSincePlanting
                );
            }
        }

        // Get yield forecast for active planting
        if ($latestSchedule && ! $latestSchedule->actual_harvest_date) {
            $yieldForecast = $yieldForecastService->getForecast($latestSchedule);
        }

        return inertia()->render('sensors/show', [
            'sensor' => $sensor->load('readings', 'latestReading', 'schedules.commodity', 'latestSchedule.commodity'),
            'careRecommendations' => $careRecommendations,
            'currentConditions' => $currentConditions,
            'hasCareRecommendations' => ! empty($careRecommendations),
            'yieldForecast' => $yieldForecast,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Get crop recommendations for a sensor (API endpoint for axios)
     */
    public function recommendations(Sensor $sensor, CropRecommendationService $cropService)
    {
        $latestReading = $sensor->latestReading;

        if (! $latestReading) {
            return response()->json([
                'error' => 'No sensor readings available for this sensor.',
                'recommendations' => [],
                'currentConditions' => null,
            ], 400);
        }

        $latestWeather = Cache::remember('latest_weather', 300, function () {
            return Weather::latest()->first();
        });

        // Use shorter cache for API requests (5 minutes)
        $cacheKey = "api_crop_rec_{$sensor->id}_{$latestReading->id}_".($latestWeather?->id ?? 'no_weather');

        $recommendations = Cache::remember($cacheKey, 300, function () use ($cropService, $latestReading, $latestWeather) {
            return $cropService->getRecommendations(
                $latestReading->moisture,
                $latestWeather
            );
        });

        $currentConditions = [
            'soil_moisture' => $latestReading->moisture,
            'temperature' => $latestWeather?->temperature,
            'weather_condition' => $latestWeather?->condition,
            'humidity' => $latestWeather?->humidity,
            'reading_date' => $latestReading->created_at,
            'weather_date' => $latestWeather?->created_at,
        ];

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
            'currentConditions' => $currentConditions,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Mark a schedule as harvested
     */
    public function harvest(Schedule $schedule)
    {
        $schedule->update([
            'actual_harvest_date' => now(),
        ]);

        return back()->with('success', 'Crop marked as harvested successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
