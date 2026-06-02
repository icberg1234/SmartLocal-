<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Http\Controllers;

use App\Models\User;
use App\Modules\Core\Support\CurrentMall;
use App\Modules\Redemption\Models\CustomerProfile;
use App\Modules\Redemption\Services\CrmService;
use App\Modules\Redemption\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ProfileController
{
    public function __construct(
        private readonly PointsService $points,
        private readonly CrmService $crm,
        private readonly CurrentMall $currentMall,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $mallId = $this->currentMall->id() ?? throw new HttpException(422, 'پاساژ مشخص نیست.');

        $profile = CustomerProfile::query()->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('mall_id', $mallId)
            ->first();

        $balance = $this->points->balance($user, $mallId);

        return response()->json([
            'profile' => [
                'visit_count' => $profile?->visit_count ?? 0,
                'total_spent' => $profile?->total_spent ?? 0,
                'segment' => $profile !== null ? $this->crm->segment($profile) : 'new',
            ],
            'points' => $balance,
            'tier' => $this->points->tier($balance),
        ]);
    }
}
