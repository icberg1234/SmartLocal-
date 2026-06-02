<?php

declare(strict_types=1);

namespace App\Modules\Venue\Http\Controllers;

use App\Models\User;
use App\Modules\Venue\Models\ParkingLot;
use App\Modules\Venue\Models\ParkingReservation;
use App\Modules\Venue\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentController
{
    public function __construct(private readonly PaymentService $payments) {}

    public function pay(Request $request, ParkingReservation $reservation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $lot = ParkingLot::query()->findOrFail($reservation->lot_id);

        return response()->json(
            $this->payments->start($user, $reservation, $lot->hourly_rate)
        );
    }

    public function callback(Request $request): JsonResponse
    {
        $ref = (string) $request->query('ref', '');
        $ok = $request->query('status') === 'ok';

        return response()->json(['paid' => $this->payments->complete($ref, $ok)]);
    }
}
