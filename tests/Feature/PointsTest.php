<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Core\Models\Mall;
use App\Modules\Redemption\Models\PointsLedger;
use App\Modules\Redemption\Services\CrmService;
use App\Modules\Redemption\Services\PointsService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->mall = Mall::factory()->create();
    $this->customer = User::factory()->create(['phone' => '09120000030', 'type' => 'customer', 'status' => 'active']);
    $this->customer->assignRole('customer');
});

function pointsHeader(): array
{
    return ['X-Mall-Id' => (string) test()->mall->id];
}

it('accrues points and computes the tier', function () {
    $svc = app(PointsService::class);

    $svc->accrue($this->customer, $this->mall->id, 50);
    expect($svc->balance($this->customer, $this->mall->id))->toBe(50)
        ->and($svc->tier(50))->toBe('bronze');

    $svc->accrue($this->customer, $this->mall->id, 60);
    expect($svc->balance($this->customer, $this->mall->id))->toBe(110)
        ->and($svc->tier(110))->toBe('silver');
});

it('excludes expired points from the balance', function () {
    PointsLedger::query()->create([
        'mall_id' => $this->mall->id, 'user_id' => $this->customer->id,
        'delta' => 40, 'reason' => 'redemption', 'expires_at' => now()->subDay(),
    ]);
    PointsLedger::query()->create([
        'mall_id' => $this->mall->id, 'user_id' => $this->customer->id,
        'delta' => 30, 'reason' => 'redemption', 'expires_at' => now()->addDay(),
    ]);

    expect(app(PointsService::class)->balance($this->customer, $this->mall->id))->toBe(30);
});

it('spends points and rejects when insufficient', function () {
    $svc = app(PointsService::class);
    $svc->accrue($this->customer, $this->mall->id, 100);

    $svc->spend($this->customer, $this->mall->id, 40);
    expect($svc->balance($this->customer, $this->mall->id))->toBe(60);

    expect(fn () => $svc->spend($this->customer, $this->mall->id, 1000))
        ->toThrow(HttpException::class);
});

it('GET /me/points returns balance and tier', function () {
    app(PointsService::class)->accrue($this->customer, $this->mall->id, 120);
    Sanctum::actingAs($this->customer);

    $this->withHeaders(pointsHeader())->getJson('/api/v1/me/points')
        ->assertOk()
        ->assertJsonPath('balance', 120)
        ->assertJsonPath('tier', 'silver');
});

it('GET /me/profile returns profile, points and tier', function () {
    app(CrmService::class)->recordVisit($this->customer, $this->mall->id, 5_000_000);
    Sanctum::actingAs($this->customer);

    $this->withHeaders(pointsHeader())->getJson('/api/v1/me/profile')
        ->assertOk()
        ->assertJsonPath('profile.visit_count', 1)
        ->assertJsonPath('profile.total_spent', 5_000_000);
});
