<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mall_id
 * @property int $level
 * @property string $name
 * @property string|null $map_svg_path
 */
class Floor extends Model
{
    use BelongsToMall;

    protected $fillable = ['mall_id', 'level', 'name', 'map_svg_path'];

    protected $casts = ['level' => 'integer'];
}
