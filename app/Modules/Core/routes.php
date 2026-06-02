<?php

declare(strict_types=1);

use App\Modules\Core\Http\Controllers\Admin\AdminPlanController;
use App\Modules\Core\Http\Controllers\Admin\MallSettingsController;
use App\Modules\Core\Http\Controllers\PlanController;
use App\Modules\Core\Models\Mall;
use Illuminate\Support\Facades\Route;

// Loaded by ModuleServiceProvider under prefix /api/v1 with the `api` middleware group.
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'smartlocal',
        'malls' => Mall::query()->count(),
    ]);
})->name('health');

// Public package catalog (base data) — shown on the mall-manager homepage.
Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');

// Mall-manager: configure own mall's base-data settings (providers + brand).
Route::middleware(['auth:sanctum', 'role:mall-manager'])->prefix('admin')->group(function (): void {
    Route::get('/mall/settings', [MallSettingsController::class, 'show']);
    Route::put('/mall/settings', [MallSettingsController::class, 'update']);
});

// Super-admin: manage packages (master data).
Route::middleware(['auth:sanctum', 'role:super-admin'])->prefix('admin')->group(function (): void {
    Route::post('/plans', [AdminPlanController::class, 'store']);
    Route::put('/plans/{plan}', [AdminPlanController::class, 'update']);
});
