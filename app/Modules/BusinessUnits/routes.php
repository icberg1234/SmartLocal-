<?php

declare(strict_types=1);

use App\Modules\BusinessUnits\Http\Controllers\ProductController;
use App\Modules\BusinessUnits\Http\Controllers\StoreController;
use App\Modules\BusinessUnits\Http\Controllers\StoreDiscountController;
use App\Modules\BusinessUnits\Http\Controllers\WhitelistController;
use Illuminate\Support\Facades\Route;

// Public browse (customer side).
Route::get('/stores', [StoreController::class, 'index']);
Route::get('/stores/{store}', [StoreController::class, 'show']);

// Store owner (authenticated).
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/my/store', [StoreController::class, 'register']);
    Route::get('/my/products', [ProductController::class, 'index']);
    Route::post('/my/products', [ProductController::class, 'store']);
    Route::put('/my/store/member-discount', [StoreDiscountController::class, 'update']);
});

// Mall manager.
Route::middleware(['auth:sanctum', 'role:mall-manager'])->group(function (): void {
    Route::post('/mall/whitelist', [WhitelistController::class, 'store']);
});
