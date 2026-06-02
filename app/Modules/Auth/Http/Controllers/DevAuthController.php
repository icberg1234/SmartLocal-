<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Demo-only password-less login (no SMS). The route is registered only
 * outside production — see app/Modules/Auth/routes.php. Lets you instantly
 * act as a seeded customer / mall-manager / store-owner.
 */
final class DevAuthController
{
    public function login(Request $request): JsonResponse
    {
        abort_if(app()->isProduction(), 404);

        $phone = (string) $request->input('phone', '');
        $user = User::query()->where('phone', $phone)->first();

        if ($user === null) {
            abort(404, 'کاربر آزمایشی یافت نشد؛ ابتدا «php artisan db:seed» را اجرا کنید.');
        }

        return response()->json([
            'data' => (new UserResource($user))->resolve($request),
            'token' => $user->createToken('dev')->plainTextToken,
            'is_new' => false,
        ]);
    }
}
