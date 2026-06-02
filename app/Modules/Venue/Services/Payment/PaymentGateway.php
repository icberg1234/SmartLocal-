<?php

declare(strict_types=1);

namespace App\Modules\Venue\Services\Payment;

interface PaymentGateway
{
    /** Start a payment; return the redirect URL the customer is sent to. */
    public function start(int $amount, string $ref): string;

    /** Verify a payment by reference after the gateway callback. */
    public function verify(string $ref): bool;
}
