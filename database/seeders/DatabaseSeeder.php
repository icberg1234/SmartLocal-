<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\BusinessUnits\Models\Subscription;
use App\Modules\Core\Models\Floor;
use App\Modules\Core\Models\Mall;
use App\Modules\Venue\Models\ParkingLot;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesSeeder::class, CategoriesSeeder::class]);

        $mall = Mall::query()->create([
            'name' => 'مجتمع تجاری الماس',
            'type' => 'mall',
            'subdomain' => 'almas',
        ]);

        foreach ([1, 2, 3] as $level) {
            Floor::query()->create([
                'mall_id' => $mall->id,
                'level' => $level,
                'name' => "طبقه {$level}",
            ]);
        }

        Subscription::query()->create([
            'mall_id' => $mall->id,
            'plan' => 'silver',
            'store_quota' => 50,
            'status' => 'active',
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
