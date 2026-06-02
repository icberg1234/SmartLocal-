<?php

declare(strict_types=1);

use App\Modules\Analytics\Http\Controllers\MallAnalyticsController;
use App\Modules\Analytics\Http\Controllers\StoreAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/my/analytics', [StoreAnalyticsController::class, 'summary']);
});

Route::middleware(['auth:sanctum', 'role:mall-manager'])->group(function (): void {
    Route::get('/mall/analytics', [MallAnalyticsController::class, 'summary']);
});
