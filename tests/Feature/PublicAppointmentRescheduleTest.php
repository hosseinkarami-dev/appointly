<?php

namespace Tests\Feature;

use App\Application\Appointment\ReschedulePublicAppointmentAction;
use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublicAppointmentRescheduleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_public_appointment_can_be_rescheduled_to_an_available_time(): void
    {
        $date = CarbonImmutable::now('UTC')->addDays(3)->startOfDay();
        $tenant = Tenant::create(['name' => 'North Clinic', 'slug' => 'north-clinic', 'timezone' => 'UTC']);
        $service = Service::create(['tenant_id' => $tenant->id, 'name' => 'Consultation', 'duration_minutes' => 30]);
        $staff = StaffProfile::create(['tenant_id' => $tenant->id, 'display_name' => 'Dr. Ada']);
        $staff->services()->attach($service, ['tenant_id' => $tenant->id, 'is_active' => true]);
        WorkingHour::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'weekday' => $date->isoWeekday(),
            'start_local_time' => '09:00',
            'end_local_time' => '12:00',
        ]);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'name' => 'Ada Lovelace']);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'public_token' => str_repeat('b', 64),
            'customer_id' => $customer->id,
            'staff_profile_id' => $staff->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'start_at' => $date->setTime(9, 0),
            'end_at' => $date->setTime(9, 30),
            'local_date' => $date->toDateString(),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->get('/booking/'.$appointment->public_token.'/reschedule')->assertOk();

        $updated = app(ReschedulePublicAppointmentAction::class)->handle(
            $appointment->public_token,
            $date->setTime(10, 0)->toIso8601String(),
        );

        self::assertSame('10:00', $updated->start_at->format('H:i'));
        self::assertSame(AppointmentStatus::Confirmed, $updated->status);
    }
}
