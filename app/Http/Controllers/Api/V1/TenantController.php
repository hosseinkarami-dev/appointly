<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateTenantRequest;
use App\Http\Resources\Api\V1\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function show(Request $request): TenantResource
    {
        return new TenantResource($this->tenant($request));
    }

    public function update(UpdateTenantRequest $request): TenantResource
    {
        $tenant = $this->tenant($request);
        $validated = $request->validated();

        $tenant->update([
            'name' => $validated['name'] ?? $tenant->name,
            'slug' => $validated['slug'] ?? $tenant->slug,
            'timezone' => $validated['timezone'] ?? $tenant->timezone,
            'email' => array_key_exists('email', $validated) ? $validated['email'] : $tenant->email,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $tenant->phone,
            'booking_horizon_days' => $validated['bookingHorizonDays'] ?? $tenant->booking_horizon_days,
        ]);

        return new TenantResource($tenant->refresh());
    }

    private function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant');
    }
}
