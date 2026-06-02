<?php

declare(strict_types=1);

namespace App\Modules\Core\Models\Scopes;

use App\Modules\Core\Support\CurrentMall;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Automatically constrains every query of a tenant-owned model to the
 * currently resolved mall. No current mall => no constraint (e.g. console/seed).
 */
final class MallScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $mallId = app(CurrentMall::class)->id();

        if ($mallId !== null) {
            $builder->where($model->getTable().'.mall_id', $mallId);
        }
    }
}
