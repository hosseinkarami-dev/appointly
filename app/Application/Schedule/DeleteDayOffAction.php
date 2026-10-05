<?php

namespace App\Application\Schedule;

use App\Models\Tenant;

final class DeleteDayOffAction
{
    public function handle(Tenant $tenant, int $dayOffId): void
    {
        $tenant->daysOff()->whereKey($dayOffId)->delete();
    }
}
