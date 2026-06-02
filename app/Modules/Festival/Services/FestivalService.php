<?php

declare(strict_types=1);

namespace App\Modules\Festival\Services;

use App\Models\User;
use App\Modules\BusinessUnits\Models\Store;
use App\Modules\Core\Support\EventRecorder;
use App\Modules\Festival\Models\Festival;
use App\Modules\Festival\Models\FestivalStore;
use App\Modules\Festival\Models\Follow;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class FestivalService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly EventRecorder $events,
    ) {}

    /**
     * @param  array{title:string, discount_pct:int, store_ids:array<int,int>}  $data
     */
    public function create(array $data): Festival
    {
        $festival = Festival::query()->create([
            'title' => $data['title'],
            'discount_pct' => $data['discount_pct'],
            'status' => 'active',
        ]);

        foreach ($data['store_ids'] as $storeId) {
            FestivalStore::query()->create([
                'festival_id' => $festival->id,
                'store_id' => $storeId,
                'status' => 'invited',
            ]);

            $store = Store::query()->find($storeId);
            if ($store !== null && $store->owner_id !== null) {
                $owner = User::query()->find($store->owner_id);
                if ($owner !== null) {
                    // Transactional invite — not gated by marketing consent.
                    $this->notifications->notify($owner, (int) $festival->mall_id, "دعوت به جشنواره {$festival->title}", (int) $festival->id, requireConsent: false);
                }
            }
        }

        $this->events->record('FestivalCreated', ['festival_id' => $festival->id], ['subject' => $festival]);

        return $festival;
    }

    public function participate(Festival $festival, Store $store, string $decision): void
    {
        $link = FestivalStore::query()
            ->where('festival_id', $festival->id)
            ->where('store_id', $store->id)
            ->first();

        if ($link === null) {
            throw new HttpException(404, 'فروشگاه شما در این جشنواره دعوت نشده است.');
        }

        $link->update(['status' => $decision === 'join' ? 'joined' : 'declined']);
    }

    public function activate(Festival $festival): int
    {
        $storeIds = FestivalStore::query()
            ->where('festival_id', $festival->id)
            ->where('status', 'joined')
            ->pluck('store_id')
            ->all();

        $sent = 0;

        if ($storeIds !== []) {
            $userIds = Follow::query()->withoutGlobalScopes()
                ->whereIn('store_id', $storeIds)
                ->pluck('user_id')
                ->unique();

            foreach ($userIds as $userId) {
                $user = User::query()->find($userId);
                if ($user !== null && $this->notifications->notify($user, (int) $festival->mall_id, "جشنواره {$festival->title} شروع شد!", (int) $festival->id)) {
                    $sent++;
                }
            }
        }

        $this->events->record('FestivalActivated', ['festival_id' => $festival->id, 'notified' => $sent], ['subject' => $festival]);

        return $sent;
    }
}
