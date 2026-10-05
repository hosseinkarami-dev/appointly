<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Enums\MembershipRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        [$user, $tenant] = DB::transaction(function () use ($validated): array {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $tenant = Tenant::create([
                'name' => $validated['businessName'],
                'slug' => $this->uniqueTenantSlug($validated['businessName']),
                'timezone' => $validated['timezone'] ?? 'UTC',
            ]);

            $tenant->users()->attach($user, [
                'role' => MembershipRole::Owner->value,
                'is_active' => true,
            ]);

            return [$user, $tenant];
        });

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'data' => $this->userPayload($user, $tenant),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'error' => [
                    'code' => 'invalid_credentials',
                    'message' => 'The provided credentials are incorrect.',
                ],
            ], 401);
        }

        $tenant = $user->tenants()->wherePivot('is_active', true)->first();

        return response()->json([
            'data' => $this->userPayload($user, $tenant),
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    public function me(): JsonResponse
    {
        $user = request()->user()->load('tenants');

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenants' => $user->tenants->map(fn (Tenant $tenant): array => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'role' => $tenant->pivot->role,
                ])->values(),
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        request()->user()->currentAccessToken()?->delete();

        return response()->json(['data' => ['loggedOut' => true]]);
    }

    private function uniqueTenantSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'business';
        $slug = $baseSlug;
        $suffix = 2;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /** @return array<string, mixed> */
    private function userPayload(User $user, ?Tenant $tenant): array
    {
        $membership = $tenant === null
            ? null
            : $user->tenants()->whereKey($tenant->id)->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'tenant' => $tenant === null ? null : [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'role' => $membership?->pivot?->role,
            ],
        ];
    }
}
