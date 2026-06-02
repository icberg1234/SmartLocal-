<?php

declare(strict_types=1);

namespace App\Modules\Festival\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mall_id
 * @property int $user_id
 * @property int $store_id
 */
class Follow extends Model
{
    use BelongsToMall;

    protected $fillable = ['mall_id', 'user_id', 'store_id'];
}
