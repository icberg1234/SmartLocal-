<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Http\Controllers;

use App\Modules\BusinessUnits\Http\Controllers\Concerns\ResolvesOwnStore;
use App\Modules\Redemption\Http\Requests\StoreRedemptionRequest;
use App\Modules\Redemption\Http\Resources\RedemptionResource;
use App\Modules\Redemption\Services\RedemptionService;
use Illuminate\Http\JsonResponse;

final class RedemptionController
{
    use ResolvesOwnStore;

    public function __construct(private readonly RedemptionService $service) {}

    public function store(StoreRedemptionRequest $request): JsonResponse
    {
        $store = $this->ownStore($request);
        $data = $request->validated();

        $redemption = $this->service->redeem(
            $store,
            (string) $data['redeem_token'],
            (int) $data['amount'],
        );

        return response()->json([
            'data' => (new RedemptionResource($redemption))->resolve($request),
        ], 201);
    }
}
