<?php

declare(strict_types=1);

namespace App\Modules\Venue\Http\Controllers;

use App\Models\User;
use App\Modules\Venue\Http\Requests\ReserveParkingRequest;
use App\Modules\Venue\Models\ParkingLot;
use App\Modules\Venue\Services\ParkingService;
use Illuminate\Http\JsonResponse;

final class ParkingController
{
    public function __construct(private readonly ParkingService $service) {}

    public function availability(): JsonResponse
    {
        $lots = ParkingLot::query()->get()->map(fn (ParkingLot $lot): array => [
            'id' => $lot->id,
            'name' => $lot->name,
            'available' => $lot->available,
            'capacity' => $lot->capacity,
            'hourly_rate' => $lot->hourly_rate,
        ])->all();

        return response()->json(['lots' => $lots]);
    }

    public function reserve(ReserveParkingRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $lot = ParkingLot::query()->findOrFail($request->validated()['lot_id']);

        $reservation = $this->service->reserve($user, $lot);

        return response()->json([
            'data' => [
                'id' => $reservation->id,
                'qr' => $reservation->qr,
                'status' => $reservation->status,
                'lottery_win' => $reservation->lottery_win,
                'requires_payment' => $reservation->status === 'pending_payment',
            ],
        ], 201);
    }
}
