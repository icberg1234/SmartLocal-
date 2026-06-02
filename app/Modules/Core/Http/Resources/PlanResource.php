<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Core\Models\Plan
 */
final class PlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'price' => $this->price,
            'store_quota' => $this->store_quota,
            'duration_days' => $this->duration_days,
            'features' => $this->features ?? [],
        ];
    }
}
