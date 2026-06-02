<?php

declare(strict_types=1);

return [
    // 1 loyalty point per N Toman of final (post-discount) amount.
    'point_unit' => (int) env('POINT_UNIT', 100000),

    // Dynamic redeem-token lifetime (seconds).
    'redeem_token_ttl' => (int) env('REDEEM_TOKEN_TTL', 60),

    // Max redemptions per customer per store per day (anti-fraud velocity).
    'redeem_velocity_per_day' => (int) env('REDEEM_VELOCITY_PER_DAY', 3),

    // Loyalty points expiry (days).
    'points_ttl_days' => (int) env('POINTS_TTL_DAYS', 180),

    // Tier thresholds (mall-wide points balance).
    'tiers' => [
        'silver' => 100,
        'gold' => 300,
    ],

    // Max notifications per user per day (anti-fatigue).
    'notif_daily_cap' => (int) env('NOTIF_DAILY_CAP', 5),

    // Free-parking lottery win chance (percent) on reservation.
    'parking_lottery_pct' => (int) env('PARKING_LOTTERY_PCT', 0),

    // OTP (login) parameters — tunable security base data.
    'otp' => [
        'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 120),
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 3),
        'lock_minutes' => (int) env('OTP_LOCK_MINUTES', 10),
    ],

    // Platform brand (per-mall override: malls.settings → 'brand').
    'brand' => env('APP_BRAND', 'SmartLocal'),

    // Message templates. Placeholders in {braces}: {brand}, {code}, {title}.
    'templates' => [
        'otp_sms' => env('TPL_OTP_SMS', 'کد ورود {brand}: {code}'),
        'festival_invite' => 'دعوت به جشنواره {title}',
        'festival_started' => 'جشنواره {title} شروع شد!',
    ],

    // Allowed venue types (reference data).
    'venue_types' => ['mall', 'car-market', 'food-market', 'bazaar'],
];
