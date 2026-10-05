<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDayOffRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DayOffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $daysOff = $this->tenant($request)->daysOff()->with('staff')->orderBy('local_date')->get();

        return response()->json(['data' => $daysOff->map(fn ($dayOff): array => $this->payload($dayOff))->values()]);
    }

    public function store(StoreDayOffRequest $request): JsonResponse
    {
        $tenant = $this->tenant($request);
        $validated = $request->validated();
        $staffId = $validated['staffId'] ?? null;

        if ($staffId !== null) {
            $tenant->staff()->findOrFail($staffId);
        }

        $dayOff = $tenant->daysOff()->updateOrCreate(
            ['local_date' => $validated['date'], 'staff_profile_id' => $staffId],
            ['reason' => $validated['reason'] ?? null]
        );

        return response()->json(['data' => $this->payload($dayOff->load('staff'))], 201);
    }

    public function destroy(Request $request, int $dayOff): JsonResponse
    {
        $model = $this->tenant($request)->daysOff()->whereKey($dayOff)->firstOrFail();
        $model->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** @return array<string, mixed> */
    private function payload(object $dayOff): array
    {
        return [
            'id' => $dayOff->id,
            'date' => $dayOff->local_date->toDateString(),
            'reason' => $dayOff->reason,
            'staffId' => $dayOff->staff_profile_id,
            'staffName' => $dayOff->staff?->display_name,
        ];
    }

    private function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant');
    }
}
