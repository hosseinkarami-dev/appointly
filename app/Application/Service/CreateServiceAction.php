<?php

namespace App\Application\Service;

use App\Models\Service;
use App\Models\Tenant;

final class CreateServiceAction
{
    /** @param array{name: string, description: ?string, durationMinutes: int, bufferMinutes: int} $data */
    public function handle(Tenant $tenant, array $data): Service
    {
        return $tenant->services()->create([
            'name' => $data['name'],
            'description' => $data['description'],
            'duration_minutes' => $data['durationMinutes'],
            'buffer_minutes' => $data['bufferMinutes'],
        ]);
    }
}
