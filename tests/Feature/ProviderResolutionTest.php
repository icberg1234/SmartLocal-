<?php

declare(strict_types=1);

use App\Modules\Auth\Services\Sms\FakeSmsSender;
use App\Modules\Auth\Services\Sms\KavenegarSmsSender;
use App\Modules\Auth\Services\Sms\SmsSender;
use App\Modules\Core\Models\Mall;
use App\Modules\Core\Support\CurrentMall;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves the SMS provider per mall from base-data settings', function () {
    $mall = Mall::query()->create([
        'name' => 'مال الف',
        'type' => 'mall',
        'settings' => ['sms' => ['driver' => 'kavenegar', 'kavenegar_key' => 'k-123']],
    ]);

    app(CurrentMall::class)->set($mall->id);

    expect(app(SmsSender::class))->toBeInstanceOf(KavenegarSmsSender::class);
});

it('uses the fake driver for a mall configured with it', function () {
    $mall = Mall::query()->create([
        'name' => 'مال ب',
        'type' => 'mall',
        'settings' => ['sms' => ['driver' => 'fake']],
    ]);

    app(CurrentMall::class)->set($mall->id);

    expect(app(SmsSender::class))->toBeInstanceOf(FakeSmsSender::class);
});

it('falls back to the platform default when a mall has no sms setting', function () {
    $mall = Mall::query()->create(['name' => 'مال ج', 'type' => 'mall']);

    app(CurrentMall::class)->set($mall->id);

    // Platform default (config/services.php) driver is fake.
    expect(app(SmsSender::class))->toBeInstanceOf(FakeSmsSender::class);
});
