<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Services;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Issues + parses the customer's rotating, signed redeem token (shown as a QR).
 * Encrypted (tamper-proof), short-lived, single-use via embedded nonce.
 */
final class RedeemTokenService
{
    public function issue(User $user, int $mallId): string
    {
        $ttl = (int) config('smartlocal.redeem_token_ttl', 60);

        return Crypt::encryptString((string) json_encode([
            'uid' => $user->id,
            'mall' => $mallId,
            'nonce' => Str::uuid()->toString(),
            'exp' => now()->addSeconds($ttl)->getTimestamp(),
        ]));
    }

    /**
     * @return array{uid:int, mall:int, nonce:string, exp:int}
     */
    public function parse(string $token): array
    {
        try {
            $data = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            throw new HttpException(422, 'کد نامعتبر است.');
        }

        if (! is_array($data) || ! isset($data['uid'], $data['mall'], $data['nonce'], $data['exp'])) {
            throw new HttpException(422, 'کد نامعتبر است.');
        }

        if ((int) $data['exp'] < now()->getTimestamp()) {
            throw new HttpException(422, 'کد منقضی شده است.');
        }

        return [
            'uid' => (int) $data['uid'],
            'mall' => (int) $data['mall'],
            'nonce' => (string) $data['nonce'],
            'exp' => (int) $data['exp'],
        ];
    }
}
