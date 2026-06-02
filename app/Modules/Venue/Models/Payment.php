<?php

declare(strict_types=1);

namespace App\Modules\Venue\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mall_id
 * @property int $user_id
 * @property int|null $reservation_id
 * @property int $amount
 * @property string $gateway
 * @property string $ref
 * @property string $status
 */
class Payment extends Model
{
    use BelongsToMall;

    protected $fillable = ['mall_id', 'user_id', 'reservation_id', 'amount', 'gateway', 'ref', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }
}
