<?php

namespace App\Application\Schedule;

use App\Models\DayOff;
use App\Models\Tenant;

final class UpsertDayOffAction
{
    public function handle(Tenant $tenant, string $localDate, ?string $reason, ?int $dayOffId = null, ?int $staffId = null): DayOff
    {
        if ($staffId !== null) {
            $tenant->staff()->findOrFail($staffId);
        }

        if ($dayOffId !== null) {
            $dayOff = $tenant->daysOff()->findOrFail($dayOffId);
            $dayOff->update(['local_date' => $localDate, 'reason' => $reason, 'staff_profile_id' => $staffId]);

            return $dayOff->refresh();
        }

        return $tenant->daysOff()->updateOrCreate(
            ['local_date' => $localDate, 'staff_profile_id' => $staffId],
            ['reason' => $reason],
        );
    }
}
