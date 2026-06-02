<?php

declare(strict_types=1);

use App\Modules\Venue\Http\Controllers\MapController;
use App\Modules\Venue\Http\Controllers\ParkingController;
use App\Modules\Venue\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Public (customer browse + gateway callback).
Route::get('/malls/{mall}/map', [MapController::class, 'show']);
Route::get('/parking/availability', [ParkingController::class, 'availability']);
Route::get('/payment/callback', [PaymentController::class, 'callback']);

// Authenticated customer.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/parking/reserve', [ParkingController::class, 'reserve']);
    Route::post('/parking/reservations/{reservation}/pay', [PaymentController::class, 'pay']);
});
