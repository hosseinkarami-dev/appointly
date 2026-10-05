<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Availability\GetAvailableSlotsAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicAvailabilityController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant, GetAvailableSlotsAction $getAvailableSlots): JsonResponse
    {
        abort_unless($tenant->is_active, 404);

        $validated = $request->validate([
            'serviceId' => ['required', 'integer'],
            'staffId' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json([
            'data' => $getAvailableSlots->handle($tenant, $validated['serviceId'], $validated['staffId'], $validated['date']),
        ]);
    }
}
