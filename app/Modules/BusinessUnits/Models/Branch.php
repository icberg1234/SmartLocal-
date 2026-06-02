<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $brand_id
 * @property int $mall_id
 */
class Branch extends Model
{
    protected $fillable = ['brand_id', 'mall_id', 'name'];
}
