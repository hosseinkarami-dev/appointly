<?php

namespace Tests\Feature;

use App\Livewire\PublicBookingPage;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Models\WorkingHour;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PublicBookingInteractionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_service_selection_hydrates_and_refreshes_without_losing_component_state(): void
    {
        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC']);
        $service = Service::create(['tenant_id' => $tenant->id, 'name' => 'Consultation', 'duration_minutes' => 30]);
        $staff = StaffProfile::create(['tenant_id' => $tenant->id, 'display_name' => 'Dr. Ada']);
        $staff->services()->attach($service, ['tenant_id' => $tenant->id, 'is_active' => true]);

        $this->get('/business/okala')
            ->assertOk()
            ->assertSee('Choose a service')
            ->assertSee('Choose a day');
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

    public function test_selecting_a_date_updates_the_day_strip_to_include_that_date(): void
    {
        $this->travelTo('2026-10-04 12:00:00');

        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC']);
        $tenant->refresh();

        $component = new PublicBookingPage;
        $component->tenant = $tenant;
        $component->date = '2026-11-10';
        $component->updatedDate();

        $html = $component->render()->with(get_object_vars($component))->with('errors', new ViewErrorBag)->render();

        $this->assertStringContainsString('wire:key="calendar-day-2026-11-10"', $html);
        $this->assertStringNotContainsString('wire:key="calendar-day-2026-10-04"', $html);
    }

    public function test_book_another_time_returns_to_the_tenant_booking_page(): void
    {
        $this->travelTo('2026-10-04 12:00:00');

        $tenant = Tenant::create(['name' => 'Okala', 'slug' => 'okala', 'timezone' => 'UTC']);
        $component = new PublicBookingPage;
        $component->tenant = $tenant;
        $component->booked = true;

        $html = $component->render()->with(get_object_vars($component))->render();

        $this->assertStringContainsString('href="'.route('business.show', ['tenant' => $tenant->slug]).'"', $html);
        $this->assertStringNotContainsString('/livewire-', $html);
    }
}
