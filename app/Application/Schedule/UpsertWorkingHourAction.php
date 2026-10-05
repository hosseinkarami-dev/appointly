<?php

namespace App\Application\Schedule;

use App\Models\Tenant;
use App\Models\WorkingHour;

final class UpsertWorkingHourAction
{
    public function handle(Tenant $tenant, int $staffId, int $weekday, string $start, string $end, ?int $workingHourId = null): WorkingHour
    {
        $staff = $tenant->staff()->findOrFail($staffId);

        if ($workingHourId !== null) {
            $workingHour = $tenant->workingHours()->findOrFail($workingHourId);
            $workingHour->update([
                'staff_profile_id' => $staff->id,
                'weekday' => $weekday,
                'start_local_time' => $start,
                'end_local_time' => $end,
                'is_active' => true,
            ]);

            return $workingHour->refresh();
        }

        return $staff->workingHours()->updateOrCreate(
            ['weekday' => $weekday],
            [
                'tenant_id' => $tenant->id,
                'start_local_time' => $start,
                'end_local_time' => $end,
                'is_active' => true,
            ],
        );
    }
}
