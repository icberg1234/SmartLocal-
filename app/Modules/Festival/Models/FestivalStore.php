<?php

declare(strict_types=1);

namespace App\Modules\Festival\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $festival_id
 * @property int $store_id
 * @property string $status
 */
class FestivalStore extends Model
{
    protected $fillable = ['festival_id', 'store_id', 'status'];
}
