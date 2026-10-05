<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'durationMinutes' => ['sometimes', 'integer', 'min:5', 'max:1440'],
            'bufferMinutes' => ['sometimes', 'integer', 'min:0', 'max:240'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}
