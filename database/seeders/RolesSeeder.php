<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super-admin', 'mall-manager', 'business-owner', 'cashier', 'customer'] as $role) {
            Role::findOrCreate($role);
        }
    }
}
