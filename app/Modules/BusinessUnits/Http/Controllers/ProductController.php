<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Http\Controllers;

use App\Modules\BusinessUnits\Http\Controllers\Concerns\ResolvesOwnStore;
use App\Modules\BusinessUnits\Http\Requests\ProductRequest;
use App\Modules\BusinessUnits\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController
{
    use ResolvesOwnStore;

    public function index(Request $request): AnonymousResourceCollection
    {
        return ProductResource::collection($this->ownStore($request)->products);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $store = $this->ownStore($request);

        $product = $store->products()->create(
            $request->validated() + ['mall_id' => $store->mall_id]
        );

        return response()->json([
            'data' => (new ProductResource($product))->resolve($request),
        ], 201);
    }
}
