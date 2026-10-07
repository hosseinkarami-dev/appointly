<?php

namespace Tests\Feature;

use App\Application\Appointment\CreateAppointmentAction;
use App\Application\Availability\GetAvailableSlotsAction;
use App\Livewire\PublicBookingPage;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Models\WorkingHour;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PublicBookingInteractionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_booking_defaults_to_a_bookable_staff_member(): void
    {
        $this->travelTo('2026-10-04 12:00:00');

        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC']);
        $service = Service::create(['tenant_id' => $tenant->id, 'name' => 'Book', 'duration_minutes' => 30]);
        $unscheduledStaff = StaffProfile::create(['tenant_id' => $tenant->id, 'display_name' => 'gu']);
        $bookableStaff = StaffProfile::create(['tenant_id' => $tenant->id, 'display_name' => 'Hossein']);

        foreach ([$unscheduledStaff, $bookableStaff] as $staff) {
            $staff->services()->attach($service, ['tenant_id' => $tenant->id, 'is_active' => true]);
        }

        WorkingHour::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $bookableStaff->id,
            'weekday' => 1,
            'start_local_time' => '09:00',
            'end_local_time' => '11:00',
        ]);

        $this->get('/business/okala')
            ->assertOk()
            ->assertSee('Hossein')
            ->assertSee('7 open')
            ->assertDontSee('wire:key="staff-choice-'.$unscheduledStaff->id.'"', false);
    }

    public function test_service_selection_hydrates_and_refreshes_without_losing_component_state(): void
    {
        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC']);
        $service = Service::create(['tenant_id' => $tenant->id, 'name' => 'Consultation', 'duration_minutes' => 30]);
        $staff = StaffProfile::create(['tenant_id' => $tenant->id, 'display_name' => 'Dr. Ada']);
        $staff->services()->attach($service, ['tenant_id' => $tenant->id, 'is_active' => true]);

        $this->get('/business/okala')
            ->assertOk()
            ->assertSee('Choose a service')
            ->assertSee('Choose a day')
            ->assertSee('Need another date?')
            ->assertSee('localWindowDays: 30', false);
    }

    public function test_public_booking_page_renders_available_times_for_the_selected_workday(): void
    {
        $this->travelTo('2026-10-04 12:00:00');

        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC']);
        $service = Service::create(['tenant_id' => $tenant->id, 'name' => 'Book', 'duration_minutes' => 30]);
        $staff = StaffProfile::create(['tenant_id' => $tenant->id, 'display_name' => 'Hossein']);
        $staff->services()->attach($service, ['tenant_id' => $tenant->id, 'is_active' => true]);
        WorkingHour::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'weekday' => 1,
            'start_local_time' => '09:00',
            'end_local_time' => '11:00',
        ]);

        $this->get('/business/okala')
            ->assertOk()
            ->assertSee('7 open')
            ->assertSee('09:00')
            ->assertSee('09:30')
            ->assertSee('10:00');
    }

    public function test_day_strip_and_time_list_use_the_same_available_slots(): void
    {
        $this->travelTo('2026-10-04 12:00:00');

        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC', 'is_active' => true]);
        $tenant->refresh();
        $service = Service::create(['tenant_id' => $tenant->id, 'name' => 'Book', 'duration_minutes' => 30]);
        $staff = StaffProfile::create(['tenant_id' => $tenant->id, 'display_name' => 'Hossein']);
        $staff->services()->attach($service, ['tenant_id' => $tenant->id, 'is_active' => true]);
        WorkingHour::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'weekday' => 1,
            'start_local_time' => '09:00',
            'end_local_time' => '11:00',
        ]);
        $component = new PublicBookingPage;
        $component->tenant = $tenant;
        $component->deviceTimezone = 'UTC';
        $component->serviceId = (string) $service->id;
        $component->staffId = (string) $staff->id;
        $component->date = '2026-10-05';
        $component->loadSlots(app(GetAvailableSlotsAction::class));

        $html = $component->render()->with(get_object_vars($component))->with('errors', new ViewErrorBag)->with('bookingHorizonDays', 30)->render();
        $availableSlotCount = collect($component->availableSlots)->where('available', true)->count();

        $this->assertSame(7, $availableSlotCount);
        $this->assertStringContainsString('Oct · 7 open', $html);
    }

    public function test_public_booking_page_shows_occupied_times_as_disabled(): void
    {
        $this->travelTo('2026-10-04 12:00:00');

        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC']);
        $service = Service::create(['tenant_id' => $tenant->id, 'name' => 'Book', 'duration_minutes' => 30]);
        $staff = StaffProfile::create(['tenant_id' => $tenant->id, 'display_name' => 'Hossein']);
        $staff->services()->attach($service, ['tenant_id' => $tenant->id, 'is_active' => true]);
        WorkingHour::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'weekday' => 1,
            'start_local_time' => '09:00',
            'end_local_time' => '11:00',
        ]);
        $customer = $tenant->customers()->create(['name' => 'Existing customer', 'email' => 'existing@example.test']);
        $tenant->appointments()->create([
            'customer_id' => $customer->id,
            'staff_profile_id' => $staff->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'duration_minutes' => 30,
            'start_at' => '2026-10-05 09:00:00',
            'end_at' => '2026-10-05 09:30:00',
            'local_date' => '2026-10-05',
            'status' => 'confirmed',
        ]);

        $this->get('/business/okala')
            ->assertSee('wire:key="slot-2026-10-05T09:00:00+00:00"', false)
            ->assertSee('wire:key="slot-2026-10-05T09:00:00+00:00" wire:click="$set(\'selectedStartAt\', \'2026-10-05T09:00:00+00:00\')" disabled', false);
    }

    public function test_public_booking_converts_business_slots_to_the_device_timezone_and_day(): void
    {
        $this->travelTo('2026-10-04 12:00:00');

        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC', 'is_active' => true]);
        $tenant->refresh();
        $service = Service::create(['tenant_id' => $tenant->id, 'name' => 'Book', 'duration_minutes' => 30]);
        $staff = StaffProfile::create(['tenant_id' => $tenant->id, 'display_name' => 'Hossein']);
        $staff->services()->attach($service, ['tenant_id' => $tenant->id, 'is_active' => true]);
        WorkingHour::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'weekday' => 1,
            'start_local_time' => '06:00',
            'end_local_time' => '07:00',
        ]);
        $component = new PublicBookingPage;
        $component->tenant = $tenant;
        $component->serviceId = (string) $service->id;
        $component->staffId = (string) $staff->id;
        $component->date = '2026-10-05';

        $component->setDeviceTimezone('America/Los_Angeles');
        $component->date = '2026-10-04';
        $component->updatedDate();
        $html = $component->render()->with(get_object_vars($component))->with('errors', new ViewErrorBag)->with('bookingHorizonDays', 30)->render();

        $this->assertSame('America/Los_Angeles', $component->deviceTimezone);
        $this->assertNotEmpty($component->availableSlots);
        $this->assertSame('2026-10-05T06:00:00+00:00', $component->availableSlots[0]['startAt']);
        $this->assertStringContainsString('Your time zone · America/Los_Angeles', $html);
        $this->assertStringContainsString('23:00', $html);
        $this->assertStringContainsString('wire:key="calendar-day-2026-10-04"', $html);
    }

    public function test_day_strip_stays_fixed_from_today_when_another_date_is_selected(): void
    {
        $this->travelTo('2026-10-04 12:00:00');

        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC']);
        $tenant->refresh();

        $component = new PublicBookingPage;
        $component->tenant = $tenant;
        $component->deviceTimezone = $tenant->timezone;
        $component->date = '2026-10-10';

        $html = $component->render()->with(get_object_vars($component))->with('errors', new ViewErrorBag)->with('bookingHorizonDays', 30)->render();

        $this->assertStringContainsString('wire:key="calendar-day-2026-10-04"', $html);
        $this->assertStringContainsString('wire:key="calendar-day-2026-11-03"', $html);
        $this->assertStringNotContainsString('wire:key="calendar-day-2026-11-04"', $html);
        $this->assertSame(31, substr_count($html, 'wire:key="calendar-day-'));
    }

    public function test_date_picker_and_component_reject_dates_more_than_thirty_days_ahead(): void
    {
        $this->travelTo('2026-10-04 12:00:00');

        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC', 'booking_horizon_days' => 90]);
        $component = new PublicBookingPage;
        $component->tenant = $tenant;
        $component->deviceTimezone = 'UTC';
        $component->date = '2026-11-03';
        $component->updatedDate();

        $this->assertFalse($component->getErrorBag()->has('date'));

        $component->date = '2026-11-04';
        $component->updatedDate();

        $this->assertTrue($component->getErrorBag()->has('date'));
    }

    public function test_booking_rejects_a_tampered_time_outside_the_thirty_day_window(): void
    {
        $this->travelTo('2026-10-04 12:00:00');

        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC', 'booking_horizon_days' => 90]);
        $tenant->refresh();
        $component = new PublicBookingPage;
        $component->tenant = $tenant;
        $component->deviceTimezone = 'UTC';
        $component->serviceId = '1';
        $component->staffId = '1';
        $component->date = '2026-11-04';
        $component->selectedStartAt = '2026-11-04T09:00:00+00:00';
        $component->customerName = 'Taylor';
        $component->customerEmail = 'taylor@example.test';

        try {
            $component->book(app(CreateAppointmentAction::class));
        } catch (ValidationException $exception) {
            $this->assertSame('Choose a time within the next 30 days.', $exception->errors()['selectedStartAt'][0]);

            return;
        }

        $this->fail('The booking action accepted a time outside the 30-day window.');
    }

    public function test_book_another_time_returns_to_the_tenant_booking_page(): void
    {
        $this->travelTo('2026-10-04 12:00:00');

        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC']);
        $component = new PublicBookingPage;
        $component->tenant = $tenant;
        $component->deviceTimezone = $tenant->timezone;
        $component->booked = true;

        $html = $component->render()->with(get_object_vars($component))->render();

        $this->assertStringContainsString('href="'.route('business.show', ['tenant' => $tenant->slug]).'"', $html);
        $this->assertStringNotContainsString('/livewire-', $html);
    }
}
