<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWorkingHourRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkingHourController extends Controller
{
    public function index(Request $request, int $staff): JsonResponse
    {
        $staffProfile = $this->tenant($request)->staff()->whereKey($staff)->firstOrFail();

        return response()->json([
            'data' => $staffProfile->workingHours()->orderBy('weekday')->orderBy('start_local_time')->get()->map(fn ($hour): array => $this->payload($hour))->values(),
        ]);
    }

    public function store(StoreWorkingHourRequest $request, int $staff): JsonResponse
    {
        $tenant = $this->tenant($request);
        $staffProfile = $tenant->staff()->whereKey($staff)->firstOrFail();
        $validated = $request->validated();

        $workingHour = $staffProfile->workingHours()->updateOrCreate(
            ['weekday' => $validated['weekday']],
            [
                'tenant_id' => $tenant->id,
                'start_local_time' => $validated['startLocalTime'],
                'end_local_time' => $validated['endLocalTime'],
                'is_active' => true,
            ]
        );

        return response()->json(['data' => $this->payload($workingHour)], 201);
    }

    public function destroy(Request $request, int $staff, int $workingHour): JsonResponse
    {
        $staffProfile = $this->tenant($request)->staff()->whereKey($staff)->firstOrFail();
        $staffProfile->workingHours()->whereKey($workingHour)->firstOrFail()->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** @return array<string, mixed> */
    private function payload(object $workingHour): array
    {
        return [
            'id' => $workingHour->id,
            'weekday' => $workingHour->weekday,
            'startLocalTime' => $workingHour->start_local_time,
            'endLocalTime' => $workingHour->end_local_time,
            'isActive' => (bool) $workingHour->is_active,
        ];
    }

    private function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant');
    }
}
