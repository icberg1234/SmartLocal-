<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services\Sms;

interface SmsSender
{
    public function send(string $phone, string $message): void;
}
