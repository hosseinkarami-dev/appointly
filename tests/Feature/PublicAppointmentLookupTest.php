<?php

namespace Tests\Feature;

use App\Application\Appointment\CancelPublicAppointmentAction;
use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublicAppointmentLookupTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_public_appointment_can_be_viewed_and_cancelled_with_its_token(): void
    {
        $tenant = Tenant::create([
            'name' => 'North Clinic',
            'slug' => 'north-clinic',
            'timezone' => 'UTC',
            'cancellation_cutoff_hours' => 0,
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
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
        ]);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'public_token' => str_repeat('a', 64),
            'customer_id' => $customer->id,
            'staff_profile_id' => $staff->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'start_at' => now()->addDay()->setTime(10, 0),
            'end_at' => now()->addDay()->setTime(10, 30),
            'local_date' => now()->addDay()->toDateString(),
            'status' => AppointmentStatus::Pending,
        ]);

        $this->get('/booking/'.$appointment->public_token)
            ->assertOk()
            ->assertSee('Consultation');

        $cancelled = app(CancelPublicAppointmentAction::class)->handle(
            $appointment->public_token,
            'Plans changed',
        );

        self::assertSame(AppointmentStatus::Cancelled, $cancelled->status);
        self::assertSame('Plans changed', $cancelled->cancellation_reason);
    }
}
