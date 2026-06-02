<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SetMemberDiscountRequest extends FormRequest
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
            'member_discount_pct' => ['required', 'integer', 'between:0,50'],
        ];
    }
}
