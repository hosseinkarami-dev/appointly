<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Identity\Enums\MembershipRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_create_and_list_services_and_staff(): void
    {
        [, $tenant, $token] = $this->ownerCredentials();

        $serviceResponse = $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson('/api/v1/services', [
                'name' => 'Consultation',
                'durationMinutes' => 30,
            ]);

        $serviceResponse->assertCreated()
            ->assertJsonPath('data.name', 'Consultation')
            ->assertJsonPath('data.durationMinutes', 30);

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson('/api/v1/staff', [
                'displayName' => 'Dr. Ada',
            ])
            ->assertCreated()
            ->assertJsonPath('data.displayName', 'Dr. Ada');

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson('/api/v1/services')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        self::assertSame('Dr. Ada', $tenant->fresh()->staff()->first()->display_name);
    }

    public function test_staff_members_cannot_manage_services(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'name' => 'North Clinic',
            'slug' => 'north-clinic',
        ]);
        $tenant->users()->attach($user, [
            'role' => MembershipRole::Staff->value,
            'is_active' => true,
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson('/api/v1/services', [
                'name' => 'Consultation',
                'durationMinutes' => 30,
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_select_a_tenant_they_do_not_belong_to(): void
    {
        [$user, $tenant] = $this->ownerCredentials(false);
        $otherTenant = Tenant::create([
            'name' => 'South Clinic',
            'slug' => 'south-clinic',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $otherTenant->id)
            ->getJson('/api/v1/services')
            ->assertForbidden();

        self::assertNotSame($tenant->id, $otherTenant->id);
    }

    public function test_management_errors_include_request_id_and_respect_tenant_boundaries(): void
    {
        [$user, $tenant, $token] = $this->ownerCredentials();
        $otherTenant = Tenant::create([
            'name' => 'South Clinic',
            'slug' => 'south-clinic',
        ]);
        $service = $otherTenant->services()->create([
            'name' => 'Private service',
            'duration_minutes' => 30,
        ]);

        $response = $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->withHeader('X-Request-Id', 'tenant-boundary-check')
            ->patchJson('/api/v1/services/'.$service->id, [
                'name' => 'Should not update',
            ]);

        $response->assertNotFound()
            ->assertHeader('X-Request-Id', 'tenant-boundary-check')
            ->assertJsonPath('error.requestId', 'tenant-boundary-check');
        self::assertSame('Private service', $service->fresh()->name);
    }

    public function test_owner_can_deactivate_a_service_without_deleting_history(): void
    {
        [, $tenant, $token] = $this->ownerCredentials();
        $service = $tenant->services()->create([
            'name' => 'Consultation',
            'duration_minutes' => 30,
        ]);

        $response = $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->deleteJson('/api/v1/services/'.$service->id);

        $response->assertOk()
            ->assertJsonPath('data.deleted', true)
            ->assertJsonPath('data.deactivated', true);
        self::assertFalse($service->fresh()->is_active);
    }

    /** @return array{0: User, 1: Tenant, 2?: string} */
    private function ownerCredentials(bool $withToken = true): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'name' => 'North Clinic',
            'slug' => 'north-clinic',
        ]);
        $tenant->users()->attach($user, [
            'role' => MembershipRole::Owner->value,
            'is_active' => true,
        ]);

        return $withToken
            ? [$user, $tenant, $user->createToken('test')->plainTextToken]
            : [$user, $tenant];
    }
}
