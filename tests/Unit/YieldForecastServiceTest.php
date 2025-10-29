<?php

use App\Models\Commodity;
use App\Models\Schedule;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Services\YieldForecastService;
use App\Services\YieldPredictionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns null for harvested schedules', function () {
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    $schedule = Schedule::create([
        'commodity_id' => $commodity->id,
        'sensor_id' => $sensor->id,
        'hectares' => 2.5,
        'seed_weight_kg' => 2.5,
        'date_planted' => now()->subDays(120),
        'expected_harvest_date' => now()->subDays(30),
        'actual_harvest_date' => now()->subDays(30),
        'yield' => 7.5,
        'expected_income' => 15000,
        'income' => 15000,
    ]);

    $service = new YieldForecastService(new YieldPredictionModel);
    $forecast = $service->getForecast($schedule);

    expect($forecast)->toBeNull();
});

it('returns basic forecast when insufficient historical data', function () {
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    SensorReading::factory()->create([
        'sensor_id' => $sensor->id,
        'moisture' => 55,
    ]);

    $schedule = Schedule::create([
        'commodity_id' => $commodity->id,
        'sensor_id' => $sensor->id,
        'hectares' => 2.5,
        'seed_weight_kg' => 2.5,
        'date_planted' => now()->subDays(60),
        'expected_harvest_date' => now()->addDays(30),
        'expected_yield' => 8.0,
        'expected_income' => 16000,
    ]);

    $service = new YieldForecastService(new YieldPredictionModel);
    $forecast = $service->getForecast($schedule);

    expect($forecast)->toBeArray()
        ->and($forecast['model_type'])->toBe('basic_estimate')
        ->and($forecast['confidence'])->toBe('low')
        ->and($forecast['predicted_yield'])->toBeGreaterThan(0);
});

it('returns ML forecast with sufficient historical data', function () {
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    // Create sensor readings
    SensorReading::factory()->create([
        'sensor_id' => $sensor->id,
        'moisture' => 55,
    ]);

    // Create historical schedules
    for ($i = 0; $i < 5; $i++) {
        Schedule::create([
            'commodity_id' => $commodity->id,
            'sensor_id' => $sensor->id,
            'hectares' => 2.5,
            'seed_weight_kg' => 2.5,
            'date_planted' => now()->subMonths(12 + $i),
            'expected_harvest_date' => now()->subMonths(9 + $i),
            'actual_harvest_date' => now()->subMonths(9 + $i),
            'yield' => 7.5 + ($i * 0.2),
            'expected_income' => 15000,
            'income' => 15000,
        ]);
    }

    // Create active schedule
    $schedule = Schedule::create([
        'commodity_id' => $commodity->id,
        'sensor_id' => $sensor->id,
        'hectares' => 2.5,
        'seed_weight_kg' => 2.5,
        'date_planted' => now()->subDays(60),
        'expected_harvest_date' => now()->addDays(30),
        'expected_yield' => 8.0,
        'expected_income' => 16000,
    ]);

    $service = new YieldForecastService(new YieldPredictionModel);
    $forecast = $service->getForecast($schedule);

    expect($forecast)->toBeArray()
        ->and($forecast['model_type'])->toBe('ml_regression')
        ->and($forecast['sample_size'])->toBe(5)
        ->and($forecast['predicted_yield'])->toBeGreaterThan(0)
        ->and($forecast['historical_yields'])->toHaveCount(5)
        ->and($forecast['environmental_factors'])->toBeArray()
        ->and($forecast)->toHaveKeys([
            'predicted_yield',
            'expected_yield',
            'optimistic_yield',
            'pessimistic_yield',
            'yield_per_hectare',
            'confidence',
            'confidence_score',
            'r_squared',
            'variance_from_expected_percent',
            'environmental_factors',
            'historical_yields',
            'growth_progress_percent',
            'days_until_harvest',
            'model_type',
            'sample_size',
        ]);
});

it('calculates growth progress correctly', function () {
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    SensorReading::factory()->create([
        'sensor_id' => $sensor->id,
        'moisture' => 55,
    ]);

    $schedule = Schedule::create([
        'commodity_id' => $commodity->id,
        'sensor_id' => $sensor->id,
        'hectares' => 2.5,
        'seed_weight_kg' => 2.5,
        'date_planted' => now()->subDays(50), // 50 days ago
        'expected_harvest_date' => now()->addDays(50), // 50 days from now (total 100 days)
        'expected_yield' => 8.0,
        'expected_income' => 16000,
    ]);

    $service = new YieldForecastService(new YieldPredictionModel);
    $forecast = $service->getForecast($schedule);

    expect($forecast['growth_progress_percent'])->toBe(50.0)
        ->and($forecast['days_until_harvest'])->toBe(50);
});
