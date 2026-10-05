<?php

namespace App\Application\Service;

use App\Models\Service;
use App\Models\Tenant;

final class UpdateServiceAction
{
    /** @param array{name: string, description: ?string, durationMinutes: int, bufferMinutes: int} $data */
    public function handle(Tenant $tenant, int $serviceId, array $data): Service
    {
        $service = $tenant->services()->whereKey($serviceId)->firstOrFail();
        $service->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'duration_minutes' => $data['durationMinutes'],
            'buffer_minutes' => $data['bufferMinutes'],
        ]);

        return $service->refresh();
    }
}
