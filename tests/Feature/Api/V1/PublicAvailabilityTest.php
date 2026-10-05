<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\DayOff;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublicAvailabilityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_public_availability_excludes_an_occupied_interval(): void
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
        Appointment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $this->createCustomer($tenant)->id,
            'staff_profile_id' => $staff->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'duration_minutes' => 30,
            'start_at' => CarbonImmutable::parse('2026-10-05 06:00:00', 'UTC'),
            'end_at' => CarbonImmutable::parse('2026-10-05 06:30:00', 'UTC'),
            'local_date' => '2026-10-05',
            'status' => 'confirmed',
        ]);

        $response = $this->getJson('/api/v1/public/businesses/north-clinic/availability?serviceId='.$service->id.'&staffId='.$staff->id.'&date=2026-10-05');

        $response->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.staffId', $staff->id)
            ->assertJsonPath('data.0.startAt', '2026-10-05T05:30:00+00:00')
            ->assertJsonPath('data.1.startAt', '2026-10-05T06:30:00+00:00');
    }

    public function test_public_availability_skips_nonexistent_spring_forward_times(): void
    {
        $tenant = Tenant::create([
            'name' => 'New York Studio',
            'slug' => 'new-york-studio',
            'timezone' => 'America/New_York',
        ]);
        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Session',
            'duration_minutes' => 30,
        ]);
        $staff = StaffProfile::create([
            'tenant_id' => $tenant->id,
            'display_name' => 'Taylor',
        ]);
        $staff->services()->attach($service, [
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        WorkingHour::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'weekday' => 7,
            'start_local_time' => '01:00',
            'end_local_time' => '05:00',
        ]);

        $response = $this->getJson('/api/v1/public/businesses/new-york-studio/availability?serviceId='.$service->id.'&staffId='.$staff->id.'&date=2026-03-08');

        $response->assertOk()
            ->assertJsonCount(11, 'data')
            ->assertJsonPath('data.0.startAt', '2026-03-08T06:00:00+00:00')
            ->assertJsonPath('data.3.startAt', '2026-03-08T06:45:00+00:00')
            ->assertJsonPath('data.4.startAt', '2026-03-08T07:00:00+00:00')
            ->assertJsonPath('data.10.startAt', '2026-03-08T08:30:00+00:00');
    }

    public function test_public_availability_keeps_fall_back_slots_unique_and_ordered(): void
    {
        $tenant = Tenant::create([
            'name' => 'New York Studio',
            'slug' => 'new-york-fallback',
            'timezone' => 'America/New_York',
        ]);
        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Session',
            'duration_minutes' => 30,
        ]);
        $staff = StaffProfile::create([
            'tenant_id' => $tenant->id,
            'display_name' => 'Taylor',
        ]);
        $staff->services()->attach($service, [
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        WorkingHour::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'weekday' => 7,
            'start_local_time' => '00:00',
            'end_local_time' => '04:00',
        ]);

        $response = $this->getJson('/api/v1/public/businesses/new-york-fallback/availability?serviceId='.$service->id.'&staffId='.$staff->id.'&date=2026-11-01');

        $response->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('data.0.startAt', '2026-11-01T04:00:00+00:00')
            ->assertJsonPath('data.3.startAt', '2026-11-01T04:45:00+00:00')
            ->assertJsonPath('data.4.startAt', '2026-11-01T05:00:00+00:00')
            ->assertJsonPath('data.8.startAt', '2026-11-01T07:00:00+00:00')
            ->assertJsonPath('data.14.startAt', '2026-11-01T08:30:00+00:00');

        $starts = collect($response->json('data'))->pluck('startAt');
        self::assertSame($starts->count(), $starts->unique()->count());
        self::assertSame($starts->sort()->values()->all(), $starts->values()->all());
    }

    public function test_public_availability_respects_staff_and_tenant_day_offs(): void
    {
        $tenant = Tenant::create([
            'name' => 'Day Off Studio',
            'slug' => 'day-off-studio',
            'timezone' => 'UTC',
        ]);
        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Consultation',
            'duration_minutes' => 30,
        ]);
        $staff = StaffProfile::create([
            'tenant_id' => $tenant->id,
            'display_name' => 'Morgan',
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
            'end_local_time' => '10:00',
        ]);

        $availabilityUrl = '/api/v1/public/businesses/day-off-studio/availability?serviceId='.$service->id.'&staffId='.$staff->id.'&date=2026-10-05';

        $this->getJson($availabilityUrl)->assertOk()->assertJsonCount(3, 'data');

        DayOff::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'local_date' => '2026-10-05',
            'reason' => 'Personal leave',
        ]);

        $this->getJson($availabilityUrl)->assertOk()->assertJsonCount(0, 'data');

        $tenant->daysOff()->delete();
        DayOff::create([
            'tenant_id' => $tenant->id,
            'local_date' => '2026-10-05',
            'reason' => 'Studio closed',
        ]);

        $this->getJson($availabilityUrl)->assertOk()->assertJsonCount(0, 'data');
    }

    private function createCustomer(Tenant $tenant): Customer
    {
        return $tenant->customers()->create(['name' => 'Ada Lovelace']);
    }
}
