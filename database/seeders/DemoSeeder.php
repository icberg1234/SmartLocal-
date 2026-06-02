<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\BusinessUnits\Models\Category;
use App\Modules\BusinessUnits\Models\Product;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\BusinessUnits\Models\StoreWhitelist;
use App\Modules\Core\Models\Mall;
use App\Modules\Redemption\Services\PointsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * "Zero to hundred" demo: the sample mall fully populated with one demo
 * account per role (customer / mall-manager / store-owner), a complete owned
 * store with products, a couple of browsable stores, and a points balance.
 * Demo accounts log in via POST /api/v1/dev/login (no SMS) outside production.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $mall = Mall::query()->where('subdomain', 'almas')->first();
        if ($mall === null) {
            return; // base data not seeded yet
        }
        $mallId = (int) $mall->id;

        /** @var PermissionRegistrar $registrar */
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        // One demo account per role (phones are the dev-login handles).
        $customer = $this->user('09120000001', 'customer', 'customer');
        $this->user('09120000002', 'staff', 'mall-manager');
        $owner = $this->user('09120000003', 'owner', 'business-owner');

        StoreWhitelist::query()->withoutGlobalScopes()
            ->firstOrCreate(['mall_id' => $mallId, 'phone' => (string) $owner->phone]);

        // A complete store owned by the demo owner.
        $boutique = $this->store($mallId, 'بوتیک رویا', 'A-12', $this->categoryId('clothing'), (int) $owner->id, 15);
        $this->products($mallId, (int) $boutique->id, [
            ['مانتو مجلسی', 2_400_000],
            ['شال نخی', 480_000],
            ['کیف چرمِ دست‌دوز', 3_200_000],
            ['کفش روزمره', 1_850_000],
        ]);

        // A few more stores so the customer's home looks alive.
        $resto = $this->store($mallId, 'رستوران سنتیِ شهر', 'B-04', $this->categoryId('food'), null, 10);
        $this->products($mallId, (int) $resto->id, [
            ['چلوکباب سلطانی', 850_000],
            ['دیزیِ سنگی', 520_000],
        ]);

        $tech = $this->store($mallId, 'دیجیتال پارس', 'C-21', $this->categoryId('digital'), null, 5);
        $this->products($mallId, (int) $tech->id, [
            ['هندزفری بلوتوث', 1_200_000],
            ['پاوربانک ۲۰٬۰۰۰', 950_000],
        ]);

        // Give the demo customer a balance (-> silver tier) if they have none.
        /** @var PointsService $points */
        $points = app(PointsService::class);
        if ($points->balance($customer, $mallId) <= 0) {
            $points->accrue($customer, $mallId, 180);
        }
    }

    private function user(string $phone, string $type, string $role): User
    {
        $user = User::query()->firstOrCreate(
            ['phone' => $phone],
            ['type' => $type, 'status' => 'active'],
        );
        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }

    private function categoryId(string $slug): ?int
    {
        $id = Category::query()->withoutGlobalScopes()->where('slug', $slug)->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function store(int $mallId, string $name, string $plaque, ?int $categoryId, ?int $ownerId, int $discount): Store
    {
        return Store::query()->withoutGlobalScopes()->firstOrCreate(
            ['mall_id' => $mallId, 'name' => $name],
            [
                'category_id' => $categoryId,
                'owner_id' => $ownerId,
                'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
                'plaque' => $plaque,
                'member_discount_pct' => $discount,
                'status' => 'active',
            ],
        );
    }

    /**
     * @param  array<int, array{0: string, 1: int}>  $items
     */
    private function products(int $mallId, int $storeId, array $items): void
    {
        foreach ($items as [$name, $price]) {
            Product::query()->withoutGlobalScopes()->firstOrCreate(
                ['mall_id' => $mallId, 'store_id' => $storeId, 'name' => $name],
                ['price' => $price, 'is_active' => true],
            );
        }
    }
}
