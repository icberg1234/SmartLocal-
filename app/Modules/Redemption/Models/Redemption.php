<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mall_id
 * @property int $user_id
 * @property int $store_id
 * @property string $nonce
 * @property int $amount
 * @property int $discount_pct
 * @property int $discount_amount
 * @property int $final_amount
 * @property int $points_awarded
 */
class Redemption extends Model
{
    use BelongsToMall;

    public const UPDATED_AT = null;

    protected $fillable = [
        'mall_id', 'user_id', 'store_id', 'nonce', 'amount',
        'discount_pct', 'discount_amount', 'final_amount', 'points_awarded',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'discount_pct' => 'integer',
            'discount_amount' => 'integer',
            'final_amount' => 'integer',
            'points_awarded' => 'integer',
        ];
    }
}
