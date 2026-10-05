<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Http\Resources\Api\V1\StaffResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class PublicBusinessController extends Controller
{
    public function show(Tenant $tenant): JsonResponse
    {
        abort_unless($tenant->is_active, 404);

        return response()->json([
            'data' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'timezone' => $tenant->timezone,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
            ],
        ]);
    }

    public function services(Tenant $tenant): mixed
    {
        abort_unless($tenant->is_active, 404);

        return ServiceResource::collection(
            $tenant->services()->where('is_active', true)->orderBy('name')->get()
        );
    }

    public function staff(Tenant $tenant): mixed
    {
        abort_unless($tenant->is_active, 404);

        return StaffResource::collection(
            $tenant->staff()->where('is_active', true)->orderBy('display_name')->get()
        );
    }
}
