<?php

declare(strict_types=1);

namespace App\Modules\Festival\Services;

use App\Models\User;
use App\Modules\Auth\Models\Consent;
use App\Modules\Core\Support\EventRecorder;
use App\Modules\Festival\Models\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Channel-abstracted notification dispatch with consent gating + daily rate cap.
 * MVP uses a fake (logged) push channel; FCM/SMS wiring lands in the hardening phase.
 */
final class NotificationService
{
    public function __construct(private readonly EventRecorder $events) {}

    public function notify(User $user, int $mallId, string $message, ?int $festivalId = null, bool $requireConsent = true): bool
    {
        if ($requireConsent) {
            $consented = Consent::query()
                ->where('user_id', $user->id)
                ->where('scope', 'marketing')
                ->exists();
            if (! $consented) {
                return false;
            }
        }

        $cap = (int) config('smartlocal.notif_daily_cap', 5);
        $sentToday = Notification::query()->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        if ($sentToday >= $cap) {
            return false;
        }

        Notification::query()->create([
            'mall_id' => $mallId,
            'user_id' => $user->id,
            'channel' => 'push',
            'festival_id' => $festivalId,
            'status' => 'sent',
        ]);

        Log::info("[notify] user={$user->id}: {$message}");
        $this->events->record('NotificationSent', ['festival_id' => $festivalId, 'channel' => 'push'], ['actor' => $user]);

        return true;
    }
}
