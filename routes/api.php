<?php

use App\Http\Controllers\CalendarPageController;
use App\Http\Controllers\CropPricesController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\SensorReadingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::post('/sensor', function (Request $request) {

//     return $request->all();
// });

Route::prefix('/sensors')
->group(function () {
    Route::controller(SensorController::class)->group(function () {
        Route::post('/register', 'store');
    });
    
    Route::controller(SensorReadingController::class)->group(function () {
        Route::post('/data', 'store');
    });
});


Route::prefix('/crops')
->group(function () {
    Route::controller(CropPricesController::class)->group(function () {
        Route::post('/data', 'store');
    });
    
    Route::controller(CalendarPageController::class)->group(function () {
        Route::get('/recommendations/{sensor}', 'recommendations');
    });
});
