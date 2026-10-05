<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'durationMinutes' => $this->duration_minutes,
            'bufferMinutes' => $this->buffer_minutes,
            'isActive' => $this->is_active,
        ];
    }
}
