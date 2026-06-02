<?php

declare(strict_types=1);

use App\Modules\Core\Models\Plan;
use Database\Seeders\PlansSeeder;

it('exposes active packages as public base data', function () {
    $this->seed(PlansSeeder::class);

    $this->getJson('/api/v1/plans')
        ->assertOk()
        ->assertJsonFragment(['key' => 'silver'])
        ->assertJsonFragment(['key' => 'gold']);
});

it('seeds the silver package with the documented quota and features', function () {
    $this->seed(PlansSeeder::class);

    $silver = Plan::query()->where('key', 'silver')->firstOrFail();

    expect($silver->store_quota)->toBe(50);
    expect($silver->duration_days)->toBe(180);
    expect($silver->features)->toBeArray()->not->toBeEmpty();
});

it('is idempotent when seeded twice', function () {
    $this->seed(PlansSeeder::class);
    $this->seed(PlansSeeder::class);

    expect(Plan::query()->count())->toBe(2);
});
