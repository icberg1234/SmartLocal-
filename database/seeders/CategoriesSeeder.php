<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\BusinessUnits\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'خوراکی', 'slug' => 'food', 'template' => 'food'],
            ['name' => 'پوشاک', 'slug' => 'clothing', 'template' => 'clothing'],
            ['name' => 'دیجیتال', 'slug' => 'digital', 'template' => 'digital'],
        ];

        foreach ($categories as $c) {
            Category::query()->firstOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
