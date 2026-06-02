<?php

declare(strict_types=1);

namespace App\Modules\Venue\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mall_id
 * @property string $name
 * @property int $capacity
 * @property int $available
 * @property int $hourly_rate
 */
class ParkingLot extends Model
{
    use BelongsToMall;

    protected $fillable = ['mall_id', 'name', 'capacity', 'available', 'hourly_rate'];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'available' => 'integer',
            'hourly_rate' => 'integer',
        ];
    }
}
