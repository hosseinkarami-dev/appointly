<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Identity\Enums\MembershipRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in(array_column(MembershipRole::cases(), 'value'))],
        ];
    }
}
