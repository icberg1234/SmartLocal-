<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Models;

use App\Models\User;
use App\Modules\Core\Models\Concerns\BelongsToMall;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $mall_id
 * @property int $category_id
 * @property int|null $owner_id
 * @property string $name
 * @property string $slug
 * @property string|null $plaque
 * @property int $member_discount_pct
 * @property string $status
 */
class Store extends Model
{
    use BelongsToMall;

    protected $fillable = [
        'mall_id', 'branch_id', 'floor_id', 'owner_id', 'category_id',
        'name', 'slug', 'plaque', 'pos_x', 'pos_y', 'member_discount_pct', 'status',
    ];

    protected function casts(): array
    {
        return ['member_discount_pct' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
