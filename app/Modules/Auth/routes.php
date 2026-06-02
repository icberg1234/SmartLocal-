<?php

declare(strict_types=1);

use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Auth\Http\Controllers\DevAuthController;
use Illuminate\Support\Facades\Route;

// Loaded by ModuleServiceProvider under /api/v1 with the `api` group.
Route::post('/auth/request-otp', [AuthController::class, 'requestOtp'])
    ->middleware('throttle:otp');

Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])
    ->middleware('throttle:10,1');

Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

// Demo-only quick login (no SMS) — never registered in production.
if (! app()->isProduction()) {
    Route::post('/dev/login', [DevAuthController::class, 'login']);
}
