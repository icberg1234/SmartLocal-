<?php

declare(strict_types=1);

use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tenant resolution runs on every API request (sets CurrentMall).
        $middleware->api(append: [
            ResolveTenant::class,
        ]);

        // RBAC aliases (role/permission) are registered in Phase 1 with spatie/laravel-permission.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
