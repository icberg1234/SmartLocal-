<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Models\Consent;
use App\Modules\Auth\Models\OtpCode;
use App\Modules\Auth\Services\Sms\SmsSender;
use App\Modules\Core\Support\CurrentMall;
use App\Modules\Core\Support\EventRecorder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class OtpService
{
    public function __construct(
        private readonly SmsSender $sms,
        private readonly EventRecorder $events,
        private readonly CurrentMall $currentMall,
    ) {}

    public function request(string $phone): void
    {
        // D1 fix: a locked phone cannot mint a fresh code (closes the lock-reset bypass).
        $latest = OtpCode::query()->where('phone', $phone)->latest('id')->first();
        if ($latest !== null && $latest->isLocked()) {
            throw new HttpException(429, 'به‌دلیل تلاش‌های زیاد موقتاً قفل شده است؛ بعداً تلاش کنید.');
        }

        OtpCode::query()->where('phone', $phone)->delete();

        $code = (string) random_int(100000, 999999);

        OtpCode::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addSeconds((int) config('smartlocal.otp.ttl_seconds', 120)),
        ]);

        $brand = (string) $this->currentMall->setting('brand', config('smartlocal.brand', 'SmartLocal'));
        $template = (string) config('smartlocal.templates.otp_sms', 'کد ورود {brand}: {code}');
        $this->sms->send($phone, strtr($template, ['{brand}' => $brand, '{code}' => $code]));
    }

    /**
     * @return array{user: User, token: string, is_new: bool}
     */
    public function verify(string $phone, string $code): array
    {
        $otp = OtpCode::query()->where('phone', $phone)->latest('id')->first();

        if ($otp === null) {
            $this->fail('کدی برای این شماره صادر نشده است.');
        }
        if ($otp->isLocked()) {
            $this->fail('به دلیل تلاش‌های زیاد، موقتاً قفل شده است.', 'locked');
        }
        if ($otp->isExpired()) {
            $this->fail('کد منقضی شده است.');
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->attempts++;
            if ($otp->attempts >= (int) config('smartlocal.otp.max_attempts', 3)) {
                $otp->locked_until = now()->addMinutes((int) config('smartlocal.otp.lock_minutes', 10));
            }
            $otp->save();
            $this->fail('کد نادرست است.');
        }

        $isNew = ! User::query()->where('phone', $phone)->exists();

        /** @var User $user */
        $user = User::query()->firstOrCreate(
            ['phone' => $phone],
            ['type' => 'customer', 'status' => 'active'],
        );

        if ($isNew) {
            $user->assignRole('customer');
            Consent::query()->firstOrCreate(
                ['user_id' => $user->id, 'scope' => 'data'],
                ['granted_at' => now()],
            );
            $this->events->record('Registered', ['channel' => 'otp'], ['actor' => $user]);
        } else {
            $this->events->record('LoggedIn', ['channel' => 'otp'], ['actor' => $user]);
        }

        $otp->delete();

        return [
            'user' => $user,
            'token' => $user->createToken('otp')->plainTextToken,
            'is_new' => $isNew,
        ];
    }

    private function fail(string $message, string $field = 'code'): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
