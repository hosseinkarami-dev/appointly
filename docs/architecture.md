# Appointly MVP Architecture

## Status and decisions

This document describes the architecture for the Appointly product roadmap. It is intentionally a modular Laravel monolith: one deployable application, one MySQL database, and clear boundaries around business rules. The current implementation includes the domain, application, API, Livewire, workspace, and public booking slices; this document remains the boundary contract as the product grows beyond its first release.

The current repository runs Laravel 13 on PHP 8.5 with Livewire 4, Sanctum, Tailwind CSS, and a PHPUnit suite. The web workspace uses a responsive shell with focused Livewire pages, while the API remains versioned and independent of web session state.

### Goals

- Let a business owner operate one appointment-based business.
- Let public customers discover services and book appointments.
- Keep API, Livewire, and server-rendered pages on the same application use cases.
- Make tenant isolation and booking correctness enforceable in code and in the database.
- Keep the future Kotlin Multiplatform client independent of Laravel views.

### Non-goals for MVP

Billing, plans, coupons, loyalty, accounting, advanced CRM, marketing automation, chat, video, calendar synchronization, multi-location businesses, marketplace features, reviews, advanced analytics, CQRS, microservices, and a message broker are deferred.

## Recommended boundaries

Use four pragmatic layers under `app/`:

```text
app/
├── Domain/                 # Framework-free business rules and contracts
│   ├── Appointment/
│   ├── Customer/
│   ├── Identity/
│   ├── Schedule/
│   ├── Service/
│   ├── Staff/
│   └── Tenant/
├── Application/            # Use cases, commands/queries, DTOs
│   ├── Appointment/
│   ├── Availability/
│   ├── Customer/
│   ├── Schedule/
│   ├── Service/
│   ├── Staff/
│   └── Tenant/
├── Infrastructure/         # Laravel/Eloquent implementations
│   ├── Persistence/Eloquent/
│   ├── Auth/
│   └── Notifications/
└── Interfaces/             # Delivery mechanisms
    ├── Api/V1/
    ├── Livewire/
    └── Web/
```

The layer dependency direction is `Interfaces -> Application -> Domain`. Infrastructure implements Domain/Application ports and is wired through Laravel's container. Domain has no Laravel, Eloquent, HTTP, Livewire, or database imports. Application code accepts input DTOs and returns output DTOs or domain results; it does not know about requests, sessions, or component state.

Use an interface only at a meaningful boundary, such as repositories needed by a use case, a clock, or a transaction abstraction. Do not create interfaces for every model or value object.

## Request flow

```text
API controller / Livewire action / web controller
        -> request validation and authorization
        -> application use case
        -> domain entities, value objects, and policies
        -> repository contract
        -> Eloquent implementation and transaction
        -> resource / view model / component state
```

Controllers and Livewire components should translate input, call one use case, and translate the result. They must not calculate availability, decide tenant ownership, or mutate Eloquent models directly.

## Cross-cutting rules

- Every business-owned record carries `tenant_id` and has a foreign key to `tenants`.
- The authenticated user has a current tenant membership; public booking resolves a tenant by its unique slug.
- Application commands receive an explicit tenant context. A repository query must require that context for tenant-owned data.
- Policies authorize the current user and tenant role; they are not the only isolation mechanism.
- API errors use one stable envelope and never expose model internals or SQL errors.
- Store appointment times in UTC and retain the tenant's IANA timezone for display and slot generation.
- Use integer minutes for service durations and working-hour times, avoiding floating point time arithmetic.
- Use immutable domain objects for money-free time concepts such as local date, local time, and interval.

## Authentication and authorization

Use Laravel's session authentication for the web/admin UI and Sanctum personal access tokens for `/api/v1`. API tokens should have abilities only if the product later needs token-level restrictions; MVP authorization remains membership-role based.

`users` are identities. `tenant_memberships` connect users to businesses with `owner` or `staff` role. A user may later belong to multiple tenants, but the MVP UI may select one current tenant. Owners manage tenant settings, staff, services, schedules, customers, and all appointments. Staff manage their assigned services and appointments within the tenant, subject to explicit policy checks.

Public booking endpoints are unauthenticated. A public customer record is created or matched by tenant and normalized contact information. Customer self-service access should use a short-lived signed link or one-time token in a later slice; never expose appointments by sequential numeric ID alone.

## Availability and booking

The availability query takes tenant, service, optional staff, local date, and booking interval settings. It:

1. Loads the tenant timezone and validates the requested date.
2. Selects eligible staff who are active, assigned to the service, and in the requested tenant.
3. Reads the staff's working-hours rules for that weekday.
4. Removes date-specific days off and other blocks.
5. Converts working intervals to UTC and subtracts appointments in blocking statuses.
6. Splits remaining intervals into service-duration slots using the configured slot increment.
7. Excludes slots in the past, outside the booking horizon, or with insufficient duration.
8. Returns slots with the staff identity so the booking command can revalidate the exact choice.

Availability is advisory. `CreateAppointment` repeats the relevant checks inside one database transaction immediately before insert.

## Double-booking prevention

The source of truth is a transaction-safe database reservation strategy:

- Appointment creation runs at `REPEATABLE READ` or the MySQL default with an explicit transaction.
- It locks the selected staff/day reservation scope before checking overlapping appointments. The recommended MVP implementation is a `staff_day_locks` row keyed by `(tenant_id, staff_id, local_date)` created ahead of time or inserted with a unique-key race-safe helper, then selected `FOR UPDATE`.
- Within that lock, it checks overlap using half-open intervals: `existing.start_at < requested.end_at AND existing.end_at > requested.start_at`.
- Only `pending`, `confirmed`, and `completed` appointments block a slot; `cancelled` does not.
- It validates tenant, service, staff assignment, working hours, days off, and duration again while locked.
- A unique constraint on the reservation scope prevents two lock rows. A duplicate-key or deadlock exception is retried a small, bounded number of times and then returned as a conflict.

Do not rely on a preflight availability query, an application-only mutex, or a frontend disabled button. A later scaling phase may use MySQL exclusion-like slot rows or Redis locks, but Redis is not required for the MVP.

## Application use cases

Commands: register user, create tenant, update tenant profile, invite/add staff, assign staff to service, create/update/archive service, define working hours, define days off, create customer, create appointment, confirm appointment, cancel appointment, and complete appointment.

Queries: get current tenant, list staff/services/customers/appointments, get public business profile, list public services, and get available slots.

Each use case owns its authorization precondition and transaction boundary. Read models may use optimized Eloquent queries, but they still require tenant or public-tenant scoping.

## Testing strategy

Use Pest for the application test suite, with framework-free unit tests for intervals, working-hour rules, status transitions, and availability calculations. Feature tests cover tenant isolation, policies, API envelopes, public booking, authentication, and database constraints. A dedicated integration test runs concurrent booking attempts against MySQL and asserts one success and one conflict. SQLite is useful for fast unit/most feature tests, but MySQL is required in CI for locking behavior. The current skeleton still contains Laravel's default PHPUnit setup; the implementation phase should add/configure Pest before writing domain tests.

## Deployment shape

Start with one Laravel application, MySQL, and a queue worker only when notifications are introduced. Use the database queue initially if asynchronous work is needed. Add object storage or Redis only for a demonstrated requirement. Logs must include tenant ID, request ID, and appointment ID without exposing customer secrets.

## Approval decisions

Before implementation, confirm the product owner accepts: one tenant per current UI context but multi-membership-ready data, UTC storage plus tenant IANA timezone, public bookings without account creation, `pending` appointments blocking time, and the `staff_day_locks` transaction strategy.
