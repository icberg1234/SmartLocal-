<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Production driver (Kavenegar). Wired when SMS_DRIVER=kavenegar.
 */
final class KavenegarSmsSender implements SmsSender
{
    public function __construct(private readonly string $apiKey) {}

    public function send(string $phone, string $message): void
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('KAVENEGAR_API_KEY is not configured.');
        }

        $response = Http::asForm()->post(
            "https://api.kavenegar.com/v1/{$this->apiKey}/sms/send.json",
            ['receptor' => $phone, 'message' => $message]
        );

        if ($response->failed()) {
            throw new RuntimeException("Kavenegar send failed: {$response->status()}");
        }
    }
}
