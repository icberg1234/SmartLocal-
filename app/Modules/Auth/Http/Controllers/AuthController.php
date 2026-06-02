<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Requests\RequestOtpRequest;
use App\Modules\Auth\Http\Requests\VerifyOtpRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuthController
{
    public function __construct(private readonly OtpService $otp) {}

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $this->otp->request($request->validated()['phone']);

        return response()->json(['message' => 'کد ارسال شد.']);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResource
    {
        $data = $request->validated();
        $result = $this->otp->verify($data['phone'], $data['code']);

        return (new UserResource($result['user']))->additional([
            'token' => $result['token'],
            'is_new' => $result['is_new'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $token = $user->currentAccessToken();

        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'خروج انجام شد.']);
    }
}
