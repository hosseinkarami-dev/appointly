<?php

namespace App\Application\Service;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

final class DeleteServiceAction
{
    public function handle(Tenant $tenant, int $serviceId): bool
    {
        return DB::transaction(function () use ($tenant, $serviceId): bool {
            $service = $tenant->services()->whereKey($serviceId)->lockForUpdate()->firstOrFail();

            if ($tenant->appointments()->where('service_id', $service->id)->exists()) {
                return false;
            }

            $service->delete();

            return true;
        });
    }
}
