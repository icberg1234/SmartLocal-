<?php

declare(strict_types=1);

namespace App\Modules\Venue\Services;

use App\Models\User;
use App\Modules\Core\Support\EventRecorder;
use App\Modules\Venue\Models\ParkingLot;
use App\Modules\Venue\Models\ParkingReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ParkingService
{
    public function __construct(private readonly EventRecorder $events) {}

    public function reserve(User $user, ParkingLot $lot): ParkingReservation
    {
        return DB::transaction(function () use ($user, $lot): ParkingReservation {
            $fresh = ParkingLot::query()->lockForUpdate()->find($lot->id);

            if ($fresh === null || $fresh->available <= 0) {
                throw new HttpException(422, 'ظرفیت پارکینگ پر است.');
            }

            $fresh->decrement('available');

            $win = random_int(1, 100) <= (int) config('smartlocal.parking_lottery_pct', 0);

            $reservation = ParkingReservation::query()->create([
                'mall_id' => $fresh->mall_id,
                'user_id' => $user->id,
                'lot_id' => $fresh->id,
                'qr' => Str::uuid()->toString(),
                'status' => $win ? 'confirmed_free' : 'pending_payment',
                'lottery_win' => $win,
            ]);

            $this->events->record('ParkingReserved', [
                'lot_id' => $fresh->id, 'lottery_win' => $win,
            ], ['actor' => $user, 'subject' => $reservation]);

            return $reservation;
        });
    }
}
