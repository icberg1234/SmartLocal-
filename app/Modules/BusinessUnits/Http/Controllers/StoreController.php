<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Http\Controllers;

use App\Models\User;
use App\Modules\BusinessUnits\Http\Requests\RegisterStoreRequest;
use App\Modules\BusinessUnits\Http\Resources\StoreResource;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\BusinessUnits\Services\StoreOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class StoreController
{
    public function __construct(private readonly StoreOnboardingService $onboarding) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Store::query()->where('status', 'active')->with('category');

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.(string) $request->string('q').'%');
        }

        return StoreResource::collection($query->paginate(20));
    }

    public function show(Store $store): StoreResource
    {
        return new StoreResource($store->load('category', 'products'));
    }

    public function register(RegisterStoreRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $store = $this->onboarding->register($user, $request->validated());

        return response()->json([
            'data' => (new StoreResource($store))->resolve($request),
        ], 201);
    }
}
