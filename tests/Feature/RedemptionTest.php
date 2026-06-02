<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\BusinessUnits\Models\Category;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\Core\Models\Mall;
use App\Modules\Redemption\Models\CustomerProfile;
use App\Modules\Redemption\Models\PointsLedger;
use App\Modules\Redemption\Models\Redemption;
use App\Modules\Redemption\Services\RedeemTokenService;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    $this->seed(CategoriesSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->mall = Mall::factory()->create();

    $this->owner = User::factory()->create(['phone' => '09120000020', 'type' => 'business-owner']);
    $this->owner->assignRole('business-owner');

    $this->store = Store::query()->create([
        'mall_id' => $this->mall->id,
        'category_id' => Category::query()->value('id'),
        'owner_id' => $this->owner->id,
        'name' => 'مدآرا',
        'slug' => 'm-'.uniqid(),
        'member_discount_pct' => 15,
    ]);

    $this->customer = User::factory()->create(['phone' => '09120000021', 'type' => 'customer', 'status' => 'active']);
    $this->customer->assignRole('customer');
});

function redeemToken(): string
{
    return app(RedeemTokenService::class)->issue(test()->customer, test()->mall->id);
}

function mallHeader(): array
{
    return ['X-Mall-Id' => (string) test()->mall->id];
}

it('applies the member discount, awards mall-wide points, and records the visit', function () {
    Sanctum::actingAs($this->owner);

    $this->withHeaders(mallHeader())
        ->postJson('/api/v1/redemptions', ['redeem_token' => redeemToken(), 'amount' => 2_000_000])
        ->assertCreated()
        ->assertJsonPath('data.discount_amount', 300_000)
        ->assertJsonPath('data.final_amount', 1_700_000)
        ->assertJsonPath('data.points_awarded', 17);

    expect(Redemption::query()->count())->toBe(1)
        ->and(PointsLedger::query()->where('user_id', $this->customer->id)->sum('delta'))->toBe(17)
        ->and(CustomerProfile::query()->where('user_id', $this->customer->id)->first()->total_spent)->toBe(1_700_000);
});

it('rejects a replayed token with 409', function () {
    Sanctum::actingAs($this->owner);
    $token = redeemToken();

    $this->withHeaders(mallHeader())->postJson('/api/v1/redemptions', ['redeem_token' => $token, 'amount' => 500_000])->assertCreated();
    $this->withHeaders(mallHeader())->postJson('/api/v1/redemptions', ['redeem_token' => $token, 'amount' => 500_000])->assertStatus(409);
});

it('rejects an expired token with 422', function () {
    config(['smartlocal.redeem_token_ttl' => -10]);
    $token = redeemToken();
    Sanctum::actingAs($this->owner);

    $this->withHeaders(mallHeader())
        ->postJson('/api/v1/redemptions', ['redeem_token' => $token, 'amount' => 500_000])
        ->assertStatus(422);
});

it('blocks after the daily velocity limit (429)', function () {
    Sanctum::actingAs($this->owner);

    for ($i = 0; $i < 3; $i++) {
        $this->withHeaders(mallHeader())
            ->postJson('/api/v1/redemptions', ['redeem_token' => redeemToken(), 'amount' => 200_000])
            ->assertCreated();
    }

    $this->withHeaders(mallHeader())
        ->postJson('/api/v1/redemptions', ['redeem_token' => redeemToken(), 'amount' => 200_000])
        ->assertStatus(429);
});

it('returns 403 when the caller owns no store', function () {
    Sanctum::actingAs($this->customer); // a plain customer, not a store owner

    $this->withHeaders(mallHeader())
        ->postJson('/api/v1/redemptions', ['redeem_token' => redeemToken(), 'amount' => 200_000])
        ->assertStatus(403);
});
