<?php

declare(strict_types=1);

namespace App\Modules\Venue\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mall_id
 * @property int $user_id
 * @property int $lot_id
 * @property string $qr
 * @property string $status
 * @property bool $lottery_win
 */
class ParkingReservation extends Model
{
    use BelongsToMall;

    protected $fillable = ['mall_id', 'user_id', 'lot_id', 'qr', 'status', 'lottery_win'];

    protected function casts(): array
    {
        return ['lottery_win' => 'boolean'];
    }
}
