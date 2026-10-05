<?php

namespace App\Application\Staff;

use App\Models\StaffProfile;
use App\Models\Tenant;

final class CreateStaffAction
{
    /** @param array{name: string, description: ?string} $data */
    public function handle(Tenant $tenant, array $data): StaffProfile
    {
        return $tenant->staff()->create([
            'display_name' => $data['name'],
            'description' => $data['description'],
        ]);
    }
}
