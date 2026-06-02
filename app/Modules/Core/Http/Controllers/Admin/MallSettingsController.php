<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Modules\Core\Http\Requests\UpdateMallSettingsRequest;
use App\Modules\Core\Models\Mall;
use App\Modules\Core\Support\CurrentMall;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Mall-manager management of their mall's base-data settings
 * (providers + brand). Secrets are stored encrypted and never echoed back.
 */
final class MallSettingsController
{
    /** @var array<string, string> group => secret field that must not be echoed/blanked */
    private const SECRETS = ['sms' => 'kavenegar_key', 'payment' => 'zarinpal_merchant'];

    public function __construct(private readonly CurrentMall $currentMall) {}

    public function show(): JsonResponse
    {
        $mall = $this->mall();

        return response()->json(['data' => $this->present($mall)]);
    }

    public function update(UpdateMallSettingsRequest $request): JsonResponse
    {
        $mall = $this->mall();
        $data = $request->validated();
        $settings = $mall->settings ?? [];

        if (array_key_exists('brand', $data)) {
            $settings['brand'] = $data['brand'];
        }

        foreach (self::SECRETS as $group => $secret) {
            if (! isset($data[$group]) || ! is_array($data[$group])) {
                continue;
            }
            $existing = isset($settings[$group]) && is_array($settings[$group]) ? $settings[$group] : [];
            foreach ($data[$group] as $key => $value) {
                // Never overwrite a stored secret with a blank value.
                if ($key === $secret && ($value === null || $value === '')) {
                    continue;
                }
                $existing[$key] = $value;
            }
            $settings[$group] = $existing;
        }

        $mall->settings = $settings;
        $mall->save();

        return response()->json(['data' => $this->present($mall)]);
    }

    private function mall(): Mall
    {
        $id = $this->currentMall->id();
        if ($id === null) {
            throw new HttpException(422, 'پاساژ مشخص نیست (X-Mall-Id).');
        }

        return Mall::query()->findOrFail($id);
    }

    /**
     * Public-safe view: real provider values are masked to a boolean "is set".
     *
     * @return array<string, mixed>
     */
    private function present(Mall $mall): array
    {
        $settings = $mall->settings ?? [];
        $sms = isset($settings['sms']) && is_array($settings['sms']) ? $settings['sms'] : [];
        $payment = isset($settings['payment']) && is_array($settings['payment']) ? $settings['payment'] : [];

        return [
            'name' => $mall->name,
            'brand' => $settings['brand'] ?? null,
            'sms' => [
                'driver' => $sms['driver'] ?? 'fake',
                'kavenegar_key_set' => ! empty($sms['kavenegar_key']),
            ],
            'payment' => [
                'driver' => $payment['driver'] ?? 'fake',
                'zarinpal_merchant_set' => ! empty($payment['zarinpal_merchant']),
            ],
        ];
    }
}
