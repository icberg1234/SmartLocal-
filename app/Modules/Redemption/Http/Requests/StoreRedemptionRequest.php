<?php

declare(strict_types=1);

namespace App\Modules\Redemption\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreRedemptionRequest extends FormRequest
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
            'redeem_token' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:1'],
        ];
    }
}
