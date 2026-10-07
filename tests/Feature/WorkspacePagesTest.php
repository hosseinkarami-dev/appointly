<?php

namespace Tests\Feature;

use App\Domain\Identity\Enums\MembershipRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WorkspacePagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_workspace_pages_render_for_the_current_tenant(): void
    {
        $user = User::factory()->create(['name' => 'Ada Lovelace']);
        $tenant = Tenant::create([
            'name' => 'North Clinic',
            'slug' => 'north-clinic',
            'timezone' => 'UTC',
        ]);
        $tenant->users()->attach($user, [
            'role' => MembershipRole::Owner->value,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        foreach (['/workspace/overview', '/workspace/calendar', '/workspace/appointments', '/workspace/services', '/workspace/team', '/workspace/customers', '/workspace/reports', '/workspace/settings'] as $path) {
            $this->get($path)->assertOk();
        }

        $this->get('/workspace')->assertRedirect('/workspace/overview');
        $this->get('/dashboard')->assertRedirect('/workspace/overview');
        $this->get('/settings')->assertRedirect('/workspace/settings');

        $this->get('/embed/north-clinic')->assertOk();

        $this->get('/workspace/appointments/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
