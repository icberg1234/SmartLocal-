<?php

declare(strict_types=1);

namespace App\Modules\Venue\Http\Controllers;

use App\Modules\BusinessUnits\Models\Store;
use App\Modules\Core\Models\Floor;
use App\Modules\Core\Models\Mall;
use Illuminate\Http\JsonResponse;

final class MapController
{
    public function show(Mall $mall): JsonResponse
    {
        $floors = Floor::query()->withoutGlobalScopes()
            ->where('mall_id', $mall->id)
            ->orderBy('level')
            ->get()
            ->map(function (Floor $floor) use ($mall): array {
                $stores = Store::query()->withoutGlobalScopes()
                    ->where('mall_id', $mall->id)
                    ->where('floor_id', $floor->id)
                    ->get()
                    ->map(fn (Store $store): array => [
                        'id' => $store->id,
                        'name' => $store->name,
                        'plaque' => $store->plaque,
                        'x' => $store->pos_x,
                        'y' => $store->pos_y,
                    ])->all();

                return ['level' => $floor->level, 'name' => $floor->name, 'stores' => $stores];
            })->all();

        return response()->json(['mall' => $mall->name, 'floors' => $floors]);
    }
}
