<?php

declare(strict_types=1);

namespace App\Modules\BusinessUnits\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterStoreRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'plaque' => ['nullable', 'string', 'max:30'],
        ];
    }
}
