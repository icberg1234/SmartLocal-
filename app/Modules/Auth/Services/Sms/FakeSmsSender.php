<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Local/test driver. Records sent messages so tests can assert on them.
 */
final class FakeSmsSender implements SmsSender
{
    /** @var array<int, array{phone:string, message:string}> */
    public static array $sent = [];

    public function send(string $phone, string $message): void
    {
        self::$sent[] = ['phone' => $phone, 'message' => $message];
        Log::info("[FakeSMS] to {$phone}: {$message}");
    }

    public static function flush(): void
    {
        self::$sent = [];
    }
}
