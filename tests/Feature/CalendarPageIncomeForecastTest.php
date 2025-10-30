<?php

use App\Models\Commodity;
use App\Models\CommodityVariant;
use App\Models\Price;
use App\Models\Schedule;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->sensor = Sensor::factory()->create();
    $this->reading = SensorReading::factory()->create([
        'sensor_id' => $this->sensor->id,
        'moisture' => 60,
    ]);

    $this->commodity = Commodity::factory()->create(['name' => 'Rice']);
    $this->variant = CommodityVariant::factory()->create([
        'commodity_id' => $this->commodity->id,
        'name' => 'Jasmine Rice',
    ]);

    // Create price history for the variant
    foreach (range(1, 10) as $daysAgo) {
        Price::factory()->create([
            'variant_id' => $this->variant->id,
            'price' => 50 + ($daysAgo * 2),
            'date' => Carbon::now()->subDays(11 - $daysAgo),
        ]);
    }

    // Create historical schedules for yield prediction
    Schedule::factory()->count(6)->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'actual_harvest_date' => Carbon::now()->subMonths(rand(1, 12)),
        'yield' => 5000 + rand(-500, 500),
        'income' => 250000 + rand(-50000, 50000),
    ]);
});

test('calendar show page includes income forecast for active planting', function () {
    // Create an active schedule (not yet harvested)
    $activeSchedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'acres' => 6.2,
        'seed_weight_kg' => 100,
        'date_planted' => Carbon::now()->subDays(30),
        'expected_harvest_date' => Carbon::now()->addDays(60),
        'actual_harvest_date' => null,
        'expected_yield' => 5000,
        'expected_income' => 300000,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('sensors.show', $this->sensor));

    $response->assertSuccessful();

    $incomeForecast = $response->viewData('page')['props']['incomeForecast'];

    expect($incomeForecast)->not->toBeNull()
        ->and($incomeForecast)->toHaveKeys([
            'predicted_income',
            'optimistic_income',
            'pessimistic_income',
            'income_per_acre',
            'confidence',
            'confidence_score',
            'yield_component',
            'price_component',
            'calculation_breakdown',
        ])
        ->and($incomeForecast['predicted_income'])->toBeGreaterThan(0)
        ->and($incomeForecast['optimistic_income'])->toBeGreaterThan($incomeForecast['predicted_income'])
        ->and($incomeForecast['pessimistic_income'])->toBeLessThan($incomeForecast['predicted_income'])
        ->and($incomeForecast['confidence'])->toBeIn(['high', 'medium', 'low']);
});

test('calendar show page does not include income forecast for harvested planting', function () {
    // Create a harvested schedule
    $harvestedSchedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'actual_harvest_date' => Carbon::now()->subDays(10),
        'yield' => 5000,
        'income' => 250000,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('sensors.show', $this->sensor));

    $response->assertSuccessful();

    $incomeForecast = $response->viewData('page')['props']['incomeForecast'];

    expect($incomeForecast)->toBeNull();
});

test('income forecast includes both yield and price components', function () {
    $activeSchedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'acres' => 6.2,
        'seed_weight_kg' => 100,
        'date_planted' => Carbon::now()->subDays(30),
        'expected_harvest_date' => Carbon::now()->addDays(60),
        'actual_harvest_date' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('sensors.show', $this->sensor));

    $incomeForecast = $response->viewData('page')['props']['incomeForecast'];

    // Verify yield component
    expect($incomeForecast['yield_component'])->toHaveKeys([
        'predicted_yield',
        'optimistic_yield',
        'pessimistic_yield',
        'confidence',
        'confidence_score',
        'model_type',
    ])
        ->and($incomeForecast['yield_component']['predicted_yield'])->toBeGreaterThan(0);

    // Verify price component
    expect($incomeForecast['price_component'])->toHaveKeys([
        'forecast_price',
        'optimistic_price',
        'pessimistic_price',
        'current_price',
        'trend',
        'confidence',
        'confidence_score',
        'price_volatility',
    ])
        ->and($incomeForecast['price_component']['trend'])->toBeIn(['increasing', 'decreasing', 'stable']);
});

test('income forecast includes calculation breakdown', function () {
    $activeSchedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'acres' => 6.2,
        'seed_weight_kg' => 100,
        'date_planted' => Carbon::now()->subDays(30),
        'expected_harvest_date' => Carbon::now()->addDays(60),
        'actual_harvest_date' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('sensors.show', $this->sensor));

    $incomeForecast = $response->viewData('page')['props']['incomeForecast'];

    expect($incomeForecast['calculation_breakdown'])->toHaveKeys([
        'formula',
        'yield_kg',
        'price_per_kg',
        'result',
    ])
        ->and($incomeForecast['calculation_breakdown']['formula'])->toBeString()
        ->and($incomeForecast['calculation_breakdown']['result'])->toBe($incomeForecast['predicted_income']);
});

test('income forecast includes historical income data', function () {
    $activeSchedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'acres' => 6.2,
        'seed_weight_kg' => 100,
        'date_planted' => Carbon::now()->subDays(30),
        'expected_harvest_date' => Carbon::now()->addDays(60),
        'actual_harvest_date' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('sensors.show', $this->sensor));

    $incomeForecast = $response->viewData('page')['props']['incomeForecast'];

    expect($incomeForecast['historical_income'])->toBeArray()
        ->and($incomeForecast['historical_income'])->not->toBeEmpty();

    // Verify structure of historical income records
    $firstHistorical = $incomeForecast['historical_income'][0];
    expect($firstHistorical)->toHaveKeys([
        'harvest_date',
        'income',
        'income_per_acre',
        'yield',
        'acres',
    ]);
});

test('income forecast calculates variance from expected income', function () {
    $activeSchedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'acres' => 6.2,
        'seed_weight_kg' => 100,
        'date_planted' => Carbon::now()->subDays(30),
        'expected_harvest_date' => Carbon::now()->addDays(60),
        'actual_harvest_date' => null,
        'expected_income' => 300000,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('sensors.show', $this->sensor));

    $incomeForecast = $response->viewData('page')['props']['incomeForecast'];

    expect($incomeForecast['expected_income'])->toBe(300000.0);

    // If variance exists, it should be calculated
    if ($incomeForecast['variance_from_expected'] !== null) {
        expect($incomeForecast['variance_from_expected_percent'])->not->toBeNull();
    }
});
