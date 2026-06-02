<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Services;

use App\Models\User;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\Core\Support\EventRecorder;
use App\Modules\Redemption\Models\Redemption;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class RedemptionService
{
    public function __construct(
        private readonly RedeemTokenService $tokens,
        private readonly PointsService $points,
        private readonly CrmService $crm,
        private readonly EventRecorder $events,
    ) {}

    public function redeem(Store $store, string $token, int $amount): Redemption
    {
        $payload = $this->tokens->parse($token);

        if ($payload['mall'] !== (int) $store->mall_id) {
            throw new HttpException(422, 'این کد متعلق به این پاساژ نیست.');
        }

        $user = User::query()->find($payload['uid']);
        if ($user === null || $user->status !== 'active') {
            throw new HttpException(422, 'مشتری معتبر/فعال نیست.');
        }

        // Anti-replay (nonce is single-use).
        if (Redemption::query()->where('nonce', $payload['nonce'])->exists()) {
            throw new HttpException(409, 'این کد قبلاً استفاده شده است.');
        }

        // Velocity guard.
        $limit = (int) config('smartlocal.redeem_velocity_per_day', 3);
        $today = Redemption::query()
            ->where('user_id', $user->id)
            ->where('store_id', $store->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        if ($today >= $limit) {
            throw new HttpException(429, 'سقف دفعات مجاز امروز پر شده است.');
        }

        $pct = (int) $store->member_discount_pct;
        $discount = intdiv($amount * $pct, 100);
        $final = $amount - $discount;
        $unit = max(1, (int) config('smartlocal.point_unit', 100000));
        $points = intdiv($final, $unit);

        return DB::transaction(function () use ($store, $user, $payload, $amount, $pct, $discount, $final, $points): Redemption {
            $redemption = Redemption::query()->create([
                'mall_id' => $store->mall_id,
                'user_id' => $user->id,
                'store_id' => $store->id,
                'nonce' => $payload['nonce'],
                'amount' => $amount,
                'discount_pct' => $pct,
                'discount_amount' => $discount,
                'final_amount' => $final,
                'points_awarded' => $points,
            ]);

            $this->points->accrue($user, (int) $store->mall_id, $points, (int) $redemption->id);
            $this->crm->recordVisit($user, (int) $store->mall_id, $final);

            $this->events->record('RedemptionCompleted', [
                'store_id' => $store->id,
                'amount' => $amount,
                'discount' => $discount,
                'final' => $final,
                'points' => $points,
            ], ['actor' => $user, 'subject' => $store]);

            return $redemption;
        });
    }
}
