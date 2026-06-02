<?php

declare(strict_types=1);

namespace App\Modules\Venue\Services;

use App\Models\User;
use App\Modules\Core\Support\EventRecorder;
use App\Modules\Venue\Models\ParkingReservation;
use App\Modules\Venue\Models\Payment;
use App\Modules\Venue\Services\Payment\PaymentGateway;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly EventRecorder $events,
    ) {}

    /**
     * @return array{ref:string, url:string}
     */
    public function start(User $user, ParkingReservation $reservation, int $amount): array
    {
        $ref = Str::uuid()->toString();

        Payment::query()->create([
            'mall_id' => $reservation->mall_id,
            'user_id' => $user->id,
            'reservation_id' => $reservation->id,
            'amount' => $amount,
            'gateway' => (string) config('services.payment.driver', 'fake'),
            'ref' => $ref,
            'status' => 'pending',
        ]);

        return ['ref' => $ref, 'url' => $this->gateway->start($amount, $ref)];
    }

    public function complete(string $ref, bool $gatewaySaysOk): bool
    {
        $payment = Payment::query()->withoutGlobalScopes()->where('ref', $ref)->first();
        if ($payment === null) {
            throw new HttpException(404, 'پرداخت یافت نشد.');
        }

        $ok = $gatewaySaysOk && $this->gateway->verify($ref);
        $payment->update(['status' => $ok ? 'paid' : 'failed']);

        if ($ok && $payment->reservation_id !== null) {
            ParkingReservation::query()->withoutGlobalScopes()
                ->where('id', $payment->reservation_id)
                ->update(['status' => 'paid']);
        }

        $this->events->record($ok ? 'PaymentSucceeded' : 'PaymentFailed', ['ref' => $ref]);

        return $ok;
    }
}
