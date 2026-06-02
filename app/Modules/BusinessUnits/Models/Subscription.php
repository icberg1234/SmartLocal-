<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mall_id
 * @property string $plan
 * @property int $store_quota
 * @property string $status
 */
class Subscription extends Model
{
    use BelongsToMall;

    protected $fillable = ['mall_id', 'plan', 'store_quota', 'starts_at', 'ends_at', 'status'];

    protected function casts(): array
    {
        return [
            'store_quota' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
