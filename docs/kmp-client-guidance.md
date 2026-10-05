# Kotlin Multiplatform client guidance

Appointly clients should consume `/api/v1` as a stable REST boundary. The client must not mirror Laravel models or depend on Livewire behavior.

## Transport

- Use `Authorization: Bearer <token>` for authenticated calls.
- Send `X-Tenant-Id` when a user belongs to more than one workspace.
- Treat all timestamps as ISO-8601 instants and convert them to the workspace timezone only for display.
- Generate API models from [`openapi-v1.yaml`](api/openapi-v1.yaml), then map generated DTOs into client-facing domain models.

## Recommended DTOs

- `AuthResponse(token, user)`
- `Business(id, name, slug, timezone)`
- `Service(id, name, durationMinutes, bufferMinutes)`
- `Staff(id, displayName)`
- `AvailabilitySlot(startAt, endAt)`
- `Appointment(id, publicToken, status, serviceName, startAt, endAt, customer, staff)`
- `Paginated<T>(data, meta, links)`
- `ApiError(code, message, details, requestId)`

Keep `AppointmentStatus` exhaustive and unknown-value tolerant so additive server values do not crash older clients. A conflict (`409`) should refresh availability and present a recoverable booking message. A `422` should map field errors to the form. A `401` should clear the token and return the user to authentication.

## Platform split

- Shared KMP code: DTOs, serialization, API client, repositories, validation mapping, and appointment state.
- Android/iOS/Web code: navigation, secure token storage, notifications, calendars, and platform UI.
- Do not put tenant authorization or availability decisions in the client; the server remains authoritative.
