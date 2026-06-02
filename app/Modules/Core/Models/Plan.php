<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Platform-level master data: the SaaS packages a mall manager subscribes to.
 * Intentionally NOT mall-scoped (global to the whole platform).
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property int $price
 * @property int $store_quota
 * @property int $duration_days
 * @property array<int,string>|null $features
 * @property bool $is_active
 * @property int $sort_order
 */
class Plan extends Model
{
    protected $fillable = [
        'key', 'name', 'price', 'store_quota', 'duration_days', 'features', 'is_active', 'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'store_quota' => 'integer',
            'duration_days' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
