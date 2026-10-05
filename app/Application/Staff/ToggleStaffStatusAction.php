<?php

namespace App\Application\Staff;

use App\Models\StaffProfile;
use App\Models\Tenant;

final class ToggleStaffStatusAction
{
    public function handle(Tenant $tenant, int $staffId): StaffProfile
    {
        $staff = $tenant->staff()->whereKey($staffId)->firstOrFail();
        $staff->update(['is_active' => ! $staff->is_active]);

        return $staff->refresh();
    }
}
