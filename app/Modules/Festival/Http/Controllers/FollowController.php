<?php

declare(strict_types=1);

namespace App\Modules\Festival\Http\Controllers;

use App\Models\User;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\Festival\Models\Follow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FollowController
{
    public function store(Request $request, Store $store): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // mall_id is auto-filled from the current tenant (BelongsToMall).
        Follow::query()->firstOrCreate([
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);

        return response()->json(['message' => 'دنبال شد.'], 201);
    }
}
