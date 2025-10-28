<?php

use App\Models\Commodity;
use App\Models\Schedule;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Services\YieldPredictionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('trains model with historical schedules', function () {
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    // Create sensor readings
    SensorReading::factory()->create([
        'sensor_id' => $sensor->id,
        'moisture' => 55,
    ]);

    // Create historical schedules
    $schedules = collect();
    for ($i = 0; $i < 5; $i++) {
        $schedule = Schedule::create([
            'commodity_id' => $commodity->id,
            'sensor_id' => $sensor->id,
            'hectares' => 2.5,
            'seeds_planted' => 50000,
            'date_planted' => now()->subMonths(12 + $i),
            'expected_harvest_date' => now()->subMonths(9 + $i),
            'actual_harvest_date' => now()->subMonths(9 + $i),
            'yield' => 7.5 + ($i * 0.2),
            'expected_income' => 15000,
            'income' => 15000,
        ]);
        $schedules->push($schedule->load('sensor.readings'));
    }

    $model = new YieldPredictionModel;
    $model->train($schedules);

    expect($model->getRSquared())->toBeGreaterThanOrEqual(0)
        ->and($model->getRSquared())->toBeLessThanOrEqual(1);
});

it('makes predictions after training', function () {
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    SensorReading::factory()->create([
        'sensor_id' => $sensor->id,
        'moisture' => 55,
    ]);

    // Create historical schedules with known pattern
    $schedules = collect();
    for ($i = 0; $i < 5; $i++) {
        $schedule = Schedule::create([
            'commodity_id' => $commodity->id,
            'sensor_id' => $sensor->id,
            'hectares' => 2.5,
            'seeds_planted' => 50000,
            'date_planted' => now()->subMonths(12 + $i),
            'expected_harvest_date' => now()->subMonths(9 + $i),
            'actual_harvest_date' => now()->subMonths(9 + $i),
            'yield' => 7.5,
            'expected_income' => 15000,
            'income' => 15000,
        ]);
        $schedules->push($schedule->load('sensor.readings'));
    }

    $model = new YieldPredictionModel;
    $model->train($schedules);

    // Create test schedule for prediction
    $testSchedule = Schedule::create([
        'commodity_id' => $commodity->id,
        'sensor_id' => $sensor->id,
        'hectares' => 2.5,
        'seeds_planted' => 50000,
        'date_planted' => now()->subDays(60),
        'expected_harvest_date' => now()->addDays(30),
        'expected_yield' => 8.0,
        'expected_income' => 16000,
    ]);
    $testSchedule->load('sensor.readings');

    $features = $model->extractFeatures($testSchedule);
    $prediction = $model->predict($features);

    expect($prediction)->toBeGreaterThan(0)
        ->and($features)->toBeArray()
        ->and($features)->toHaveCount(4); // 4 features: moisture, days, seeds/ha, hectares
});

it('calculates confidence based on R-squared and sample size', function () {
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    SensorReading::factory()->create([
        'sensor_id' => $sensor->id,
        'moisture' => 55,
    ]);

    // Create historical schedules
    $schedules = collect();
    for ($i = 0; $i < 10; $i++) {
        $schedule = Schedule::create([
            'commodity_id' => $commodity->id,
            'sensor_id' => $sensor->id,
            'hectares' => 2.5,
            'seeds_planted' => 50000,
            'date_planted' => now()->subMonths(12 + $i),
            'expected_harvest_date' => now()->subMonths(9 + $i),
            'actual_harvest_date' => now()->subMonths(9 + $i),
            'yield' => 7.5 + ($i * 0.1),
            'expected_income' => 15000,
            'income' => 15000,
        ]);
        $schedules->push($schedule->load('sensor.readings'));
    }

    $model = new YieldPredictionModel;
    $model->train($schedules);

    $confidence = $model->getConfidence(10);

    expect($confidence)->toBeGreaterThanOrEqual(0)
        ->and($confidence)->toBeLessThanOrEqual(1);
});

it('returns zero prediction when not trained', function () {
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    $schedule = Schedule::create([
        'commodity_id' => $commodity->id,
        'sensor_id' => $sensor->id,
        'hectares' => 2.5,
        'seeds_planted' => 50000,
        'date_planted' => now()->subDays(60),
        'expected_harvest_date' => now()->addDays(30),
        'expected_yield' => 8.0,
        'expected_income' => 16000,
    ]);

    $model = new YieldPredictionModel;
    $features = [55, 90, 20000, 2.5]; // mock features

    $prediction = $model->predict($features);

    expect($prediction)->toBe(0.0);
});

it('does not train with insufficient data', function () {
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    // Create only 2 schedules (less than minimum 3)
    $schedules = collect();
    for ($i = 0; $i < 2; $i++) {
        $schedule = Schedule::create([
            'commodity_id' => $commodity->id,
            'sensor_id' => $sensor->id,
            'hectares' => 2.5,
            'seeds_planted' => 50000,
            'date_planted' => now()->subMonths(12 + $i),
            'expected_harvest_date' => now()->subMonths(9 + $i),
            'actual_harvest_date' => now()->subMonths(9 + $i),
            'yield' => 7.5,
            'expected_income' => 15000,
            'income' => 15000,
        ]);
        $schedules->push($schedule);
    }

    $model = new YieldPredictionModel;
    $model->train($schedules);

    expect($model->getRSquared())->toBe(0.0);
});
