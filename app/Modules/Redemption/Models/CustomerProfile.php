<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $mall_id
 * @property int $visit_count
 * @property int $total_spent
 */
class CustomerProfile extends Model
{
    use BelongsToMall;

    protected $fillable = ['user_id', 'mall_id', 'visit_count', 'total_spent', 'last_visit_at'];

    protected function casts(): array
    {
        return [
            'visit_count' => 'integer',
            'total_spent' => 'integer',
            'last_visit_at' => 'datetime',
        ];
    }
}
