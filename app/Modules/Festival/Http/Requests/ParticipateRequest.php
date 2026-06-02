<?php

declare(strict_types=1);

namespace App\Modules\Festival\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ParticipateRequest extends FormRequest
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
            'decision' => ['required', 'string', 'in:join,decline'],
        ];
    }
}
