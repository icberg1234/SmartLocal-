<?php

declare(strict_types=1);

use App\Modules\Redemption\Http\Controllers\RedemptionController;
use App\Modules\Redemption\Http\Controllers\RedeemTokenController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    // Customer: rotating QR token.
    Route::get('/me/redeem-token', [RedeemTokenController::class, 'show']);

    // Store cashier/owner: scan + apply.
    Route::post('/redemptions', [RedemptionController::class, 'store']);
});
