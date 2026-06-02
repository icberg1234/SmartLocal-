<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Auth\Services\Sms\FakeSmsSender;
use App\Modules\Auth\Services\Sms\KavenegarSmsSender;
use App\Modules\Auth\Services\Sms\SmsSender;
use App\Modules\Core\Support\CurrentMall;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Auto-discovers modules under app/Modules/* and wires their
 * routes (routes.php) and migrations (Database/Migrations).
 * Keeps the monolith modular without per-module manual registration.
 */
final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentMall::class);

        $this->app->bind(SmsSender::class, function (): SmsSender {
            if (env('SMS_DRIVER', 'fake') === 'kavenegar') {
                return new KavenegarSmsSender((string) env('KAVENEGAR_API_KEY', ''));
            }

            return new FakeSmsSender();
        });
    }

    public function boot(): void
    {
        foreach ($this->modulePaths() as $module) {
            $routes = $module.DIRECTORY_SEPARATOR.'routes.php';
            if (is_file($routes)) {
                Route::middleware('api')
                    ->prefix('api/v1')
                    ->group($routes);
            }

            $migrations = $module.DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR.'Migrations';
            if (is_dir($migrations)) {
                $this->loadMigrationsFrom($migrations);
            }
        }
    }

    /**
     * @return array<int,string>
     */
    private function modulePaths(): array
    {
        $paths = glob(app_path('Modules'.DIRECTORY_SEPARATOR.'*'), GLOB_ONLYDIR);

        return $paths === false ? [] : $paths;
    }
}
