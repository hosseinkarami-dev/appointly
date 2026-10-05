<?php

namespace Tests\Feature\Application;

use App\Application\Schedule\UpsertDayOffAction;
use App\Application\Schedule\UpsertWorkingHourAction;
use App\Application\Service\CreateServiceAction;
use App\Application\Service\DeleteServiceAction;
use App\Application\Staff\CreateStaffAction;
use App\Application\Staff\DeleteStaffAction;
use App\Application\Staff\ToggleStaffServiceAssignmentAction;
use App\Application\Tenant\UpdateTenantProfileAction;
use App\Models\Appointment;
use App\Models\DayOff;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WorkspaceActionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_workspace_actions_manage_setup_within_the_tenant_boundary(): void
    {
        $tenant = Tenant::create([
            'name' => 'North Clinic',
            'slug' => 'north-clinic',
            'timezone' => 'UTC',
        ]);

        $updatedTenant = app(UpdateTenantProfileAction::class)->handle($tenant, [
            'name' => 'North Wellness',
            'slug' => 'north-wellness',
            'timezone' => 'Asia/Tehran',
            'email' => 'hello@example.com',
            'phone' => '+1 555 0100',
            'bookingHorizonDays' => 45,
        ]);
        $service = app(CreateServiceAction::class)->handle($updatedTenant, [
            'name' => 'Consultation',
            'description' => null,
            'durationMinutes' => 30,
            'bufferMinutes' => 10,
        ]);
        $staff = app(CreateStaffAction::class)->handle($updatedTenant, [
            'name' => 'Dr. Ada',
            'description' => 'Primary clinician',
        ]);

        self::assertSame('North Wellness', $updatedTenant->name);
        self::assertSame(45, $updatedTenant->booking_horizon_days);
        self::assertSame('Consultation', $service->name);
        self::assertSame('Dr. Ada', $staff->display_name);

        $assignment = app(ToggleStaffServiceAssignmentAction::class)->handle($updatedTenant, $staff->id, $service->id);
        self::assertTrue($assignment);
        self::assertTrue($staff->fresh()->services()->whereKey($service->id)->exists());

        $workingHour = app(UpsertWorkingHourAction::class)->handle($updatedTenant, $staff->id, 1, '09:00', '17:00');
        $dayOff = app(UpsertDayOffAction::class)->handle($updatedTenant, '2026-10-12', 'Holiday');

        self::assertSame('09:00', substr($workingHour->start_local_time, 0, 5));
        self::assertSame('Holiday', $dayOff->reason);
    }

    public function test_unused_team_member_can_be_deleted_with_dependent_schedule_records(): void
    {
        $tenant = Tenant::create([
            'name' => 'North Clinic',
            'slug' => 'north-clinic-delete',
            'timezone' => 'UTC',
        ]);
        $staff = app(CreateStaffAction::class)->handle($tenant, ['name' => 'Morgan', 'description' => null]);
        WorkingHour::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'weekday' => 1,
            'start_local_time' => '09:00',
            'end_local_time' => '17:00',
        ]);

        self::assertTrue(app(DeleteStaffAction::class)->handle($tenant, $staff->id));
        self::assertDatabaseMissing('staff_profiles', ['id' => $staff->id]);
        self::assertDatabaseMissing('working_hours', ['staff_profile_id' => $staff->id]);
    }

    public function test_team_member_with_appointment_history_or_days_off_cannot_be_deleted(): void
    {
        $tenant = Tenant::create([
            'name' => 'North Clinic',
            'slug' => 'north-clinic-retain',
            'timezone' => 'UTC',
        ]);
        $service = app(CreateServiceAction::class)->handle($tenant, [
            'name' => 'Consultation',
            'description' => null,
            'durationMinutes' => 30,
            'bufferMinutes' => 0,
        ]);
        $staff = app(CreateStaffAction::class)->handle($tenant, ['name' => 'Morgan', 'description' => null]);
        $customer = $tenant->customers()->create(['name' => 'Taylor']);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'staff_profile_id' => $staff->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'start_at' => CarbonImmutable::parse('2026-10-05 09:00:00', 'UTC'),
            'end_at' => CarbonImmutable::parse('2026-10-05 09:30:00', 'UTC'),
            'local_date' => '2026-10-05',
            'status' => 'confirmed',
        ]);

        self::assertFalse(app(DeleteStaffAction::class)->handle($tenant, $staff->id));
        self::assertDatabaseHas('staff_profiles', ['id' => $staff->id]);

        $appointment = $tenant->appointments()->firstOrFail();
        $appointment->delete();
        DayOff::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'local_date' => '2026-10-12',
            'reason' => 'Leave',
        ]);

        self::assertFalse(app(DeleteStaffAction::class)->handle($tenant, $staff->id));
        self::assertDatabaseHas('staff_profiles', ['id' => $staff->id]);
    }

    public function test_delete_staff_action_is_scoped_to_the_given_tenant(): void
    {
        $owner = Tenant::create(['name' => 'Owner', 'slug' => 'staff-owner', 'timezone' => 'UTC']);
        $other = Tenant::create(['name' => 'Other', 'slug' => 'staff-other', 'timezone' => 'UTC']);
        $staff = StaffProfile::create(['tenant_id' => $other->id, 'display_name' => 'Other tenant']);

        try {
            app(DeleteStaffAction::class)->handle($owner, $staff->id);
            self::fail('A tenant must not delete another tenant’s team member.');
        } catch (ModelNotFoundException) {
            self::assertDatabaseHas('staff_profiles', ['id' => $staff->id]);
        }
    }

    public function test_service_can_be_deleted_without_appointment_history(): void
    {
        $tenant = Tenant::create(['name' => 'Studio', 'slug' => 'service-delete', 'timezone' => 'UTC']);
        $service = app(CreateServiceAction::class)->handle($tenant, [
            'name' => 'Consultation',
            'description' => null,
            'durationMinutes' => 30,
            'bufferMinutes' => 0,
        ]);

        self::assertTrue(app(DeleteServiceAction::class)->handle($tenant, $service->id));
        self::assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_service_with_appointment_history_cannot_be_deleted(): void
    {
        $tenant = Tenant::create(['name' => 'Studio', 'slug' => 'service-history', 'timezone' => 'UTC']);
        $service = app(CreateServiceAction::class)->handle($tenant, [
            'name' => 'Consultation',
            'description' => null,
            'durationMinutes' => 30,
            'bufferMinutes' => 0,
        ]);
        $staff = app(CreateStaffAction::class)->handle($tenant, ['name' => 'Morgan', 'description' => null]);
        $customer = $tenant->customers()->create(['name' => 'Taylor']);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'staff_profile_id' => $staff->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'start_at' => CarbonImmutable::parse('2026-10-05 09:00:00', 'UTC'),
            'end_at' => CarbonImmutable::parse('2026-10-05 09:30:00', 'UTC'),
            'local_date' => '2026-10-05',
            'status' => 'confirmed',
        ]);

        self::assertFalse(app(DeleteServiceAction::class)->handle($tenant, $service->id));
        self::assertDatabaseHas('services', ['id' => $service->id]);
    }
}
