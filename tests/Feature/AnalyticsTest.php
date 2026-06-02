<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\BusinessUnits\Models\Category;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\Core\Models\Mall;
use App\Modules\Redemption\Models\Redemption;
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

    $this->manager = User::factory()->create(['phone' => '09120000060', 'type' => 'mall-manager']);
    $this->manager->assignRole('mall-manager');

    $this->owner = User::factory()->create(['phone' => '09120000061', 'type' => 'business-owner']);
    $this->owner->assignRole('business-owner');
    $this->store = Store::query()->create([
        'mall_id' => $this->mall->id, 'category_id' => Category::query()->value('id'),
        'owner_id' => $this->owner->id, 'name' => 'مدآرا', 'slug' => 'a-'.uniqid(), 'member_discount_pct' => 10,
    ]);

    $c1 = User::factory()->create(['phone' => '09120000062', 'type' => 'customer']);
    $c2 = User::factory()->create(['phone' => '09120000063', 'type' => 'customer']);

    // 4 redemptions at the store: c1 x2, c2 x2  (store becomes "active", both are repeat)
    foreach ([$c1, $c1, $c2, $c2] as $i => $cust) {
        Redemption::query()->create([
            'mall_id' => $this->mall->id, 'user_id' => $cust->id, 'store_id' => $this->store->id,
            'nonce' => 'n-'.uniqid().$i, 'amount' => 1_000_000, 'discount_pct' => 10,
            'discount_amount' => 100_000, 'final_amount' => 900_000, 'points_awarded' => 9,
        ]);
    }
});

function analyticsHeader(): array
{
    return ['X-Mall-Id' => (string) test()->mall->id];
}

it('computes mall KPIs for the manager', function () {
    Sanctum::actingAs($this->manager);

    $this->withHeaders(analyticsHeader())->getJson('/api/v1/mall/analytics')
        ->assertOk()
        ->assertJsonPath('monthly_redeeming_customers', 2)
        ->assertJsonPath('redeemed_gmv', 4_000_000)
        ->assertJsonPath('active_redeeming_stores', 1)
        ->assertJsonPath('repeat_redemption_rate', 100);
});

it('computes the store owner KPIs', function () {
    Sanctum::actingAs($this->owner);

    $this->withHeaders(analyticsHeader())->getJson('/api/v1/my/analytics')
        ->assertOk()
        ->assertJsonPath('redemptions', 4)
        ->assertJsonPath('redeemed_gmv', 4_000_000);
});

it('forbids a non-manager from the mall dashboard', function () {
    Sanctum::actingAs($this->owner); // business-owner, not manager

    $this->withHeaders(analyticsHeader())->getJson('/api/v1/mall/analytics')
        ->assertStatus(403);
});
