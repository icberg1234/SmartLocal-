<?php

declare(strict_types=1);

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
