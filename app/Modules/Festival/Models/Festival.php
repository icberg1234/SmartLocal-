<?php

declare(strict_types=1);

namespace App\Modules\Festival\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mall_id
 * @property string $title
 * @property int $discount_pct
 * @property string $status
 */
class Festival extends Model
{
    use BelongsToMall;

    protected $fillable = ['mall_id', 'title', 'discount_pct', 'starts_at', 'ends_at', 'status'];

    protected function casts(): array
    {
        return [
            'discount_pct' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
