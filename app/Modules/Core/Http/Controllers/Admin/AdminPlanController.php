<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Modules\Core\Http\Requests\PlanRequest;
use App\Modules\Core\Http\Resources\PlanResource;
use App\Modules\Core\Models\Plan;
use Illuminate\Http\JsonResponse;

/**
 * Super-admin CRUD over packages (master data). Read is the public /plans route.
 */
final class AdminPlanController
{
    public function store(PlanRequest $request): JsonResponse
    {
        $plan = Plan::query()->create($request->validated());

        return response()->json([
            'data' => (new PlanResource($plan))->resolve($request),
        ], 201);
    }

    public function update(PlanRequest $request, Plan $plan): PlanResource
    {
        $plan->update($request->validated());

        return new PlanResource($plan->refresh());
    }
}
