<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\BusinessUnits\Models\Store
 */
final class StoreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'plaque' => $this->plaque,
            'category_id' => $this->category_id,
            'member_discount_pct' => $this->member_discount_pct,
            'status' => $this->status,
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
