<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 */
class Brand extends Model
{
    protected $fillable = ['name'];
}
