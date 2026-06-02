<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Services;

use App\Models\User;
use App\Modules\Core\Support\EventRecorder;
use App\Modules\Redemption\Models\PointsLedger;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Mall-wide loyalty points: accrue, balance (excluding expired), tier, spend.
 */
final class PointsService
{
    public function __construct(private readonly EventRecorder $events) {}

    public function accrue(User $user, int $mallId, int $points, ?int $redemptionId = null): void
    {
        if ($points <= 0) {
            return;
        }

        PointsLedger::query()->create([
            'mall_id' => $mallId,
            'user_id' => $user->id,
            'delta' => $points,
            'reason' => 'redemption',
            'redemption_id' => $redemptionId,
            'expires_at' => now()->addDays((int) config('smartlocal.points_ttl_days', 180)),
        ]);

        $this->events->record('PointsAccrued', ['delta' => $points, 'mall_id' => $mallId], ['actor' => $user]);
    }

    public function balance(User $user, int $mallId): int
    {
        return (int) PointsLedger::query()->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('mall_id', $mallId)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('delta');
    }

    public function tier(int $balance): string
    {
        if ($balance >= (int) config('smartlocal.tiers.gold', 300)) {
            return 'gold';
        }
        if ($balance >= (int) config('smartlocal.tiers.silver', 100)) {
            return 'silver';
        }

        return 'bronze';
    }

    public function spend(User $user, int $mallId, int $points): void
    {
        if ($points <= 0) {
            throw new HttpException(422, 'مقدار امتیاز نامعتبر است.');
        }
        if ($this->balance($user, $mallId) < $points) {
            throw new HttpException(422, 'امتیاز کافی نیست.');
        }

        PointsLedger::query()->create([
            'mall_id' => $mallId,
            'user_id' => $user->id,
            'delta' => -$points,
            'reason' => 'spend',
        ]);

        $this->events->record('PointsSpent', ['delta' => -$points, 'mall_id' => $mallId], ['actor' => $user]);
    }
}
