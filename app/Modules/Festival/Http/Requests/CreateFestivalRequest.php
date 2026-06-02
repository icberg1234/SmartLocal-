<?php

declare(strict_types=1);

namespace App\Modules\Festival\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFestivalRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:120'],
            'discount_pct' => ['required', 'integer', 'between:0,90'],
            'store_ids' => ['required', 'array', 'min:1'],
            'store_ids.*' => ['integer', 'exists:stores,id'],
        ];
    }
}
