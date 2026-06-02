<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Modules\Redemption\Models\Redemption;
use Illuminate\Support\Carbon;

/**
 * Computes the KPIs defined in SmartLocal_KPI_NorthStar_Spec.md.
 * All metrics are mall-scoped over a rolling 30-day window by default.
 */
final class AnalyticsService
{
    public function __construct(private readonly int $windowDays = 30) {}

    private function since(): Carbon
    {
        return now()->subDays($this->windowDays);
    }

    /** North Star: distinct customers with >=1 valid redemption in the window. */
    public function monthlyRedeemingCustomers(int $mallId): int
    {
        return Redemption::query()->withoutGlobalScopes()
            ->where('mall_id', $mallId)
            ->where('created_at', '>=', $this->since())
            ->distinct('user_id')
            ->count('user_id');
    }

    /** Redeemed GMV (gross, pre-discount) in the window. */
    public function redeemedGmv(int $mallId): int
    {
        return (int) Redemption::query()->withoutGlobalScopes()
            ->where('mall_id', $mallId)
            ->where('created_at', '>=', $this->since())
            ->sum('amount');
    }

    /** Active Redeeming Stores — MVP floor: stores with >=4 redemptions in the window. */
    public function activeRedeemingStores(int $mallId, int $floor = 4): int
    {
        return Redemption::query()->withoutGlobalScopes()
            ->where('mall_id', $mallId)
            ->where('created_at', '>=', $this->since())
            ->selectRaw('store_id, COUNT(*) as c')
            ->groupBy('store_id')
            ->havingRaw('COUNT(*) >= ?', [$floor])
            ->get()
            ->count();
    }

    /** Repeat-redemption rate: % of redeemers with >=2 redemptions in the window. */
    public function repeatRedemptionRate(int $mallId): float
    {
        $counts = Redemption::query()->withoutGlobalScopes()
            ->where('mall_id', $mallId)
            ->where('created_at', '>=', $this->since())
            ->selectRaw('user_id, COUNT(*) as c')
            ->groupBy('user_id')
            ->pluck('c');

        if ($counts->isEmpty()) {
            return 0.0;
        }

        $repeat = $counts->filter(fn ($c): bool => (int) $c >= 2)->count();

        return round($repeat / $counts->count() * 100, 1);
    }

    /**
     * @return array<string, int|float>
     */
    public function summary(int $mallId): array
    {
        return [
            'monthly_redeeming_customers' => $this->monthlyRedeemingCustomers($mallId),
            'redeemed_gmv' => $this->redeemedGmv($mallId),
            'active_redeeming_stores' => $this->activeRedeemingStores($mallId),
            'repeat_redemption_rate' => $this->repeatRedemptionRate($mallId),
        ];
    }
}
