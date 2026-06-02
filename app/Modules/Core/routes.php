<?php

declare(strict_types=1);

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
