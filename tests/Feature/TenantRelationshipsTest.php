<?php

namespace Tests\Feature;

use App\Domain\Identity\Enums\MembershipRole;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TenantRelationshipsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_tenant_membership_and_staff_service_relationships_are_tenant_scoped(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'name' => 'North Clinic',
            'slug' => 'north-clinic',
            'timezone' => 'Asia/Tehran',
        ]);
        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Consultation',
            'duration_minutes' => 30,
        ]);
        $staff = StaffProfile::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'display_name' => 'Dr. Ada',
        ]);

        $tenant->users()->attach($user, [
            'role' => MembershipRole::Owner->value,
            'is_active' => true,
        ]);
        $staff->services()->attach($service, [
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $tenant->refresh();
        $staff->refresh();

        self::assertTrue($tenant->users->contains($user));
        self::assertSame(MembershipRole::Owner->value, $tenant->users->first()->pivot->role);
        self::assertTrue($staff->services->contains($service));
        self::assertSame(30, $service->fresh()->duration_minutes);
    }
}
