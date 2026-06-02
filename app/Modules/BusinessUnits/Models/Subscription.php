<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use App\Modules\Core\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $mall_id
 * @property string $plan
 * @property int|null $plan_id
 * @property int $store_quota
 * @property string $status
 * @property-read \App\Modules\Core\Models\Plan|null $package
 */
class Subscription extends Model
{
    use BelongsToMall;

    protected $fillable = ['mall_id', 'plan', 'plan_id', 'store_quota', 'starts_at', 'ends_at', 'status'];

    protected function casts(): array
    {
        return [
            'store_quota' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
