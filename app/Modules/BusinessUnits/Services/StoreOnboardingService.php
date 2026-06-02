<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Services;

use App\Models\User;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\BusinessUnits\Models\StoreWhitelist;
use App\Modules\BusinessUnits\Models\Subscription;
use App\Modules\Core\Support\CurrentMall;
use App\Modules\Core\Support\EventRecorder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class StoreOnboardingService
{
    public function __construct(
        private readonly CurrentMall $currentMall,
        private readonly EventRecorder $events,
    ) {}

    /**
     * @param  array{category_id:int, name:string, plaque?:string|null}  $data
     */
    public function register(User $user, array $data): Store
    {
        if ($this->currentMall->id() === null) {
            $this->fail('پاساژ مشخص نیست (X-Mall-Id).', 'mall');
        }

        // Whitelist + quota are auto-scoped to the current mall via the global MallScope.
        $allowed = StoreWhitelist::query()->where('phone', $user->phone)->exists();
        if (! $allowed) {
            abort(403, 'موبایل شما در فهرست مجاز این پاساژ نیست.');
        }

        $quota = (int) (Subscription::query()->where('status', 'active')->max('store_quota') ?? 0);
        if (Store::query()->count() >= $quota) {
            $this->fail('سهمیه فروشگاه‌های این پکیج پر است.', 'quota');
        }

        $store = Store::query()->create([
            'category_id' => $data['category_id'],
            'owner_id' => $user->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'plaque' => $data['plaque'] ?? null,
            'member_discount_pct' => 0,
        ]);

        if (! $user->hasRole('business-owner')) {
            $user->assignRole('business-owner');
        }

        $this->events->record('StoreOnboarded', ['store_id' => $store->id], [
            'actor' => $user,
            'subject' => $store,
        ]);

        return $store;
    }

    private function fail(string $message, string $field): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
