<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Models\Consent;
use App\Modules\Auth\Models\OtpCode;
use App\Modules\Auth\Services\Sms\SmsSender;
use App\Modules\Core\Support\EventRecorder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class OtpService
{
    private const TTL_SECONDS = 120;

    private const MAX_ATTEMPTS = 3;

    private const LOCK_MINUTES = 10;

    public function __construct(
        private readonly SmsSender $sms,
        private readonly EventRecorder $events,
    ) {}

    public function request(string $phone): void
    {
        OtpCode::query()->where('phone', $phone)->delete();

        $code = (string) random_int(100000, 999999);

        OtpCode::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addSeconds(self::TTL_SECONDS),
        ]);

        $this->sms->send($phone, "کد ورود SmartLocal: {$code}");
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
            if ($otp->attempts >= self::MAX_ATTEMPTS) {
                $otp->locked_until = now()->addMinutes(self::LOCK_MINUTES);
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
