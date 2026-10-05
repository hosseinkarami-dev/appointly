<?php

namespace App\Application\Service;

use App\Models\Service;
use App\Models\Tenant;

final class ToggleServiceStatusAction
{
    public function handle(Tenant $tenant, int $serviceId): Service
    {
        $service = $tenant->services()->whereKey($serviceId)->firstOrFail();
        $service->update(['is_active' => ! $service->is_active]);

        return $service->refresh();
    }
}
