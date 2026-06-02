<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Http\Controllers;

use App\Modules\BusinessUnits\Http\Controllers\Concerns\ResolvesOwnStore;
use App\Modules\Redemption\Models\Redemption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StoreAnalyticsController
{
    use ResolvesOwnStore;

    public function summary(Request $request): JsonResponse
    {
        $store = $this->ownStore($request);
        $since = now()->subDays(30);

        $base = Redemption::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('created_at', '>=', $since);

        return response()->json([
            'store_id' => $store->id,
            'redemptions' => (clone $base)->count(),
            'redeemed_gmv' => (int) (clone $base)->sum('amount'),
        ]);
    }
}
