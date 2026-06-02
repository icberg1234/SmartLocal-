<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mall_id
 * @property string $phone
 */
class StoreWhitelist extends Model
{
    use BelongsToMall;

    protected $table = 'store_whitelist';

    protected $fillable = ['mall_id', 'phone'];
}
