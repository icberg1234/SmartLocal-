<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Http\Controllers;

use App\Modules\BusinessUnits\Http\Requests\AddWhitelistRequest;
use App\Modules\BusinessUnits\Models\StoreWhitelist;
use Illuminate\Http\JsonResponse;

final class WhitelistController
{
    public function store(AddWhitelistRequest $request): JsonResponse
    {
        // mall_id is auto-filled from the current tenant (BelongsToMall).
        $entry = StoreWhitelist::query()->firstOrCreate([
            'phone' => $request->validated()['phone'],
        ]);

        return response()->json(['data' => ['phone' => $entry->phone]], 201);
    }
}
