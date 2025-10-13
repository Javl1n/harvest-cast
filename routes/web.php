<?php

use App\Http\Controllers\CalendarPageController;
use App\Http\Controllers\PricingForecastController;
use App\Http\Controllers\SensorController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::prefix('/sensors')->name('sensors.')->controller(SensorController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{sensor}', 'show')->name('show');
    });

    Route::prefix('/crops')->name('crops.')->controller(CalendarPageController::class)
    ->group(function () {
        Route::get('/create/sensor/{sensor}', 'create')->name('create');
        Route::post('/', 'store')->name('store');
    });

    Route::prefix('/pricing-forecast')->name('pricing-forecast.')->controller(PricingForecastController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });
});



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';