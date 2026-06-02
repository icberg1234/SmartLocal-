<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Auth\Models\OtpCode;
use App\Modules\Auth\Services\Sms\FakeSmsSender;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    FakeSmsSender::flush();
    $this->seed(RolesSeeder::class);
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
});

function latestOtp(): string
{
    $msg = end(FakeSmsSender::$sent)['message'];
    preg_match('/(\d{6})/', $msg, $m);

    return $m[1];
}

it('sends an OTP and stores a code with ~120s expiry', function () {
    $this->postJson('/api/v1/auth/request-otp', ['phone' => '09123456789'])
        ->assertOk();

    $otp = OtpCode::query()->where('phone', '09123456789')->first();
    expect($otp)->not->toBeNull()
        ->and(FakeSmsSender::$sent)->toHaveCount(1)
        ->and($otp->expires_at->diffInSeconds(now()))->toBeLessThanOrEqual(121);
});

it('rejects an invalid phone format', function () {
    $this->postJson('/api/v1/auth/request-otp', ['phone' => '12345'])
        ->assertStatus(422);
});

it('throttles after 5 requests per minute', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/request-otp', ['phone' => '09120000000'])->assertOk();
    }
    $this->postJson('/api/v1/auth/request-otp', ['phone' => '09120000000'])
        ->assertStatus(429);
});

it('verifies a correct code, creates a customer, and returns a token', function () {
    $this->postJson('/api/v1/auth/request-otp', ['phone' => '09121112233']);

    $res = $this->postJson('/api/v1/auth/verify-otp', [
        'phone' => '09121112233',
        'code' => latestOtp(),
    ])->assertOk();

    $res->assertJsonPath('data.roles.0', 'customer');
    expect($res->json('token'))->not->toBeEmpty()
        ->and($res->json('is_new'))->toBeTrue()
        ->and(User::query()->where('phone', '09121112233')->exists())->toBeTrue();
});

it('rejects an expired code', function () {
    $this->postJson('/api/v1/auth/request-otp', ['phone' => '09121112244']);
    $code = latestOtp();
    OtpCode::query()->where('phone', '09121112244')->update(['expires_at' => now()->subMinute()]);

    $this->postJson('/api/v1/auth/verify-otp', ['phone' => '09121112244', 'code' => $code])
        ->assertStatus(422);
});

it('locks after 3 wrong attempts', function () {
    $this->postJson('/api/v1/auth/request-otp', ['phone' => '09121112255']);

    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/v1/auth/verify-otp', ['phone' => '09121112255', 'code' => '000000'])
            ->assertStatus(422);
    }

    $otp = OtpCode::query()->where('phone', '09121112255')->first();
    expect($otp->locked_until)->not->toBeNull();
});

it('logs out an authenticated user', function () {
    $this->postJson('/api/v1/auth/request-otp', ['phone' => '09121112266']);
    $token = $this->postJson('/api/v1/auth/verify-otp', [
        'phone' => '09121112266', 'code' => latestOtp(),
    ])->json('token');

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
});
