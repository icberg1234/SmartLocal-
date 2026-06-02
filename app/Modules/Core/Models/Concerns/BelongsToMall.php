<?php

declare(strict_types=1);

namespace App\Modules\Core\Models\Concerns;

use App\Modules\Core\Models\Mall;
use App\Modules\Core\Models\Scopes\MallScope;
use App\Modules\Core\Support\CurrentMall;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Apply to any tenant-owned model. Adds the global MallScope and
 * auto-fills mall_id from the resolved tenant on create.
 */
trait BelongsToMall
{
    public static function bootBelongsToMall(): void
    {
        static::addGlobalScope(new MallScope());

        static::creating(function ($model): void {
            if (empty($model->mall_id) && ($mallId = app(CurrentMall::class)->id()) !== null) {
                $model->mall_id = $mallId;
            }
        });
    }

    public function mall(): BelongsTo
    {
        return $this->belongsTo(Mall::class);
    }
}
