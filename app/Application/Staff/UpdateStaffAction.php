<?php

namespace App\Application\Staff;

use App\Models\StaffProfile;
use App\Models\Tenant;

final class UpdateStaffAction
{
    /** @param array{name: string, description: ?string} $data */
    public function handle(Tenant $tenant, int $staffId, array $data): StaffProfile
    {
        $staff = $tenant->staff()->whereKey($staffId)->firstOrFail();
        $staff->update(['display_name' => $data['name'], 'description' => $data['description']]);

        return $staff->refresh();
    }
}
