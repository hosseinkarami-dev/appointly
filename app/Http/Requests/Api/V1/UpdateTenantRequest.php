<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('tenant');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'alpha_dash', 'max:255', Rule::unique('tenants', 'slug')->ignore($tenant?->id)],
            'timezone' => ['sometimes', 'required', 'timezone:all'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'bookingHorizonDays' => ['sometimes', 'required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
