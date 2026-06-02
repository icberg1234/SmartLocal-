<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Services;

use App\Models\User;
use App\Modules\Redemption\Models\CustomerProfile;

final class CrmService
{
    public function recordVisit(User $user, int $mallId, int $spent): CustomerProfile
    {
        $profile = CustomerProfile::query()->withoutGlobalScopes()->firstOrNew(
            ['user_id' => $user->id, 'mall_id' => $mallId],
            ['visit_count' => 0, 'total_spent' => 0],
        );

        $profile->visit_count += 1;
        $profile->total_spent += $spent;
        $profile->last_visit_at = now();
        $profile->save();

        return $profile;
    }

    public function segment(CustomerProfile $profile): string
    {
        if ($profile->total_spent >= 10_000_000 || $profile->visit_count >= 10) {
            return 'vip';
        }
        if ($profile->visit_count >= 3) {
            return 'regular';
        }

        return 'new';
    }
}
