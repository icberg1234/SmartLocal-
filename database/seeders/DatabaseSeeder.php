<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\BusinessUnits\Models\Subscription;
use App\Modules\Core\Models\Floor;
use App\Modules\Core\Models\Mall;
use App\Modules\Core\Models\Plan;
use App\Modules\Venue\Models\ParkingLot;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesSeeder::class, CategoriesSeeder::class, PlansSeeder::class]);

        $mall = Mall::query()->create([
            'name' => 'مجتمع تجاری الماس',
            'type' => 'mall',
            'subdomain' => 'almas',
            'settings' => [
                // Per-mall provider config (base data). Defaults to fake; in
                // production set each mall's own driver + credentials here.
                'sms' => ['driver' => 'fake', 'kavenegar_key' => ''],
                'payment' => ['driver' => 'fake', 'zarinpal_merchant' => '', 'callback_url' => ''],
            ],
        ]);

        foreach ([1, 2, 3] as $level) {
            Floor::query()->create([
                'mall_id' => $mall->id,
                'level' => $level,
                'name' => "طبقه {$level}",
            ]);
        }

        /** @var Plan $silver */
        $silver = Plan::query()->where('key', 'silver')->firstOrFail();
        Subscription::query()->create([
            'mall_id' => $mall->id,
            'plan' => $silver->key,
            'plan_id' => $silver->id,
            'store_quota' => $silver->store_quota,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays($silver->duration_days),
        ]);

        ParkingLot::query()->create([
            'mall_id' => $mall->id,
            'name' => 'پارکینگ مرکزی',
            'capacity' => 200,
            'available' => 200,
            'hourly_rate' => 15000,
        ]);
    }
}
