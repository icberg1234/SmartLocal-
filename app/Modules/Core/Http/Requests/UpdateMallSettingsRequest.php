<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Manager-facing update of per-mall base data (providers, brand).
 * Authorization is enforced by the route's role:mall-manager middleware.
 */
final class UpdateMallSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'brand' => ['sometimes', 'nullable', 'string', 'max:60'],
            'sms' => ['sometimes', 'array'],
            'sms.driver' => ['sometimes', 'in:fake,kavenegar'],
            'sms.kavenegar_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'payment' => ['sometimes', 'array'],
            'payment.driver' => ['sometimes', 'in:fake,zarinpal'],
            'payment.zarinpal_merchant' => ['sometimes', 'nullable', 'string', 'max:255'],
            'payment.callback_url' => ['sometimes', 'nullable', 'url', 'max:255'],
        ];
    }
}
