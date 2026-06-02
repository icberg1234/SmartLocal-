<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $template
 */
class Category extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'template'];
}
