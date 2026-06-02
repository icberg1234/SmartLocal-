<?php

declare(strict_types=1);

namespace App\Modules\Venue\Services\Payment;

use Illuminate\Support\Facades\Http;

/**
 * Production gateway (Zarinpal). Wired when PAYMENT_DRIVER=zarinpal.
 * Minimal happy-path wiring; full IPG handshake hardened later.
 */
final class ZarinpalGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $merchantId,
        private readonly string $callbackUrl,
    ) {}

    public function start(int $amount, string $ref): string
    {
        $response = Http::asJson()->post('https://api.zarinpal.com/pg/v4/payment/request.json', [
            'merchant_id' => $this->merchantId,
            'amount' => $amount,
            'description' => "SmartLocal parking {$ref}",
            'callback_url' => $this->callbackUrl.'?ref='.$ref,
        ]);

        $authority = (string) $response->json('data.authority', '');

        return "https://www.zarinpal.com/pg/StartPay/{$authority}";
    }

    public function verify(string $ref): bool
    {
        // Verification handshake (authority/amount) is completed in the hardening phase.
        return false;
    }
}
