<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Http\Controllers;

use App\Models\User;
use App\Modules\Core\Support\CurrentMall;
use App\Modules\Redemption\Services\RedeemTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class RedeemTokenController
{
    public function __construct(
        private readonly RedeemTokenService $tokens,
        private readonly CurrentMall $currentMall,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $mallId = $this->currentMall->id();
        if ($mallId === null) {
            throw new HttpException(422, 'پاساژ مشخص نیست (X-Mall-Id).');
        }

        return response()->json([
            'token' => $this->tokens->issue($user, $mallId),
            'expires_in' => (int) config('smartlocal.redeem_token_ttl', 60),
        ]);
    }
}
