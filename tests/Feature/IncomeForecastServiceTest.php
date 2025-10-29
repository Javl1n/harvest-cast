<?php

use App\Models\Commodity;
use App\Models\CommodityVariant;
use App\Models\Price;
use App\Models\Schedule;
use App\Models\Sensor;
use App\Services\IncomeForecastService;
use App\Services\PriceForecastService;
use App\Services\YieldForecastService;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create mock services
    $this->yieldService = Mockery::mock(YieldForecastService::class);
    $this->priceService = Mockery::mock(PriceForecastService::class);
    $this->service = new IncomeForecastService($this->yieldService, $this->priceService);

    // Create test data
    $this->sensor = Sensor::factory()->create();
    $this->commodity = Commodity::factory()->create(['name' => 'Rice']);
    $this->variant = CommodityVariant::factory()->create([
        'commodity_id' => $this->commodity->id,
        'name' => 'Jasmine Rice',
    ]);

    // Create some price data for the variant
    foreach (range(1, 5) as $daysAgo) {
        Price::factory()->create([
            'variant_id' => $this->variant->id,
            'price' => 50 + ($daysAgo * 2),
            'date' => Carbon::now()->subDays(6 - $daysAgo),
        ]);
    }
});

afterEach(function () {
    Mockery::close();
});

test('it returns null for harvested schedules', function () {
    $schedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'actual_harvest_date' => Carbon::now()->subDays(5),
    ]);

    $forecast = $this->service->getForecast($schedule);

    expect($forecast)->toBeNull();
});

test('it returns null when yield forecast is unavailable', function () {
    $schedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'actual_harvest_date' => null,
        'expected_harvest_date' => Carbon::now()->addDays(60),
    ]);

    $this->yieldService->shouldReceive('getForecast')
        ->with($schedule)
        ->once()
        ->andReturn(null);

    $forecast = $this->service->getForecast($schedule);

    expect($forecast)->toBeNull();
});

test('it returns null when commodity has no variants', function () {
    $commodityNoVariants = Commodity::factory()->create(['name' => 'Corn']);

    $schedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $commodityNoVariants->id,
        'actual_harvest_date' => null,
        'expected_harvest_date' => Carbon::now()->addDays(60),
    ]);

    $this->yieldService->shouldReceive('getForecast')
        ->with($schedule)
        ->once()
        ->andReturn([
            'predicted_yield' => 5000,
            'optimistic_yield' => 6000,
            'pessimistic_yield' => 4000,
            'confidence' => 'high',
            'confidence_score' => 85,
            'days_until_harvest' => 60,
            'environmental_factors' => [],
        ]);

    $forecast = $this->service->getForecast($schedule);

    expect($forecast)->toBeNull();
});

test('it calculates income forecast successfully', function () {
    $schedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'hectares' => 2.5,
        'actual_harvest_date' => null,
        'expected_harvest_date' => Carbon::now()->addDays(60),
        'expected_income' => 300000,
    ]);

    $yieldForecast = [
        'predicted_yield' => 5000, // kg
        'optimistic_yield' => 6000,
        'pessimistic_yield' => 4000,
        'confidence' => 'high',
        'confidence_score' => 85,
        'days_until_harvest' => 60,
        'environmental_factors' => [
            ['factor' => 'Soil Moisture', 'impact' => 'Optimal', 'weight' => 40],
        ],
    ];

    $priceForecast = [
        'forecast_price' => 60, // PHP per kg
        'optimistic_price' => 70,
        'pessimistic_price' => 50,
        'current_price' => 55,
        'trend' => 'increasing',
        'confidence' => 'medium',
        'confidence_score' => 75,
        'price_volatility' => 10,
    ];

    $this->yieldService->shouldReceive('getForecast')
        ->with($schedule)
        ->once()
        ->andReturn($yieldForecast);

    $this->priceService->shouldReceive('getForecastForDate')
        ->once()
        ->andReturn($priceForecast);

    $forecast = $this->service->getForecast($schedule);

    expect($forecast)->not->toBeNull()
        ->and($forecast)->toHaveKeys([
            'predicted_income',
            'optimistic_income',
            'pessimistic_income',
            'expected_income',
            'income_per_hectare',
            'confidence',
            'confidence_score',
            'variance_from_expected',
            'variance_from_expected_percent',
            'harvest_date',
            'days_until_harvest',
            'yield_component',
            'price_component',
            'yield_factors',
            'historical_income',
            'calculation_breakdown',
        ])
        ->and($forecast['predicted_income'])->toBe(300000.0) // 5000 kg × 60 PHP
        ->and($forecast['optimistic_income'])->toBe(420000.0) // 6000 kg × 70 PHP
        ->and($forecast['pessimistic_income'])->toBe(200000.0) // 4000 kg × 50 PHP
        ->and($forecast['expected_income'])->toBe(300000.0)
        ->and($forecast['income_per_hectare'])->toBe(120000.0) // 300000 / 2.5
        ->and($forecast['days_until_harvest'])->toBe(60);
});

test('it calculates combined confidence score', function () {
    $schedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'actual_harvest_date' => null,
        'expected_harvest_date' => Carbon::now()->addDays(60),
    ]);

    $yieldForecast = [
        'predicted_yield' => 5000,
        'optimistic_yield' => 6000,
        'pessimistic_yield' => 4000,
        'confidence' => 'high',
        'confidence_score' => 90, // High yield confidence
        'days_until_harvest' => 60,
        'environmental_factors' => [],
    ];

    $priceForecast = [
        'forecast_price' => 60,
        'optimistic_price' => 70,
        'pessimistic_price' => 50,
        'current_price' => 55,
        'trend' => 'stable',
        'confidence' => 'medium',
        'confidence_score' => 70, // Medium price confidence
        'price_volatility' => 10,
    ];

    $this->yieldService->shouldReceive('getForecast')
        ->with($schedule)
        ->once()
        ->andReturn($yieldForecast);

    $this->priceService->shouldReceive('getForecastForDate')
        ->once()
        ->andReturn($priceForecast);

    $forecast = $this->service->getForecast($schedule);

    // Combined confidence should be average: (90 + 70) / 2 = 80
    expect($forecast['confidence_score'])->toBe(80)
        ->and($forecast['confidence'])->toBe('high'); // 80 >= 70
});

test('it calculates variance from expected income', function () {
    $schedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'actual_harvest_date' => null,
        'expected_harvest_date' => Carbon::now()->addDays(60),
        'expected_income' => 250000, // Expected 250k
    ]);

    $yieldForecast = [
        'predicted_yield' => 5000,
        'optimistic_yield' => 6000,
        'pessimistic_yield' => 4000,
        'confidence' => 'high',
        'confidence_score' => 85,
        'days_until_harvest' => 60,
        'environmental_factors' => [],
    ];

    $priceForecast = [
        'forecast_price' => 60, // Will result in 300k predicted income
        'optimistic_price' => 70,
        'pessimistic_price' => 50,
        'current_price' => 55,
        'trend' => 'increasing',
        'confidence' => 'high',
        'confidence_score' => 85,
        'price_volatility' => 10,
    ];

    $this->yieldService->shouldReceive('getForecast')
        ->with($schedule)
        ->once()
        ->andReturn($yieldForecast);

    $this->priceService->shouldReceive('getForecastForDate')
        ->once()
        ->andReturn($priceForecast);

    $forecast = $this->service->getForecast($schedule);

    // Predicted: 300k, Expected: 250k
    // Variance: 50k, Percentage: (50k / 250k) * 100 = 20%
    expect($forecast['variance_from_expected'])->toBe(50000.0)
        ->and($forecast['variance_from_expected_percent'])->toBe(20.0);
});

test('it includes historical income data', function () {
    // Create historical harvested schedules
    Schedule::factory()->count(3)->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'actual_harvest_date' => Carbon::now()->subMonths(rand(1, 12)),
        'yield' => 5000,
        'income' => 250000,
        'hectares' => 2,
    ]);

    $schedule = Schedule::factory()->create([
        'sensor_id' => $this->sensor->id,
        'commodity_id' => $this->commodity->id,
        'actual_harvest_date' => null,
        'expected_harvest_date' => Carbon::now()->addDays(60),
    ]);

    $yieldForecast = [
        'predicted_yield' => 5000,
        'optimistic_yield' => 6000,
        'pessimistic_yield' => 4000,
        'confidence' => 'high',
        'confidence_score' => 85,
        'days_until_harvest' => 60,
        'environmental_factors' => [],
    ];

    $priceForecast = [
        'forecast_price' => 60,
        'optimistic_price' => 70,
        'pessimistic_price' => 50,
        'current_price' => 55,
        'trend' => 'increasing',
        'confidence' => 'high',
        'confidence_score' => 85,
        'price_volatility' => 10,
    ];

    $this->yieldService->shouldReceive('getForecast')
        ->with($schedule)
        ->once()
        ->andReturn($yieldForecast);

    $this->priceService->shouldReceive('getForecastForDate')
        ->once()
        ->andReturn($priceForecast);

    $forecast = $this->service->getForecast($schedule);

    expect($forecast['historical_income'])->toHaveCount(3)
        ->and($forecast['historical_income'][0])->toHaveKeys([
            'harvest_date',
            'income',
            'income_per_hectare',
            'yield',
            'hectares',
        ]);
});
