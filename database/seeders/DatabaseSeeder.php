<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Core\Models\Floor;
use App\Modules\Core\Models\Mall;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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
    }
}
