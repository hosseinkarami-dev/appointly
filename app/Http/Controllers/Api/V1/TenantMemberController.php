<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTenantMemberRequest;
use App\Http\Resources\Api\V1\TenantMemberResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantMemberController extends Controller
{
    public function index(Request $request): mixed
    {
        return TenantMemberResource::collection(
            $this->tenant($request)->users()->orderBy('name')->get()
        );
    }

    public function store(StoreTenantMemberRequest $request): TenantMemberResource|JsonResponse
    {
        $tenant = $this->tenant($request);
        $user = User::query()->where('email', $request->validated('email'))->firstOrFail();

        if ($tenant->users()->whereKey($user->id)->exists()) {
            return response()->json([
                'error' => [
                    'code' => 'member_already_exists',
                    'message' => 'This user is already a member of the workspace.',
                    'requestId' => $request->attributes->get('requestId'),
                ],
            ], 409);
        }

        $tenant->users()->attach($user, [
            'role' => $request->validated('role'),
            'is_active' => true,
        ]);

        return new TenantMemberResource($tenant->users()->whereKey($user->id)->firstOrFail());
    }

    private function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant');
    }
}
