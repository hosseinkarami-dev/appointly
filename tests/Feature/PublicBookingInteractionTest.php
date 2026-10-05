<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
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
}
