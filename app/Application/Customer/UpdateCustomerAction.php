<?php

namespace App\Application\Customer;

use App\Models\Customer;
use App\Models\Tenant;

final class UpdateCustomerAction
{
    /** @param array{name: string, email: ?string, phone: ?string, notes: ?string} $data */
    public function handle(Tenant $tenant, int $customerId, array $data): Customer
    {
        $customer = $tenant->customers()->whereKey($customerId)->firstOrFail();
        $before = $customer->only(['name', 'email', 'phone', 'notes']);

        $customer->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'notes' => $data['notes'],
        ]);

        $tenant->auditLogs()->create([
            'actor_id' => auth()->id(),
            'action' => 'customer.updated',
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
            'before' => $before,
            'after' => $customer->only(['name', 'email', 'phone', 'notes']),
        ]);

        return $customer->refresh();
    }
}
