# Appointly MVP Database Design

MySQL is the production database. All IDs are unsigned big integers or UUIDs chosen consistently at implementation time; this proposal uses UUIDs for public-facing resources and internal numeric IDs only if the team prefers Laravel defaults. Foreign keys, unique constraints, and indexes are part of the tenant-safety design.

## Tables

| Table | Important columns and constraints |
|---|---|
| `users` | `id`, `name`, `email` unique, password, verification timestamps |
| `tenants` | `id`, `name`, `slug` unique, `timezone`, contact fields, `booking_horizon_days`, active, timestamps |
| `tenant_memberships` | `tenant_id`, `user_id`, `role`, active; unique `(tenant_id, user_id)` |
| `staff_profiles` | `id`, `tenant_id`, `user_id` nullable/unique per tenant, display fields, active; indexes `(tenant_id, active)` |
| `services` | `id`, `tenant_id`, name, description, `duration_minutes`, `buffer_minutes`, active, timestamps; index `(tenant_id, active)` |
| `staff_services` | `tenant_id`, `staff_id`, `service_id`, active; unique `(tenant_id, staff_id, service_id)` |
| `working_hours` | `tenant_id`, `staff_id`, weekday, `start_local_time`, `end_local_time`, active; index `(tenant_id, staff_id, weekday)` |
| `days_off` | `tenant_id`, `staff_id` nullable for all staff, `local_date`, reason; index `(tenant_id, local_date, staff_id)` |
| `customers` | `id`, `tenant_id`, name, normalized email/phone, display email/phone, notes, active; indexes `(tenant_id, normalized_email)` and `(tenant_id, normalized_phone)` |
| `appointments` | `id`, `tenant_id`, customer/staff/service IDs, service name/duration snapshots, `start_at`, `end_at`, `local_date`, status, notes, cancellation/completion metadata; indexes `(tenant_id, staff_id, start_at, end_at, status)` and `(tenant_id, customer_id, start_at)` |
| `staff_day_locks` | `tenant_id`, `staff_id`, `local_date`, timestamps; unique `(tenant_id, staff_id, local_date)` |
| `personal_access_tokens` | Sanctum token table, hashed token and abilities |

All tenant-owned foreign keys should use `ON DELETE RESTRICT` by default. Prefer archiving/deactivation over deleting services, staff, customers, or appointments with history. Deleting a tenant should be an explicit administrative operation with a retention policy, not a casual cascade.

## Appointment storage

Store `start_at` and `end_at` as UTC `datetime` values. Store `local_date` as the tenant-local booking date used for locking and schedule lookup. Keep `timezone` on the tenant, not on each appointment, unless regulatory/history requirements later require a timezone snapshot. The service snapshot includes name and duration; IDs still point to the current records for navigation.

Use a status enum represented by a database string with application validation: `pending`, `confirmed`, `cancelled`, `completed`. Add a check constraint where supported, but do not rely on it as the only validation.

## Tenant isolation

Repositories require a `TenantContext` and include `where tenant_id = ?` in every tenant-owned query. Application commands validate related IDs under the same tenant. Policies check membership and role. Eloquent global scopes may provide a safety net for ordinary reads, but explicit scoped repository methods remain the primary mechanism because global scopes are easy to bypass in maintenance code.

Public queries use the resolved tenant ID from a unique slug, then apply the same tenant predicate. Never accept a tenant ID from an untrusted client as proof of access.

## Concurrency design

`staff_day_locks` serializes booking writes for a staff/date pair. The booking transaction obtains that row with `FOR UPDATE`, checks overlap, then inserts. Ensure the row exists before locking; if lazy creation is required, insert it with the unique key and handle duplicate-key races before selecting it. Keep the critical section short and do not send email or call external services inside it.

The overlap predicate is:

```sql
existing.start_at < requested_end_at
AND existing.end_at > requested_start_at
AND existing.status IN ('pending', 'confirmed', 'completed')
```

This half-open interval convention allows an appointment ending at 10:00 and another starting at 10:00. The database index accelerates the check, while the lock provides serialization.

## Migration order

1. Extend users/authentication support and create tenants.
2. Add memberships and staff profiles.
3. Add services and staff-service assignments.
4. Add working hours and days off.
5. Add customers.
6. Add appointments and staff-day locks.
7. Add Sanctum tokens and any indexes discovered by query profiling.

Each migration must include foreign keys, tenant-aware indexes, and a rollback. Seed only deterministic demo data in local development; never seed production tenants or users.
