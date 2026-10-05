# Appointly MVP Domain Model

## Bounded contexts

The MVP has small modules rather than separate services:

- **Tenant and Identity:** business profile, membership, roles, authentication context.
- **Service and Staff:** bookable offerings and staff eligibility.
- **Schedule:** recurring working hours, days off, and time intervals.
- **Customer:** tenant-scoped people receiving appointments.
- **Appointment and Availability:** booking lifecycle, conflict rules, and slot calculation.

These modules share IDs and tenant context through application commands, but domain rules remain local to the owning module.

## Aggregates and entities

### Tenant aggregate

`Tenant` is the root for business identity: name, unique slug, timezone, contact details, booking horizon, and active state. It owns no large child collection in memory. Memberships, services, staff, and customers are loaded through scoped repositories.

`TenantMembership` associates a `UserId` with a `TenantId` and role (`Owner` or `Staff`). Membership is the authorization boundary.

### Service aggregate

`Service` contains name, description, duration in minutes, optional buffer, active state, and tenant. Duration must be positive and within a configured safe maximum. `StaffService` is the assignment relation; an appointment can only select an assigned active staff member.

### Staff aggregate

`StaffProfile` represents a tenant member's bookable profile, display name, active state, and optional public description. The MVP can reuse a user identity for login and have a separate staff profile for tenant-specific data.

### Schedule aggregate

`WorkingHours` contains a weekday and one or more non-overlapping local-time intervals. `DayOff` contains a tenant/staff scope, local date, and optional reason. The domain rejects invalid intervals, overlapping rules, and times outside `00:00`–`24:00`.

Schedule rules are staff-specific in the data model. Tenant defaults may be added later without changing appointment semantics.

### Customer aggregate

`Customer` is tenant-scoped contact data: name, email, phone, notes, and active state. Public booking may create a customer without a user account. Normalize email/phone before matching, and do not permit one tenant's customer to be used by another.

### Appointment aggregate

`Appointment` is the root for a reservation: tenant, customer, staff, service snapshot, start/end instants, local-date reference, status, notes, and cancellation/completion metadata. Store service name and duration snapshots so historical appointments remain understandable after a service changes.

Allowed lifecycle:

```text
pending -> confirmed -> completed
pending -> cancelled
confirmed -> cancelled
```

Invalid transitions are domain errors. Cancellation records actor, time, and reason. Completion is allowed only for a confirmed appointment at or after its scheduled end unless an owner explicitly overrides that policy.

## Value objects

Use value objects where they prevent recurring mistakes:

- `TenantId`, `UserId`, `StaffId`, `ServiceId`, `CustomerId`, `AppointmentId`.
- `LocalDate`, `LocalTime`, `UtcDateTime`, and `TimeInterval`.
- `TenantTimezone` validated against the IANA timezone database.
- `AppointmentStatus`, `MembershipRole`, and `DayOfWeek` enums.
- `TenantContext` containing the authenticated membership and tenant ID.

The domain should not require Eloquent model instances. Mapping belongs in Infrastructure.

## Domain services

Use two focused services:

- `AvailabilityCalculator`: pure calculation over service duration, schedule intervals, days off, current time, and occupied intervals.
- `AppointmentConflictChecker`: validates interval overlap and blocking statuses.

The application layer composes these with repositories and a transaction. Do not make a generic `BookingManager` that owns every rule.

## Domain errors

Represent expected business failures explicitly: `TenantAccessDenied`, `InvalidServiceDuration`, `StaffNotAssignedToService`, `OutsideWorkingHours`, `StaffDayOff`, `AppointmentConflict`, `InvalidAppointmentTransition`, and `BookingWindowExceeded`. Interfaces translate these to HTTP 403, 409, or 422 responses.

## Invariants

- Every aggregate operation is tenant-scoped.
- A service duration is positive and appointments end exactly at start plus the snapshotted duration/buffer.
- A staff member must be active and assigned to the service.
- Appointment start/end are valid, ordered, and represent one local date in the tenant timezone.
- A blocking appointment cannot overlap another blocking appointment for the same staff member.
- Cancelled appointments do not consume availability.
- A public booking cannot select an inactive tenant, inactive service, inactive staff member, or unassigned staff member.
