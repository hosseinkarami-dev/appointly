<?php

namespace App\Models;

use App\Domain\Appointment\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['public_token', 'tenant_id', 'customer_id', 'staff_profile_id', 'service_id', 'service_name', 'duration_minutes', 'buffer_minutes', 'start_at', 'end_at', 'local_date', 'status', 'notes', 'cancelled_at', 'cancelled_by', 'cancellation_reason', 'completed_at', 'reminder_sent_at'])]
class Appointment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'start_at' => 'immutable_datetime',
            'end_at' => 'immutable_datetime',
            'local_date' => 'date',
            'status' => AppointmentStatus::class,
            'cancelled_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'reminder_sent_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<StaffProfile, $this> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'staff_profile_id');
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
