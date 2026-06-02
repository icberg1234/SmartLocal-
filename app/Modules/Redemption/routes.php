<?php

declare(strict_types=1);

use App\Modules\Redemption\Http\Controllers\PointsController;
use App\Modules\Redemption\Http\Controllers\ProfileController;
use App\Modules\Redemption\Http\Controllers\RedeemTokenController;
use App\Modules\Redemption\Http\Controllers\RedemptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    // Customer: rotating QR token, points, profile.
    Route::get('/me/redeem-token', [RedeemTokenController::class, 'show']);
    Route::get('/me/points', [PointsController::class, 'show']);
    Route::get('/me/profile', [ProfileController::class, 'show']);

    // Store cashier/owner: scan + apply.
    Route::post('/redemptions', [RedemptionController::class, 'store']);
});
