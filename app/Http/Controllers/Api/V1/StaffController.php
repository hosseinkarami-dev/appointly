<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreStaffRequest;
use App\Http\Requests\Api\V1\UpdateStaffRequest;
use App\Http\Resources\Api\V1\StaffResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StaffController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        return StaffResource::collection(
            $tenant->staff()->orderBy('display_name')->orderBy('id')->get()
        );
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $staff = $tenant->staff()->create([
            'display_name' => $validated['displayName'],
            'description' => $validated['description'] ?? null,
        ]);

        return (new StaffResource($staff))->response()->setStatusCode(201);
    }

    public function update(UpdateStaffRequest $request, int $staff): StaffResource|JsonResponse
    {
        $model = $this->tenant($request)->staff()->whereKey($staff)->first();

        if ($model === null) {
            return response()->json(['error' => ['code' => 'staff_not_found', 'message' => 'The requested staff member was not found.', 'requestId' => $request->attributes->get('requestId')]], 404);
        }

        $validated = $request->validated();
        $model->update([
            'display_name' => $validated['displayName'] ?? $model->display_name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $model->description,
            'is_active' => $validated['isActive'] ?? $model->is_active,
        ]);

        return new StaffResource($model->refresh());
    }

    private function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant');
    }
}
