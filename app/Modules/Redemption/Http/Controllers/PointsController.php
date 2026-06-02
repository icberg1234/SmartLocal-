<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Http\Controllers;

use App\Models\User;
use App\Modules\Core\Support\CurrentMall;
use App\Modules\Redemption\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class PointsController
{
    public function __construct(
        private readonly PointsService $points,
        private readonly CurrentMall $currentMall,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $mallId = $this->currentMall->id() ?? throw new HttpException(422, 'پاساژ مشخص نیست.');

        $balance = $this->points->balance($user, $mallId);

        return response()->json([
            'balance' => $balance,
            'tier' => $this->points->tier($balance),
        ]);
    }
}
