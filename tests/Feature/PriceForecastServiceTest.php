<?php

use App\Models\Commodity;
use App\Models\CommodityVariant;
use App\Models\Price;
use App\Services\PriceForecastService;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = new PriceForecastService;

    // Create test data
    $this->commodity = Commodity::factory()->create(['name' => 'Rice']);
    $this->variant = CommodityVariant::factory()->create([
        'commodity_id' => $this->commodity->id,
        'name' => 'Jasmine Rice',
    ]);
});

test('it returns null when insufficient price data exists', function () {
    // Only create 2 prices (minimum is 3)
    Price::factory()->count(2)->create([
        'variant_id' => $this->variant->id,
    ]);

    $targetDate = Carbon::now()->addDays(30);
    $forecast = $this->service->getForecastForDate($this->variant, $targetDate);

    expect($forecast)->toBeNull();
});

test('it calculates price forecast for future date', function () {
    // Create historical price data with an upward trend
    $basePrice = 50;
    foreach (range(1, 10) as $daysAgo) {
        Price::factory()->create([
            'variant_id' => $this->variant->id,
            'price' => $basePrice + ($daysAgo * 2), // Increasing trend
            'date' => Carbon::now()->subDays(11 - $daysAgo),
        ]);
    }

    $targetDate = Carbon::now()->addDays(7);
    $forecast = $this->service->getForecastForDate($this->variant, $targetDate);

    expect($forecast)->not->toBeNull()
        ->and($forecast)->toHaveKeys([
            'forecast_price',
            'optimistic_price',
            'pessimistic_price',
            'current_price',
            'trend',
            'confidence',
            'confidence_score',
            'price_volatility',
            'target_date',
            'days_ahead',
            'sample_size',
        ])
        ->and($forecast['trend'])->toBe('increasing')
        ->and($forecast['sample_size'])->toBe(10)
        ->and($forecast['days_ahead'])->toBe(7);
});

test('it detects stable price trend', function () {
    // Create prices with minimal variation
    foreach (range(1, 10) as $daysAgo) {
        Price::factory()->create([
            'variant_id' => $this->variant->id,
            'price' => 50 + (rand(-1, 1) * 0.05), // Very stable around 50
            'date' => Carbon::now()->subDays(11 - $daysAgo),
        ]);
    }

    $targetDate = Carbon::now()->addDays(7);
    $forecast = $this->service->getForecastForDate($this->variant, $targetDate);

    expect($forecast['trend'])->toBe('stable');
});

test('it detects decreasing price trend', function () {
    // Create historical price data with a downward trend
    $basePrice = 70;
    foreach (range(1, 10) as $daysAgo) {
        Price::factory()->create([
            'variant_id' => $this->variant->id,
            'price' => $basePrice - ($daysAgo * 2), // Decreasing trend
            'date' => Carbon::now()->subDays(11 - $daysAgo),
        ]);
    }

    $targetDate = Carbon::now()->addDays(7);
    $forecast = $this->service->getForecastForDate($this->variant, $targetDate);

    expect($forecast['trend'])->toBe('decreasing');
});

test('it calculates confidence based on price volatility', function () {
    // Create very stable prices (low volatility = high confidence)
    foreach (range(1, 10) as $daysAgo) {
        Price::factory()->create([
            'variant_id' => $this->variant->id,
            'price' => 50 + (rand(0, 2) * 0.1), // Very low variation
            'date' => Carbon::now()->subDays(11 - $daysAgo),
        ]);
    }

    $targetDate = Carbon::now()->addDays(7);
    $forecast = $this->service->getForecastForDate($this->variant, $targetDate);

    expect($forecast['confidence'])->toBeIn(['high', 'medium', 'low'])
        ->and($forecast['confidence_score'])->toBeGreaterThanOrEqual(0)
        ->and($forecast['confidence_score'])->toBeLessThanOrEqual(100);
});

test('it generates multiple forecast periods', function () {
    // Create historical price data
    foreach (range(1, 10) as $daysAgo) {
        Price::factory()->create([
            'variant_id' => $this->variant->id,
            'price' => 50 + ($daysAgo * 1),
            'date' => Carbon::now()->subDays(11 - $daysAgo),
        ]);
    }

    $forecast = $this->service->getMultipleForecast($this->variant);

    expect($forecast)->not->toBeNull()
        ->and($forecast)->toHaveKeys(['trend', 'slope', 'price_change_percent', 'forecasts', 'confidence'])
        ->and($forecast['forecasts'])->toHaveCount(6) // 3 daily + 3 monthly
        ->and($forecast['forecasts'][0])->toHaveKey('type', 'daily')
        ->and($forecast['forecasts'][3])->toHaveKey('type', 'monthly');
});

test('it ensures pessimistic price is never negative', function () {
    // Create prices with high volatility
    foreach (range(1, 10) as $daysAgo) {
        Price::factory()->create([
            'variant_id' => $this->variant->id,
            'price' => 10 + (rand(-5, 5) * 2), // High volatility around low price
            'date' => Carbon::now()->subDays(11 - $daysAgo),
        ]);
    }

    $targetDate = Carbon::now()->addDays(7);
    $forecast = $this->service->getForecastForDate($this->variant, $targetDate);

    expect($forecast['pessimistic_price'])->toBeGreaterThanOrEqual(0);
});
