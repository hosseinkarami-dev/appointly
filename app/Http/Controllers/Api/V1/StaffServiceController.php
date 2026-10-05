<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffServiceController extends Controller
{
    public function store(Request $request, int $staff, int $service): JsonResponse
    {
        $tenant = $this->tenant($request);
        $staffProfile = $tenant->staff()->whereKey($staff)->firstOrFail();
        $serviceModel = $tenant->services()->whereKey($service)->firstOrFail();

        $staffProfile->services()->syncWithoutDetaching([
            $serviceModel->id => ['tenant_id' => $tenant->id, 'is_active' => true],
        ]);

        return response()->json(['data' => ['staffId' => $staffProfile->id, 'serviceId' => $serviceModel->id, 'isActive' => true]], 201);
    }

    public function destroy(Request $request, int $staff, int $service): JsonResponse
    {
        $tenant = $this->tenant($request);
        $staffProfile = $tenant->staff()->whereKey($staff)->firstOrFail();
        $serviceModel = $tenant->services()->whereKey($service)->firstOrFail();
        $staffProfile->services()->updateExistingPivot($serviceModel->id, ['is_active' => false]);

        return response()->json(['data' => ['staffId' => $staffProfile->id, 'serviceId' => $serviceModel->id, 'isActive' => false]]);
    }

    private function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant');
    }
}
