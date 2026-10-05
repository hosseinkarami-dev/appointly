<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Domain\Identity\Enums\MembershipRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_public_booking_creates_a_pending_appointment_and_lock(): void
    {
        [$tenant, $service, $staff] = $this->bookingSetup();

        $response = $this->postJson('/api/v1/public/businesses/north-clinic/appointments', [
            'serviceId' => $service->id,
            'staffId' => $staff->id,
            'startAt' => '2026-10-05T05:30:00+00:00',
            'customer' => [
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', AppointmentStatus::Pending->value)
            ->assertJsonPath('data.staffId', $staff->id);
        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'status' => AppointmentStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('staff_day_locks', [
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'local_date' => '2026-10-05',
        ]);
    }

    public function test_booking_rejects_a_second_request_for_the_same_interval(): void
    {
        [, $service, $staff] = $this->bookingSetup();
        $payload = [
            'serviceId' => $service->id,
            'staffId' => $staff->id,
            'startAt' => '2026-10-05T05:30:00+00:00',
            'customer' => [
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
            ],
        ];

        $this->postJson('/api/v1/public/businesses/north-clinic/appointments', $payload)
            ->assertCreated();

        $this->postJson('/api/v1/public/businesses/north-clinic/appointments', $payload)
            ->assertConflict()
            ->assertJsonPath('error.code', 'appointment_conflict');

        self::assertSame(1, Appointment::query()->count());
    }

    public function test_booking_allows_an_adjacent_interval(): void
    {
        [, $service, $staff] = $this->bookingSetup();

        $this->postJson('/api/v1/public/businesses/north-clinic/appointments', [
            'serviceId' => $service->id,
            'staffId' => $staff->id,
            'startAt' => '2026-10-05T05:30:00+00:00',
            'customer' => ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
        ])->assertCreated();

        $this->postJson('/api/v1/public/businesses/north-clinic/appointments', [
            'serviceId' => $service->id,
            'staffId' => $staff->id,
            'startAt' => '2026-10-05T06:00:00+00:00',
            'customer' => ['name' => 'Grace Hopper', 'email' => 'grace@example.com'],
        ])->assertCreated();

        self::assertSame(2, Appointment::query()->count());
    }

    public function test_booking_rejects_an_overlapping_interval(): void
    {
        [, $service, $staff] = $this->bookingSetup();
        $payload = [
            'serviceId' => $service->id,
            'staffId' => $staff->id,
            'startAt' => '2026-10-05T05:30:00+00:00',
            'customer' => ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
        ];

        $this->postJson('/api/v1/public/businesses/north-clinic/appointments', $payload)
            ->assertCreated();

        $this->postJson('/api/v1/public/businesses/north-clinic/appointments', [
            ...$payload,
            'startAt' => '2026-10-05T05:45:00+00:00',
        ])->assertConflict()
            ->assertJsonPath('error.code', 'appointment_conflict');
    }

    public function test_cancelled_time_can_be_booked_again(): void
    {
        [$tenant, $service, $staff, $owner, $token] = $this->bookingSetup(withOwner: true);
        $payload = [
            'serviceId' => $service->id,
            'staffId' => $staff->id,
            'startAt' => '2026-10-05T05:30:00+00:00',
            'customer' => ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
        ];

        $appointment = $this->postJson('/api/v1/public/businesses/north-clinic/appointments', $payload)
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson('/api/v1/appointments/'.$appointment.'/cancel', ['reason' => 'Customer requested'])
            ->assertOk()
            ->assertJsonPath('data.status', AppointmentStatus::Cancelled->value);

        $this->postJson('/api/v1/public/businesses/north-clinic/appointments', $payload)
            ->assertCreated();

        self::assertSame(2, Appointment::query()->count());
        self::assertSame(1, Appointment::query()->where('status', AppointmentStatus::Cancelled)->count());
    }

    public function test_owner_can_confirm_and_complete_an_appointment(): void
    {
        [, $service, $staff, $owner, $token] = $this->bookingSetup(withOwner: true);
        $appointment = $this->createAppointment($service, $staff);

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $owner->tenants()->first()->id)
            ->postJson('/api/v1/appointments/'.$appointment->id.'/confirm')
            ->assertOk()
            ->assertJsonPath('data.status', AppointmentStatus::Confirmed->value);

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $owner->tenants()->first()->id)
            ->postJson('/api/v1/appointments/'.$appointment->id.'/complete')
            ->assertOk()
            ->assertJsonPath('data.status', AppointmentStatus::Completed->value);

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $owner->tenants()->first()->id)
            ->postJson('/api/v1/appointments/'.$appointment->id.'/cancel')
            ->assertConflict()
            ->assertJsonPath('error.code', 'invalid_appointment_transition');
    }

    public function test_assigned_staff_can_manage_their_appointment(): void
    {
        [, $service, $staff, $staffUser, $token] = $this->bookingSetup(withStaff: true);
        $appointment = $this->createAppointment($service, $staff);

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $staffUser->tenants()->first()->id)
            ->postJson('/api/v1/appointments/'.$appointment->id.'/confirm')
            ->assertOk()
            ->assertJsonPath('data.status', AppointmentStatus::Confirmed->value);
    }

    public function test_staff_cannot_manage_another_staff_members_appointment(): void
    {
        [$tenant, $service, $staff, $staffUser, $token] = $this->bookingSetup(withStaff: true);
        $otherStaff = StaffProfile::create([
            'tenant_id' => $tenant->id,
            'display_name' => 'Dr. Grace',
        ]);
        $otherAppointment = $this->createAppointment($service, $otherStaff);

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $staffUser->tenants()->first()->id)
            ->postJson('/api/v1/appointments/'.$otherAppointment->id.'/confirm')
            ->assertForbidden();
    }

    public function test_staff_only_sees_their_own_appointments(): void
    {
        [$tenant, $service, $staff, $staffUser, $token] = $this->bookingSetup(withStaff: true);
        $appointment = $this->createAppointment($service, $staff);
        $otherStaff = StaffProfile::create([
            'tenant_id' => $tenant->id,
            'display_name' => 'Dr. Grace',
        ]);
        $otherAppointment = $this->createAppointment($service, $otherStaff);

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $staffUser->tenants()->first()->id)
            ->getJson('/api/v1/appointments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $appointment->id);

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $staffUser->tenants()->first()->id)
            ->getJson('/api/v1/appointments/'.$otherAppointment->id)
            ->assertNotFound();
    }

    /** @return array{0: Tenant, 1: Service, 2: StaffProfile, 3?: User, 4?: string} */
    private function bookingSetup(bool $withOwner = false, bool $withStaff = false): array
    {
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
            'display_name' => 'Dr. Ada',
        ]);
        $staff->services()->attach($service, [
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        WorkingHour::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'weekday' => 1,
            'start_local_time' => '09:00',
            'end_local_time' => '11:00',
        ]);

        if (! $withOwner) {
            if ($withStaff) {
                $staffUser = User::factory()->create();
                $tenant->users()->attach($staffUser, [
                    'role' => MembershipRole::Staff->value,
                    'is_active' => true,
                ]);
                $staff->update(['user_id' => $staffUser->id]);

                return [$tenant, $service, $staff, $staffUser, $staffUser->createToken('test')->plainTextToken];
            }

            return [$tenant, $service, $staff];
        }

        $owner = User::factory()->create();
        $tenant->users()->attach($owner, [
            'role' => MembershipRole::Owner->value,
            'is_active' => true,
        ]);

        return [$tenant, $service, $staff, $owner, $owner->createToken('test')->plainTextToken];
    }

    private function createAppointment(Service $service, StaffProfile $staff): Appointment
    {
        return Appointment::create([
            'tenant_id' => $staff->tenant_id,
            'customer_id' => $staff->tenant->customers()->create(['name' => 'Ada Lovelace'])->id,
            'staff_profile_id' => $staff->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'duration_minutes' => $service->duration_minutes,
            'buffer_minutes' => $service->buffer_minutes ?? 0,
            'start_at' => '2026-10-05 05:30:00',
            'end_at' => '2026-10-05 06:00:00',
            'local_date' => '2026-10-05',
            'status' => AppointmentStatus::Pending,
        ]);
    }
}
