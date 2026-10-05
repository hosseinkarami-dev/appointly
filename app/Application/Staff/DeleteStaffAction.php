<?php

namespace App\Application\Staff;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

final class DeleteStaffAction
{
    public function handle(Tenant $tenant, int $staffId): bool
    {
        return DB::transaction(function () use ($tenant, $staffId): bool {
            $staff = $tenant->staff()->whereKey($staffId)->lockForUpdate()->firstOrFail();

            if ($staff->appointments()->exists() || $staff->daysOff()->exists()) {
                return false;
            }

            $staff->delete();

            return true;
        });
    }
}
