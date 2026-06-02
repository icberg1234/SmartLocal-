<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Http\Controllers;

use App\Modules\BusinessUnits\Http\Controllers\Concerns\ResolvesOwnStore;
use App\Modules\BusinessUnits\Http\Requests\SetMemberDiscountRequest;
use App\Modules\BusinessUnits\Http\Resources\StoreResource;
use Illuminate\Http\JsonResponse;

final class StoreDiscountController
{
    use ResolvesOwnStore;

    public function update(SetMemberDiscountRequest $request): JsonResponse
    {
        $store = $this->ownStore($request);
        $store->update(['member_discount_pct' => $request->integer('member_discount_pct')]);

        return response()->json([
            'data' => (new StoreResource($store->refresh()))->resolve($request),
        ]);
    }
}
