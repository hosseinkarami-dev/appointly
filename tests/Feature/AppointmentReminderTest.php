<?php

namespace Tests\Feature;

use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Notifications\AppointmentLifecycleNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AppointmentReminderTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_confirmed_appointments_receive_one_reminder(): void
    {
        Notification::fake();
        $tenant = Tenant::create(['name' => 'North Clinic', 'slug' => 'north-clinic', 'timezone' => 'UTC']);
        $service = Service::create(['tenant_id' => $tenant->id, 'name' => 'Consultation', 'duration_minutes' => 30]);
        $staff = StaffProfile::create(['tenant_id' => $tenant->id, 'display_name' => 'Dr. Ada']);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
        $startAt = CarbonImmutable::now('UTC')->addHours(24);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'public_token' => str_repeat('r', 64),
            'customer_id' => $customer->id,
            'staff_profile_id' => $staff->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'start_at' => $startAt,
            'end_at' => $startAt->addMinutes(30),
            'local_date' => $startAt->toDateString(),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->artisan('app:send-appointment-reminders')->assertSuccessful();
        $this->artisan('app:send-appointment-reminders')->assertSuccessful();

        Notification::assertSentTo($customer, AppointmentLifecycleNotification::class, 1);
        self::assertNotNull($appointment->refresh()->reminder_sent_at);
    }
}
