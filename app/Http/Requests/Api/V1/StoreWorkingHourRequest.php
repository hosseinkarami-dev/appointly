<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWorkingHourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weekday' => ['required', 'integer', 'between:1,7'],
            'startLocalTime' => ['required', 'date_format:H:i'],
            'endLocalTime' => ['required', 'date_format:H:i'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['startLocalTime', 'endLocalTime'])) {
                    return;
                }

                if ($this->string('startLocalTime')->toString() >= $this->string('endLocalTime')->toString()) {
                    $validator->errors()->add('endLocalTime', 'The end time must be after the start time.');
                }
            },
        ];
    }
}
