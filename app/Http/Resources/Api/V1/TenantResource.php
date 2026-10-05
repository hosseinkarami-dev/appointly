<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'timezone' => $this->timezone,
            'email' => $this->email,
            'phone' => $this->phone,
            'bookingHorizonDays' => $this->booking_horizon_days,
            'isActive' => $this->is_active,
        ];
    }
}
