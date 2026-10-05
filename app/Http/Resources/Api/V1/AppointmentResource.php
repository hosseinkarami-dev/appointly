<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'publicToken' => $this->public_token,
            'status' => $this->status->value,
            'startAt' => $this->start_at->toIso8601String(),
            'endAt' => $this->end_at->toIso8601String(),
            'localDate' => $this->local_date->toDateString(),
            'service' => [
                'id' => $this->service_id,
                'name' => $this->service_name,
                'durationMinutes' => $this->duration_minutes,
            ],
            'staffId' => $this->staff_profile_id,
            'customerId' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn (): array => [
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ]),
            'notes' => $this->notes,
        ];
    }
}
