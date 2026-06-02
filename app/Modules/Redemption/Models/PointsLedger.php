<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mall_id
 * @property int $user_id
 * @property int $delta
 * @property string $reason
 */
class PointsLedger extends Model
{
    use BelongsToMall;

    public const UPDATED_AT = null;

    protected $table = 'points_ledger';

    protected $fillable = ['mall_id', 'user_id', 'delta', 'reason', 'redemption_id', 'expires_at'];

    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'expires_at' => 'datetime',
        ];
    }
}
