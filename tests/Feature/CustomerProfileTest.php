<?php

namespace Tests\Feature;

use App\Application\Customer\UpdateCustomerAction;
use App\Domain\Identity\Enums\MembershipRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_workspace_customer_can_be_updated_and_the_change_is_audited(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'North Clinic', 'slug' => 'north-clinic', 'timezone' => 'UTC']);
        $tenant->users()->attach($user, ['role' => MembershipRole::Owner->value, 'is_active' => true]);
        $customer = $tenant->customers()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

        $this->actingAs($user);

        $updated = app(UpdateCustomerAction::class)->handle($tenant, $customer->id, [
            'name' => 'Ada Byron Lovelace',
            'email' => 'ada.byron@example.com',
            'phone' => '+1 555 0100',
            'notes' => 'Prefers morning appointments.',
        ]);

        $this->assertSame('Ada Byron Lovelace', $updated->name);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'email' => 'ada.byron@example.com']);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $user->id,
            'action' => 'customer.updated',
            'auditable_id' => $customer->id,
        ]);
    }
}
