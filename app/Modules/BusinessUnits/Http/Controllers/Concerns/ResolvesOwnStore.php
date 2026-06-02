<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Http\Controllers\Concerns;

use App\Models\User;
use App\Modules\BusinessUnits\Models\Store;
use Illuminate\Http\Request;

trait ResolvesOwnStore
{
    protected function ownStore(Request $request): Store
    {
        /** @var User $user */
        $user = $request->user();

        $store = Store::query()->where('owner_id', $user->id)->first();
        abort_if($store === null, 403, 'شما فروشگاهی ندارید.');

        return $store;
    }
}
