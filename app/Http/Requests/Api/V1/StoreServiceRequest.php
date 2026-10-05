<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'durationMinutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'bufferMinutes' => ['nullable', 'integer', 'min:0', 'max:240'],
        ];
    }
}
