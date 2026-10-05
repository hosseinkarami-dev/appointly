# Appointly API Contract (v1 proposal)

The API is versioned under `/api/v1`. It is a client contract, not a serialization of Eloquent models. JSON uses camelCase in examples, ISO-8601 UTC timestamps, and stable resource names. The eventual OpenAPI document should be generated from or maintained beside these definitions before client implementation begins.

## Authentication

`POST /api/v1/auth/register` creates a user and, when onboarding includes a business name, a tenant and owner membership. `POST /api/v1/auth/login` returns a Sanctum token. `POST /api/v1/auth/logout` revokes the current token. `GET /api/v1/me` returns the identity and memberships. Authenticated endpoints use `Authorization: Bearer <token>`.

Web/admin pages use the normal Laravel session; the API must not depend on Livewire or session state.

## Resource groups

Authenticated tenant routes use the current tenant context. An explicit tenant selector may be represented by `X-Tenant-Id` only after membership authorization; the preferred long-term contract is a tenant membership endpoint and a selected tenant ID in the token/session context.

| Area | Endpoints |
|---|---|
| Tenant | `GET/PATCH /tenant`, `GET /tenant/members`, `POST /tenant/members` |
| Services | `GET/POST /services`, `GET/PATCH/DELETE /services/{service}`, assignment endpoints |
| Staff | `GET/POST /staff`, `GET/PATCH /staff/{staff}` |
| Schedule | `GET/PUT /staff/{staff}/working-hours`, `GET/POST/DELETE /days-off` |
| Customers | `GET/POST /customers`, `GET/PATCH /customers/{customer}` |
| Appointments | `GET /appointments`, `POST /appointments`, `GET /appointments/{appointment}`, status actions |
| Availability | `GET /availability?serviceId=&staffId=&date=` |

Public routes do not require authentication:

```text
GET  /api/v1/public/businesses/{slug}
GET  /api/v1/public/businesses/{slug}/services
GET  /api/v1/public/businesses/{slug}/availability?serviceId=&staffId=&date=
POST /api/v1/public/businesses/{slug}/appointments
GET  /api/v1/public/appointments/{publicToken}
```

The public appointment token is opaque, random, short-lived where appropriate, and scoped to the appointment. Do not use sequential appointment IDs as public credentials.

## Appointment request

```json
{
  "serviceId": "svc_123",
  "staffId": "stf_123",
  "startAt": "2026-10-08T09:00:00Z",
  "customer": {
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "phone": "+1 555 0100"
  },
  "notes": "Please call on arrival"
}
```

The server determines end time from the service snapshot and verifies tenant timezone, working hours, days off, booking horizon, staff assignment, and conflicts. Clients must not submit an authoritative end time.

## Response envelope

Single resources:

```json
{
  "data": {
    "id": "apt_123",
    "status": "pending",
    "startAt": "2026-10-08T09:00:00Z",
    "endAt": "2026-10-08T09:30:00Z",
    "service": { "id": "svc_123", "name": "Consultation", "durationMinutes": 30 },
    "staff": { "id": "stf_123", "name": "Sam Lee" },
    "customer": { "id": "cus_123", "name": "Ada Lovelace" }
  }
}
```

Collections use `data` plus pagination metadata:

```json
{
  "data": [],
  "meta": { "currentPage": 1, "perPage": 20, "total": 0, "lastPage": 1 },
  "links": { "self": "/api/v1/appointments?page=1", "next": null }
}
```

Errors use a predictable envelope:

```json
{
  "error": {
    "code": "appointment_conflict",
    "message": "The selected time is no longer available.",
    "details": { "field": "startAt" },
    "requestId": "req_123"
  }
}
```

Use HTTP 401 for missing/invalid authentication, 403 for authorization, 404 for missing resources in the authorized scope, 409 for booking conflicts or invalid concurrent state, 422 for validation/domain input errors, and 429 for throttling. Do not leak whether another tenant's identifier exists.

## Filtering and pagination

List endpoints support documented filters only, for example `status`, `staffId`, `from`, `to`, and `page`. Use cursor pagination for appointment timelines if volume requires it; page-number pagination is sufficient for the first release. Cap page size and reject unknown filter fields rather than silently accepting them.

## Compatibility rules

Once v1 is consumed, additive fields are allowed, but changing meaning, removing fields, or changing enum values requires a new version or a deprecation period. Every endpoint must document authorization, validation, status codes, and example success/error responses in the OpenAPI contract. API resources must be explicit so internal column renames do not become breaking changes.
