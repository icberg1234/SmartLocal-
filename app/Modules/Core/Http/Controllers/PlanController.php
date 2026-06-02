<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Http\Resources\PlanResource;
use App\Modules\Core\Models\Plan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PlanController
{
    /**
     * Public catalog of active packages — the mall-manager homepage.
     */
    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection(
            Plan::query()->where('is_active', true)->orderBy('sort_order')->get()
        );
    }
}
