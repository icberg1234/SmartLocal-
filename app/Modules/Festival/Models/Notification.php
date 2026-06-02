<?php

declare(strict_types=1);

namespace App\Modules\Festival\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mall_id
 * @property int $user_id
 * @property string $channel
 * @property int|null $festival_id
 * @property string $status
 */
class Notification extends Model
{
    use BelongsToMall;

    public const UPDATED_AT = null;

    protected $table = 'app_notifications';

    protected $fillable = ['mall_id', 'user_id', 'channel', 'festival_id', 'status'];
}
