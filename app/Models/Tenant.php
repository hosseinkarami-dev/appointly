<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'timezone', 'email', 'phone', 'booking_horizon_days', 'cancellation_cutoff_hours', 'notify_new_appointments', 'notify_appointment_lifecycle', 'reminders_enabled', 'webhook_url', 'webhook_secret', 'is_active'])]
class Tenant extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'booking_horizon_days' => 'integer',
            'cancellation_cutoff_hours' => 'integer',
            'is_active' => 'boolean',
            'notify_new_appointments' => 'boolean',
            'notify_appointment_lifecycle' => 'boolean',
            'reminders_enabled' => 'boolean',
        ];
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_memberships')
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    /** @return HasMany<StaffProfile, $this> */
    public function staff(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /** @return HasMany<Customer, $this> */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** @return HasMany<WorkingHour, $this> */
    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class);
    }

    /** @return HasMany<DayOff, $this> */
    public function daysOff(): HasMany
    {
        return $this->hasMany(DayOff::class);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
