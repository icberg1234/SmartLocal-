<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Http\Controllers\Admin;

use App\Modules\BusinessUnits\Models\Store;
use App\Modules\BusinessUnits\Models\Subscription;
use Illuminate\Http\JsonResponse;

/**
 * Mall-manager landing view: subscription package + quota usage.
 * Both queries are auto-scoped to the current mall by the global MallScope.
 */
final class OverviewController
{
    public function show(): JsonResponse
    {
        $subscription = Subscription::query()->where('status', 'active')->latest('id')->first();
        $quota = $subscription !== null ? $subscription->store_quota : 0;
        $used = Store::query()->count();

        return response()->json(['data' => [
            'plan' => $subscription?->plan,
            'quota' => $quota,
            'stores_used' => $used,
            'stores_remaining' => max(0, $quota - $used),
            'ends_at' => $subscription?->ends_at,
        ]]);
    }
}
