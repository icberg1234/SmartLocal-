<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Auth\Models\Consent;
use App\Modules\BusinessUnits\Models\Category;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\Core\Models\Mall;
use App\Modules\Festival\Models\Festival;
use App\Modules\Festival\Models\FestivalStore;
use App\Modules\Festival\Models\Follow;
use App\Modules\Festival\Models\Notification;
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

    $this->manager = User::factory()->create(['phone' => '09120000040', 'type' => 'mall-manager']);
    $this->manager->assignRole('mall-manager');

    $this->owner = User::factory()->create(['phone' => '09120000041', 'type' => 'business-owner']);
    $this->owner->assignRole('business-owner');
    $this->store = Store::query()->create([
        'mall_id' => $this->mall->id, 'category_id' => Category::query()->value('id'),
        'owner_id' => $this->owner->id, 'name' => 'مدآرا', 'slug' => 'f-'.uniqid(), 'member_discount_pct' => 30,
    ]);

    // follower WITH marketing consent
    $this->follower1 = User::factory()->create(['phone' => '09120000042', 'type' => 'customer', 'status' => 'active']);
    Follow::query()->create(['mall_id' => $this->mall->id, 'user_id' => $this->follower1->id, 'store_id' => $this->store->id]);
    Consent::query()->create(['user_id' => $this->follower1->id, 'scope' => 'marketing', 'granted_at' => now()]);

    // follower WITHOUT consent
    $this->follower2 = User::factory()->create(['phone' => '09120000043', 'type' => 'customer', 'status' => 'active']);
    Follow::query()->create(['mall_id' => $this->mall->id, 'user_id' => $this->follower2->id, 'store_id' => $this->store->id]);
});

function festivalHeader(): array
{
    return ['X-Mall-Id' => (string) test()->mall->id];
}

it('lets a manager create a festival and invites + notifies the store', function () {
    Sanctum::actingAs($this->manager);

    $this->withHeaders(festivalHeader())
        ->postJson('/api/v1/festivals', [
            'title' => 'جشنواره پاییزه',
            'discount_pct' => 30,
            'store_ids' => [$this->store->id],
        ])
        ->assertCreated();

    expect(FestivalStore::query()->where('store_id', $this->store->id)->where('status', 'invited')->exists())->toBeTrue()
        ->and(Notification::query()->withoutGlobalScopes()->where('user_id', $this->owner->id)->exists())->toBeTrue();
});

it('lets the invited store opt in', function () {
    $festival = Festival::query()->create(['mall_id' => $this->mall->id, 'title' => 'X', 'discount_pct' => 20, 'status' => 'active']);
    FestivalStore::query()->create(['festival_id' => $festival->id, 'store_id' => $this->store->id, 'status' => 'invited']);

    Sanctum::actingAs($this->owner);
    $this->withHeaders(festivalHeader())
        ->postJson("/api/v1/festivals/{$festival->id}/participate", ['decision' => 'join'])
        ->assertOk();

    expect(FestivalStore::query()->where('festival_id', $festival->id)->where('store_id', $this->store->id)->value('status'))->toBe('joined');
});

it('on activation, notifies only followers who consented', function () {
    $festival = Festival::query()->create(['mall_id' => $this->mall->id, 'title' => 'پاییزه', 'discount_pct' => 30, 'status' => 'active']);
    FestivalStore::query()->create(['festival_id' => $festival->id, 'store_id' => $this->store->id, 'status' => 'joined']);

    Sanctum::actingAs($this->manager);
    $this->withHeaders(festivalHeader())
        ->postJson("/api/v1/festivals/{$festival->id}/activate")
        ->assertOk()
        ->assertJsonPath('notified', 1);

    expect(Notification::query()->withoutGlobalScopes()->where('user_id', $this->follower1->id)->where('festival_id', $festival->id)->exists())->toBeTrue()
        ->and(Notification::query()->withoutGlobalScopes()->where('user_id', $this->follower2->id)->where('festival_id', $festival->id)->exists())->toBeFalse();
});

it('lets a customer follow a store', function () {
    $customer = User::factory()->create(['phone' => '09120000044', 'type' => 'customer', 'status' => 'active']);
    Sanctum::actingAs($customer);

    $this->withHeaders(festivalHeader())
        ->postJson("/api/v1/stores/{$this->store->id}/follow")
        ->assertCreated();

    expect(Follow::query()->withoutGlobalScopes()->where('user_id', $customer->id)->where('store_id', $this->store->id)->exists())->toBeTrue();
});
