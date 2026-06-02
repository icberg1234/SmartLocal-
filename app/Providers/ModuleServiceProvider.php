<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Auth\Services\Sms\FakeSmsSender;
use App\Modules\Auth\Services\Sms\KavenegarSmsSender;
use App\Modules\Auth\Services\Sms\SmsSender;
use App\Modules\Core\Support\CurrentMall;
use App\Modules\Venue\Services\Payment\FakeGateway;
use App\Modules\Venue\Services\Payment\PaymentGateway;
use App\Modules\Venue\Services\Payment\ZarinpalGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
            if (config('services.sms.driver') === 'kavenegar') {
                return new KavenegarSmsSender((string) config('services.sms.kavenegar_key', ''));
            }

            return new FakeSmsSender();
        });

        $this->app->bind(PaymentGateway::class, function (): PaymentGateway {
            if (config('services.payment.driver') === 'zarinpal') {
                return new ZarinpalGateway(
                    (string) config('services.payment.zarinpal_merchant', ''),
                    (string) config('services.payment.callback_url', ''),
                );
            }

            return new FakeGateway();
        });
    }

    public function boot(): void
    {
        // D2 fix: OTP request throttle keyed by phone (not IP — shared mobile NAT).
        RateLimiter::for('otp', function (Request $request): Limit {
            return Limit::perMinute(5)->by((string) $request->input('phone', (string) $request->ip()));
        });

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
