<?php

namespace App\Application\Staff;

use App\Models\Tenant;

final class ToggleStaffServiceAssignmentAction
{
    public function handle(Tenant $tenant, int $staffId, int $serviceId): bool
    {
        $staff = $tenant->staff()->findOrFail($staffId);
        $service = $tenant->services()->findOrFail($serviceId);
        $assignment = $staff->services()->whereKey($service->id)->first();

        if ($assignment === null) {
            $staff->services()->attach($service->id, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]);

            return true;
        }

        $isActive = ! $assignment->pivot->is_active;
        $staff->services()->updateExistingPivot($service->id, [
            'tenant_id' => $tenant->id,
            'is_active' => $isActive,
        ]);

        return $isActive;
    }
}
