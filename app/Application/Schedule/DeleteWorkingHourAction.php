<?php

namespace App\Application\Schedule;

use App\Models\Tenant;

final class DeleteWorkingHourAction
{
    public function handle(Tenant $tenant, int $workingHourId): void
    {
        $tenant->workingHours()->whereKey($workingHourId)->delete();
    }
}
