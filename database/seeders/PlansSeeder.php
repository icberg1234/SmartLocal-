<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Core\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'key' => 'silver',
                'name' => 'پکیج نقره‌ای',
                'price' => 12_000_000,
                'store_quota' => 50,
                'duration_days' => 180,
                'sort_order' => 1,
                'features' => ['تا ۵۰ فروشگاه', 'مدت ۶ ماه', 'نقشه و پارکینگ', 'یک جشنواره در ماه'],
            ],
            [
                'key' => 'gold',
                'name' => 'پکیج طلایی',
                'price' => 30_000_000,
                'store_quota' => 150,
                'duration_days' => 365,
                'sort_order' => 2,
                'features' => ['تا ۱۵۰ فروشگاه', 'مدت ۱۲ ماه', 'نقشه و پارکینگ', 'جشنواره نامحدود', 'گزارش پیشرفته'],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['key' => $plan['key']], $plan);
        }
    }
}
