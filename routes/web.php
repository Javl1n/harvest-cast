<?php

use App\Http\Controllers\CalendarPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::prefix('/calendar')->name('calendar.')->controller(CalendarPageController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });
});



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';