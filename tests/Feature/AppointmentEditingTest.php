<?php

namespace Tests\Feature;

use App\Application\Appointment\UpdateAppointmentAction;
use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Domain\Identity\Enums\MembershipRole;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AppointmentEditingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_workspace_appointment_notes_are_updated_and_audited(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'North Clinic', 'slug' => 'north-clinic', 'timezone' => 'UTC']);
        $tenant->users()->attach($user, ['role' => MembershipRole::Owner->value, 'is_active' => true]);
        $service = Service::create(['tenant_id' => $tenant->id, 'name' => 'Consultation', 'duration_minutes' => 30]);
        $staff = StaffProfile::create(['tenant_id' => $tenant->id, 'display_name' => 'Dr. Ada']);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'name' => 'Ada Lovelace']);
        $startAt = CarbonImmutable::now('UTC')->addDay();
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'staff_profile_id' => $staff->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'duration_minutes' => 30,
            'start_at' => $startAt,
            'end_at' => $startAt->addMinutes(30),
            'local_date' => $startAt->toDateString(),
            'status' => AppointmentStatus::Pending,
        ]);

        $this->actingAs($user);
        $this->get('/workspace/appointments/'.$appointment->id.'/calendar')
            ->assertOk()
            ->assertHeader('content-type', 'text/calendar; charset=UTF-8')
            ->assertSee('BEGIN:VCALENDAR');

        $updated = app(UpdateAppointmentAction::class)->handle($tenant, $appointment->id, ['notes' => 'Bring the intake form.']);

        $this->assertSame('Bring the intake form.', $updated->notes);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $user->id,
            'action' => 'appointment.updated',
            'auditable_id' => $appointment->id,
        ]);
    }
}
