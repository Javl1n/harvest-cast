<?php

use App\Http\Controllers\CalendarPageController;
use App\Http\Controllers\CropImageController;
use App\Http\Controllers\PricingForecastController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return redirect()->route('calendar.index');
    })->name('dashboard');

    Route::prefix('/calendar')->name('calendar.')->controller(CalendarPageController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/sensor/{sensor}', 'show')->name('show');
        });

    Route::prefix('/crops')->name('crops.')->controller(CalendarPageController::class)
        ->middleware('admin')
        ->group(function () {
            Route::get('/create/sensor/{sensor}', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::patch('/harvest/{schedule}', 'harvest')->name('harvest');
        });

    Route::prefix('/pricing-forecast')->name('pricing-forecast.')->controller(PricingForecastController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
        });

    Route::prefix('/crop-images')->name('crop-images.')->controller(CropImageController::class)
        ->middleware('admin')
        ->group(function () {
            Route::post('/', 'store')->name('store');
            Route::delete('/{image}', 'destroy')->name('destroy');
        });

    Route::get('/crop-images/{image}', [CropImageController::class, 'show'])->name('crop-images.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
