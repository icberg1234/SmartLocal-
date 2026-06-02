<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Models;

use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $store_id
 * @property string $name
 * @property int $price
 * @property bool $is_active
 */
class Product extends Model
{
    use BelongsToMall;

    protected $fillable = ['mall_id', 'store_id', 'name', 'price', 'image_path', 'is_active'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
