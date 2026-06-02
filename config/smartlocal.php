<?php

declare(strict_types=1);

return [
    // 1 loyalty point per N Toman of final (post-discount) amount.
    'point_unit' => (int) env('POINT_UNIT', 100000),

    // Dynamic redeem-token lifetime (seconds).
    'redeem_token_ttl' => (int) env('REDEEM_TOKEN_TTL', 60),

    // Max redemptions per customer per store per day (anti-fraud velocity).
    'redeem_velocity_per_day' => (int) env('REDEEM_VELOCITY_PER_DAY', 3),
];
