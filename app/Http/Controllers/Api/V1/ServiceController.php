<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreServiceRequest;
use App\Http\Requests\Api\V1\UpdateServiceRequest;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        return ServiceResource::collection(
            $tenant->services()->orderBy('name')->orderBy('id')->get()
        );
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $service = $tenant->services()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['durationMinutes'],
            'buffer_minutes' => $validated['bufferMinutes'] ?? 0,
        ]);

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    public function update(UpdateServiceRequest $request, int $service): ServiceResource|JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');
        $serviceModel = $tenant->services()->whereKey($service)->first();

        if ($serviceModel === null) {
            return response()->json([
                'error' => [
                    'code' => 'service_not_found',
                    'message' => 'The requested service was not found.',
                    'requestId' => $request->attributes->get('requestId'),
                ],
            ], 404);
        }

        $validated = $request->validated();
        $serviceModel->update([
            'name' => $validated['name'] ?? $serviceModel->name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $serviceModel->description,
            'duration_minutes' => $validated['durationMinutes'] ?? $serviceModel->duration_minutes,
            'buffer_minutes' => $validated['bufferMinutes'] ?? $serviceModel->buffer_minutes,
            'is_active' => $validated['isActive'] ?? $serviceModel->is_active,
        ]);

        return new ServiceResource($serviceModel->refresh());
    }

    public function destroy(Request $request, int $service): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $serviceModel = $tenant->services()->whereKey($service)->first();

        if ($serviceModel === null) {
            return response()->json(['error' => ['code' => 'service_not_found', 'message' => 'The requested service was not found.', 'requestId' => $request->attributes->get('requestId')]], 404);
        }

        $serviceModel->update(['is_active' => false]);

        return response()->json(['data' => ['deleted' => true, 'deactivated' => true]]);
    }
}
