<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\BusinessUnits\Models\Product;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\Core\Models\Mall;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('logs in a seeded user via dev login without OTP', function () {
    $this->seed(RolesSeeder::class);
    $user = User::factory()->create(['phone' => '09120000099', 'type' => 'customer']);
    $user->assignRole('customer');

    $this->postJson('/api/v1/dev/login', ['phone' => '09120000099'])
        ->assertOk()
        ->assertJsonPath('data.roles.0', 'customer')
        ->assertJsonStructure(['token', 'data' => ['id', 'phone', 'roles']]);
});

it('returns 404 for an unknown dev-login phone', function () {
    $this->postJson('/api/v1/dev/login', ['phone' => '09129999999'])->assertNotFound();
});

it('builds a complete zero-to-hundred demo dataset', function () {
    $this->seed(RolesSeeder::class);
    $this->seed(CategoriesSeeder::class);
    Mall::factory()->create(['subdomain' => 'almas']);

    $this->seed(DemoSeeder::class);

    // one account per role
    expect(User::query()->where('phone', '09120000001')->exists())->toBeTrue()
        ->and(User::query()->where('phone', '09120000002')->exists())->toBeTrue()
        ->and(User::query()->where('phone', '09120000003')->exists())->toBeTrue();

    // a fully populated store with products
    expect(Store::query()->withoutGlobalScopes()->where('name', 'بوتیک رویا')->exists())->toBeTrue()
        ->and(Product::query()->withoutGlobalScopes()->count())->toBeGreaterThanOrEqual(4);

    // dev login as the owner then works
    $this->postJson('/api/v1/dev/login', ['phone' => '09120000003'])
        ->assertOk()
        ->assertJsonPath('data.roles.0', 'business-owner');
});
