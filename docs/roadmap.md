# Appointly Product Roadmap

## Product direction

Appointly is a polished, multi-tenant booking workspace for service businesses. It should feel calm and premium for the business operator while giving clients a fast, trustworthy booking experience on every device.

The goal is more than an MVP: Appointly should become a dependable operating system for appointments, availability, customers, staff, and business growth. The web application remains the primary workspace, and future Kotlin Multiplatform clients consume the stable versioned REST API.

## Product principles

- Make the next action obvious: book, confirm, reschedule, or prepare.
- Treat time and availability as server-owned business rules.
- Keep every tenant boundary explicit in application use cases and persistence queries.
- Prefer focused, composable pages and components over one giant dashboard screen.
- Make the experience accessible, responsive, fast, and comfortable in light or dark mode.
- Keep the API independent from Blade and Livewire so future clients do not inherit web concerns.
- Build a dependable foundation before adding billing, integrations, or automation.

## Delivery stages

### Stage 0 — foundation and correctness

- Keep Laravel 13+, PHP 8.5+, MySQL, Sanctum, Livewire, and the layered application structure aligned.
- Use named web routes and a stable `/dashboard` workspace entry point.
- Add request IDs, consistent API errors, tenant context, role authorization, and explicit resources.
- Keep domain rules framework-free and route all reusable mutations through application actions.
- Add feature coverage for authentication, tenant isolation, booking conflicts, and workspace routes.

### Stage 1 — workspace experience

- Replace the single setup dashboard with a real workspace shell and responsive navigation.
- Provide dedicated pages for overview, calendar, appointments, services, team, customers, and workspace settings.
- Make the authenticated workspace feel like one app with Livewire navigation, persisted shell elements, and clear loading transitions.
- Add reusable UI components for page headers, metric cards, empty states, status badges, dialogs, filters, tables, and mobile navigation.
- Show meaningful empty states and guided setup progress for new businesses.
- Add global search, notifications, profile menu, theme preference, and shareable booking-page actions.
- Keep Livewire components focused by page; do not duplicate business decisions in Blade.

### Stage 2 — booking operations

- Deliver a calendar with day, week, and agenda views, timezone-aware navigation, and appointment status actions.
- Add appointment detail pages with customer history, notes, status timeline, and safe cancellation/completion flows.
- Add tenant-scoped appointment detail pages with confirm, complete, and cancel actions backed by the existing transition use case.
- Add customer list, search, profile, appointment history, notes, and contact normalization.
- Add service catalog management with active/archive states, durations, buffers, pricing-ready fields, and staff assignment.
- Add staff profiles, working hours, exceptions, service assignments, and availability previews.
- Use the existing appointment creation and transition use cases for every web and API booking path.

### Stage 3 — client booking experience

- Build a branded public booking page with service discovery, staff selection, date navigation, live availability, and a clear confirmation state.
- Add public appointment lookup through opaque tokens and safe reschedule/cancellation policies. **Lookup and public cancellation are now available; rescheduling remains.**
- Add embeddable booking links and business profile sharing.
- Improve validation, loading states, conflict recovery, accessibility, mobile ergonomics, and timezone explanations.

### Stage 4 — communication and automation

- Add email and in-app notifications for new, confirmed, cancelled, and completed appointments. **Lifecycle notifications are wired for customer email and active workspace-user database notifications.**
- Add reminders, notification preferences, templates, and a durable queue workflow. **A scheduled, idempotent 24-hour reminder command, workspace notification preferences, and queue-capable notifications are now in place; production queue infrastructure and editable templates remain.**
- Add audit history for owner actions, appointment transitions, and important configuration changes.
- Add scheduled availability summaries and operational alerts.

### Stage 5 — insights and integrations

- Add operational reporting: utilization, cancellations, no-shows, revenue-ready summaries, and customer retention signals. **A tenant-scoped monthly reports page now covers appointment volume, confirmation/completion/cancellation rates, and service demand; utilization, no-shows, and revenue-ready metrics remain.**
- Add calendar integrations behind application contracts.
- Add webhooks and API keys for external systems while preserving `/api/v1` compatibility.
- Add export tools and configurable data retention.

### Stage 6 — platform readiness

- Add subscription and billing only after the core booking workflow is stable.
- Add multiple locations, richer roles, customer accounts, waitlists, recurring availability, rescheduling, and payments as separate bounded capabilities.
- Add MySQL CI for locking tests, browser smoke tests, backups, monitoring, deployment automation, and performance budgets.
- Publish generated API contracts and Kotlin Multiplatform DTO guidance. **Owner-managed Sanctum API keys, signed queued webhooks, a versioned OpenAPI v1 contract, and KMP client guidance are now available.**

## Operations notes

- Production should use `QUEUE_CONNECTION=database` with a continuously running `php artisan queue:work --tries=3` process.
- The scheduler must run `php artisan schedule:run` every minute; it dispatches the hourly appointment reminder command.
- Failed queue jobs are stored in `failed_jobs` and should be monitored with `php artisan queue:failed`.
- `/health/ready` verifies database connectivity and reports the configured queue connection for deployment probes.
- On Laravel Cloud, enable managed database backups and keep backup retention separate from the ephemeral application filesystem; verify restore procedures before production launch.

## Current implementation sequence

1. Correct route naming and establish `/dashboard` as the workspace entry point. **Complete.**
2. Move workspace mutations behind application actions and add action coverage. **Complete.**
3. Build the workspace shell and split the dashboard into focused pages/components. **Complete for the first workspace slice.**
4. Build calendar, appointment detail, and customer operations. **In progress: detail/profile surfaces, safe status actions, appointment note editing, appointment/customer activity timelines, service/team management, working hours, customer editing, and service-aware booking are available; richer schedule editing and timeline filtering remain.**
5. Polish public booking and add client self-service access. **In progress: availability selection, service-aware staff filtering, token lookup, cancellation, rescheduling, iframe embedding, and configurable cancellation cutoff controls are available; final mobile/accessibility polish remains.**
6. Add communication, auditability, reporting, and deployment hardening. **In progress: lifecycle notifications, audit logging, scheduled reminders, notification preferences, no-show tracking, utilization reporting, CSV and iCalendar appointment exports, queue-capable delivery, and monthly reports are available; provider sync and production worker operations remain.**

## Acceptance gates

Every stage is accepted only when:

- Domain and application tests pass.
- Tenant isolation is covered for reads and writes.
- Owner/staff authorization boundaries are explicit.
- API and Livewire paths use the same application use cases.
- New UI works on mobile and desktop, supports keyboard navigation, and respects dark mode.
- Loading, empty, validation, conflict, and error states are designed—not accidental.
- Database access is scoped, ordered, and eager-loaded where needed.
- No business rule is duplicated in controllers, Livewire components, or Blade templates.
- The full test suite and production asset build pass.

## Deferred product decisions

- Paid plans, billing provider, and trial policy.
- Customer accounts versus guest-first self-service.
- Cancellation cutoff and no-show policy.
- Automatic staff assignment when multiple staff are eligible.
- Calendar providers and synchronization direction.
- Multi-location data model and role granularity.
