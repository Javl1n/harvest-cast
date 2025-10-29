<?php

use App\Models\Commodity;
use App\Models\Schedule;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\User;

it('displays yield forecast on sensor detail page for active planting', function () {
    $user = User::factory()->create();
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    // Create sensor reading
    SensorReading::factory()->create([
        'sensor_id' => $sensor->id,
        'moisture' => 55,
    ]);

    // Create historical schedules for training data
    for ($i = 0; $i < 5; $i++) {
        $historicalSchedule = Schedule::create([
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

    // Create active schedule (not harvested)
    $activeSchedule = Schedule::create([
        'commodity_id' => $commodity->id,
        'sensor_id' => $sensor->id,
        'hectares' => 2.5,
        'seeds_planted' => 50000,
        'date_planted' => now()->subDays(60),
        'expected_harvest_date' => now()->addDays(30),
        'expected_yield' => 8.0,
        'expected_income' => 16000,
    ]);

    $response = $this->actingAs($user)->get("/sensors/{$sensor->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('sensors/show')
        ->has('yieldForecast')
        ->where('yieldForecast.model_type', 'ml_regression')
        ->where('yieldForecast.sample_size', 5)
    );
});

it('does not display yield forecast for harvested crops', function () {
    $user = User::factory()->create();
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    SensorReading::factory()->create([
        'sensor_id' => $sensor->id,
        'moisture' => 55,
    ]);

    // Create harvested schedule
    $harvestedSchedule = Schedule::create([
        'commodity_id' => $commodity->id,
        'sensor_id' => $sensor->id,
        'hectares' => 2.5,
        'seeds_planted' => 50000,
        'date_planted' => now()->subDays(120),
        'expected_harvest_date' => now()->subDays(30),
        'actual_harvest_date' => now()->subDays(30),
        'yield' => 7.5,
        'expected_income' => 15000,
        'income' => 15000,
    ]);

    $response = $this->actingAs($user)->get("/sensors/{$sensor->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('sensors/show')
        ->where('yieldForecast', null)
    );
});

it('uses basic forecast when insufficient historical data', function () {
    $user = User::factory()->create();
    $sensor = Sensor::factory()->create();
    $commodity = Commodity::factory()->create();

    SensorReading::factory()->create([
        'sensor_id' => $sensor->id,
        'moisture' => 55,
    ]);

    // Create active schedule without historical data
    $activeSchedule = Schedule::create([
        'commodity_id' => $commodity->id,
        'sensor_id' => $sensor->id,
        'hectares' => 2.5,
        'seeds_planted' => 50000,
        'date_planted' => now()->subDays(60),
        'expected_harvest_date' => now()->addDays(30),
        'expected_yield' => 8.0,
        'expected_income' => 16000,
    ]);

    $response = $this->actingAs($user)->get("/sensors/{$sensor->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('sensors/show')
        ->has('yieldForecast')
        ->where('yieldForecast.model_type', 'basic_estimate')
        ->where('yieldForecast.confidence', 'low')
    );
});
