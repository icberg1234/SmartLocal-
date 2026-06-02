<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Redemption\Models\Redemption
 */
final class RedemptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'discount_pct' => $this->discount_pct,
            'discount_amount' => $this->discount_amount,
            'final_amount' => $this->final_amount,
            'points_awarded' => $this->points_awarded,
        ];
    }
}
