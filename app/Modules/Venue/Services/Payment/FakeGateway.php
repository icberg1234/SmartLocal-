<?php

declare(strict_types=1);

namespace App\Modules\Venue\Services\Payment;

/**
 * Local/test gateway. The callback's `status` param drives success/failure.
 */
final class FakeGateway implements PaymentGateway
{
    public function start(int $amount, string $ref): string
    {
        return "https://fake.pay/checkout/{$ref}?amount={$amount}";
    }

    public function verify(string $ref): bool
    {
        return true;
    }
}
