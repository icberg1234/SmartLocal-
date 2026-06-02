<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\BusinessUnits\Models\Category;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\BusinessUnits\Models\StoreWhitelist;
use App\Modules\BusinessUnits\Models\Subscription;
use App\Modules\Core\Models\Mall;
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
    Subscription::query()->create([
        'mall_id' => $this->mall->id, 'plan' => 'silver', 'store_quota' => 50, 'status' => 'active',
    ]);
});

function makeUser(string $phone): User
{
    $u = User::factory()->create(['phone' => $phone, 'type' => 'customer']);
    $u->assignRole('customer');

    return $u;
}

function mallHeaders(): array
{
    return ['X-Mall-Id' => (string) test()->mall->id];
}

it('lets a whitelisted user self-register a store and become a business-owner', function () {
    StoreWhitelist::query()->create(['mall_id' => $this->mall->id, 'phone' => '09120000010']);
    $user = makeUser('09120000010');
    Sanctum::actingAs($user);

    $this->withHeaders(mallHeaders())
        ->postJson('/api/v1/my/store', [
            'category_id' => Category::query()->value('id'),
            'name' => 'مدآرا',
            'plaque' => 'B-12',
        ])
        ->assertCreated();

    expect(Store::query()->withoutGlobalScopes()->where('mall_id', $this->mall->id)->count())->toBe(1)
        ->and($user->fresh()->hasRole('business-owner'))->toBeTrue();
});

it('rejects a non-whitelisted user with 403', function () {
    $user = makeUser('09120000011');
    Sanctum::actingAs($user);

    $this->withHeaders(mallHeaders())
        ->postJson('/api/v1/my/store', ['category_id' => Category::query()->value('id'), 'name' => 'X'])
        ->assertStatus(403);
});

it('rejects registration when the package quota is full', function () {
    Subscription::query()->where('mall_id', $this->mall->id)->update(['store_quota' => 0]);
    StoreWhitelist::query()->create(['mall_id' => $this->mall->id, 'phone' => '09120000012']);
    $user = makeUser('09120000012');
    Sanctum::actingAs($user);

    $this->withHeaders(mallHeaders())
        ->postJson('/api/v1/my/store', ['category_id' => Category::query()->value('id'), 'name' => 'X'])
        ->assertStatus(422);
});

it('validates the member discount range (0-50)', function () {
    $user = makeUser('09120000013');
    $user->assignRole('business-owner');
    Store::query()->create([
        'mall_id' => $this->mall->id, 'category_id' => Category::query()->value('id'),
        'owner_id' => $user->id, 'name' => 'S', 'slug' => 'sx-'.uniqid(), 'member_discount_pct' => 0,
    ]);
    Sanctum::actingAs($user);

    $this->withHeaders(mallHeaders())
        ->putJson('/api/v1/my/store/member-discount', ['member_discount_pct' => 60])
        ->assertStatus(422);

    $this->withHeaders(mallHeaders())
        ->putJson('/api/v1/my/store/member-discount', ['member_discount_pct' => 30])
        ->assertOk();
});

it('lets an owner add a product to their store', function () {
    $user = makeUser('09120000014');
    $user->assignRole('business-owner');
    Store::query()->create([
        'mall_id' => $this->mall->id, 'category_id' => Category::query()->value('id'),
        'owner_id' => $user->id, 'name' => 'S2', 'slug' => 'sy-'.uniqid(), 'member_discount_pct' => 0,
    ]);
    Sanctum::actingAs($user);

    $this->withHeaders(mallHeaders())
        ->postJson('/api/v1/my/products', ['name' => 'مانتو', 'price' => 2400000])
        ->assertCreated();
});

it('exposes active stores publicly', function () {
    Store::query()->create([
        'mall_id' => $this->mall->id, 'category_id' => Category::query()->value('id'),
        'name' => 'Public', 'slug' => 'pub-'.uniqid(), 'status' => 'active',
    ]);

    $this->withHeaders(mallHeaders())->getJson('/api/v1/stores')->assertOk();
});
