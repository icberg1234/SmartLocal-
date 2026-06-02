<?php

declare(strict_types=1);

use App\Modules\Festival\Http\Controllers\FestivalController;
use App\Modules\Festival\Http\Controllers\FollowController;
use Illuminate\Support\Facades\Route;

// Mall manager: create + activate festivals.
Route::middleware(['auth:sanctum', 'role:mall-manager'])->group(function (): void {
    Route::post('/festivals', [FestivalController::class, 'store']);
    Route::post('/festivals/{festival}/activate', [FestivalController::class, 'activate']);
});

// Store owner opts in/out; customer follows a store.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/festivals/{festival}/participate', [FestivalController::class, 'participate']);
    Route::post('/stores/{store}/follow', [FollowController::class, 'store']);
});
