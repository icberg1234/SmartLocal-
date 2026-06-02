<?php

declare(strict_types=1);

use App\Modules\Auth\Models\OtpCode;
use App\Modules\Auth\Services\Sms\FakeSmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    FakeSmsSender::flush();
});

it('renders the OTP SMS from the configurable brand and template', function () {
    config([
        'smartlocal.brand' => 'پاساژ تست',
        'smartlocal.templates.otp_sms' => 'رمز {brand}: {code}',
    ]);

    $this->postJson('/api/v1/auth/request-otp', ['phone' => '09120000011'])->assertOk();

    $message = end(FakeSmsSender::$sent)['message'];
    expect($message)->toStartWith('رمز پاساژ تست:');
});

it('honors the configured OTP TTL', function () {
    config(['smartlocal.otp.ttl_seconds' => 300]);

    $this->postJson('/api/v1/auth/request-otp', ['phone' => '09120000022'])->assertOk();

    $otp = OtpCode::query()->where('phone', '09120000022')->firstOrFail();
    $delta = $otp->expires_at->getTimestamp() - now()->getTimestamp();

    expect($delta)->toBeGreaterThan(250)->toBeLessThanOrEqual(301);
});
