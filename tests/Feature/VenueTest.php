<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\BusinessUnits\Models\Category;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\Core\Models\Floor;
use App\Modules\Core\Models\Mall;
use App\Modules\Venue\Models\ParkingLot;
use App\Modules\Venue\Models\ParkingReservation;
use App\Modules\Venue\Models\Payment;
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
    $this->lot = ParkingLot::query()->create([
        'mall_id' => $this->mall->id, 'name' => 'مرکزی', 'capacity' => 2, 'available' => 2, 'hourly_rate' => 15000,
    ]);
    $this->customer = User::factory()->create(['phone' => '09120000050', 'type' => 'customer', 'status' => 'active']);
    config(['smartlocal.parking_lottery_pct' => 0]);
});

function venueHeader(): array
{
    return ['X-Mall-Id' => (string) test()->mall->id];
}

it('returns the mall map with floors and store positions', function () {
    $floor = Floor::query()->create(['mall_id' => $this->mall->id, 'level' => 1, 'name' => 'طبقه ۱']);
    Store::query()->create([
        'mall_id' => $this->mall->id, 'category_id' => Category::query()->value('id'),
        'floor_id' => $floor->id, 'name' => 'مدآرا', 'slug' => 'v-'.uniqid(),
        'plaque' => 'B-12', 'pos_x' => 10, 'pos_y' => 20,
    ]);

    $this->getJson("/api/v1/malls/{$this->mall->id}/map")
        ->assertOk()
        ->assertJsonPath('floors.0.stores.0.plaque', 'B-12');
});

it('reserves parking and decrements availability', function () {
    Sanctum::actingAs($this->customer);

    $this->withHeaders(venueHeader())
        ->postJson('/api/v1/parking/reserve', ['lot_id' => $this->lot->id])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending_payment')
        ->assertJsonPath('data.requires_payment', true);

    expect(ParkingLot::query()->withoutGlobalScopes()->find($this->lot->id)->available)->toBe(1);
});

it('rejects reservation when the lot is full', function () {
    $this->lot->update(['available' => 0]);
    Sanctum::actingAs($this->customer);

    $this->withHeaders(venueHeader())
        ->postJson('/api/v1/parking/reserve', ['lot_id' => $this->lot->id])
        ->assertStatus(422);
});

it('grants free parking when the lottery wins', function () {
    config(['smartlocal.parking_lottery_pct' => 100]);
    Sanctum::actingAs($this->customer);

    $this->withHeaders(venueHeader())
        ->postJson('/api/v1/parking/reserve', ['lot_id' => $this->lot->id])
        ->assertCreated()
        ->assertJsonPath('data.lottery_win', true)
        ->assertJsonPath('data.status', 'confirmed_free');
});

it('completes a successful payment via callback', function () {
    Sanctum::actingAs($this->customer);
    $resId = $this->withHeaders(venueHeader())
        ->postJson('/api/v1/parking/reserve', ['lot_id' => $this->lot->id])->json('data.id');

    $ref = $this->withHeaders(venueHeader())
        ->postJson("/api/v1/parking/reservations/{$resId}/pay")->json('ref');

    $this->getJson("/api/v1/payment/callback?ref={$ref}&status=ok")
        ->assertOk()
        ->assertJsonPath('paid', true);

    expect(Payment::query()->withoutGlobalScopes()->where('ref', $ref)->value('status'))->toBe('paid')
        ->and(ParkingReservation::query()->withoutGlobalScopes()->find($resId)->status)->toBe('paid');
});

it('marks a failed payment on a failed callback', function () {
    Sanctum::actingAs($this->customer);
    $resId = $this->withHeaders(venueHeader())
        ->postJson('/api/v1/parking/reserve', ['lot_id' => $this->lot->id])->json('data.id');
    $ref = $this->withHeaders(venueHeader())
        ->postJson("/api/v1/parking/reservations/{$resId}/pay")->json('ref');

    $this->getJson("/api/v1/payment/callback?ref={$ref}&status=failed")
        ->assertOk()
        ->assertJsonPath('paid', false);

    expect(Payment::query()->withoutGlobalScopes()->where('ref', $ref)->value('status'))->toBe('failed');
});
