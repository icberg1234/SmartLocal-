<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\BusinessUnits\Models\Subscription;
use App\Modules\Core\Models\Mall;
use App\Modules\Core\Models\Plan;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->mall = Mall::factory()->create();
});

function actingAsRole(string $role): User
{
    $user = User::factory()->create(['phone' => '0912'.random_int(1000000, 9999999), 'type' => 'staff']);
    $user->assignRole($role);
    Sanctum::actingAs($user);

    return $user;
}

function adminHeaders(): array
{
    return ['X-Mall-Id' => (string) test()->mall->id];
}

it('lets a mall-manager update provider settings and encrypts the secret at rest', function () {
    actingAsRole('mall-manager');

    test()->withHeaders(adminHeaders())->putJson('/api/v1/admin/mall/settings', [
        'brand' => 'پاساژ الماس',
        'sms' => ['driver' => 'kavenegar', 'kavenegar_key' => 'secret-key-xyz'],
    ])->assertOk()
        ->assertJsonPath('data.brand', 'پاساژ الماس')
        ->assertJsonPath('data.sms.kavenegar_key_set', true);

    // The secret is NOT in the response and NOT stored in plaintext...
    $raw = (string) DB::table('malls')->where('id', test()->mall->id)->value('settings');
    expect($raw)->not->toContain('secret-key-xyz');

    // ...but decrypts correctly through the model.
    expect(test()->mall->fresh()->setting('sms.kavenegar_key'))->toBe('secret-key-xyz');
});

it('does not overwrite a stored secret with a blank value', function () {
    actingAsRole('mall-manager');

    test()->withHeaders(adminHeaders())->putJson('/api/v1/admin/mall/settings', [
        'sms' => ['driver' => 'kavenegar', 'kavenegar_key' => 'keep-me'],
    ])->assertOk();

    test()->withHeaders(adminHeaders())->putJson('/api/v1/admin/mall/settings', [
        'sms' => ['driver' => 'kavenegar', 'kavenegar_key' => ''],
    ])->assertOk();

    expect(test()->mall->fresh()->setting('sms.kavenegar_key'))->toBe('keep-me');
});

it('forbids a non-manager from updating mall settings', function () {
    actingAsRole('customer');

    test()->withHeaders(adminHeaders())
        ->putJson('/api/v1/admin/mall/settings', ['brand' => 'x'])
        ->assertForbidden();
});

it('lets a super-admin create and update a package', function () {
    actingAsRole('super-admin');

    test()->postJson('/api/v1/admin/plans', [
        'key' => 'platinum', 'name' => 'پلاتینیوم', 'price' => 50000000,
        'store_quota' => 300, 'duration_days' => 365, 'features' => ['همه امکانات'],
    ])->assertCreated();

    $plan = Plan::query()->where('key', 'platinum')->firstOrFail();

    test()->putJson("/api/v1/admin/plans/{$plan->id}", [
        'key' => 'platinum', 'name' => 'پلاتینیوم پلاس', 'price' => 60000000,
        'store_quota' => 400, 'duration_days' => 365,
    ])->assertOk()->assertJsonPath('data.name', 'پلاتینیوم پلاس');
});

it('forbids a manager from creating packages', function () {
    actingAsRole('mall-manager');

    test()->postJson('/api/v1/admin/plans', [
        'key' => 'x', 'name' => 'x', 'price' => 1, 'store_quota' => 1, 'duration_days' => 1,
    ])->assertForbidden();
});

it('returns a manager overview with subscription quota usage', function () {
    actingAsRole('mall-manager');
    Subscription::query()->create([
        'mall_id' => test()->mall->id, 'plan' => 'silver', 'store_quota' => 50, 'status' => 'active',
    ]);

    test()->withHeaders(adminHeaders())->getJson('/api/v1/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.quota', 50)
        ->assertJsonPath('data.stores_used', 0)
        ->assertJsonPath('data.stores_remaining', 50);
});
