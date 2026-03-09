# Milestone 1 - Stabilization & Review

## Scope covered
- Fixed runtime/security blockers in messaging visibility.
- Added baseline project docs for core flows and test commands.
- Executed internal test suite with feature-level flow verification.

## Blockers fixed
- Message listing exposure:
  - Non-admin users could fetch unrelated messages via `GET /messages`.
  - Fix applied in:
    - `app/Http/Controllers/MessageController.php`
    - `app/Http/Controllers/Dashboard/MessagePageController.php`
  - Current behavior:
    - Admin can list all messages.
    - Non-admin can only list messages where they are sender or recipient.

## Core flow status
- Event CRUD + publish logic: working.
- Event details: **API** `GET /events/{event}/details` and **UI** `/app/events/{event}` (show page with event info, bookings, messages): working.
- Booking flow: create (published-event check), index/show/update with role scoping: working.
- Messaging (insert-only + `MessageInserted` event firing): working.

## Internal testing
Run locally:

```bash
php artisan migrate --seed
php artisan test
```

Key feature tests:
- `tests/Feature/EventPolicyTest.php`
- `tests/Feature/EventBookingMessageFlowTest.php` (flow, event details API, agreement log on booking create/update)
- `tests/Feature/RolesPermissionsCrudTest.php`
- `tests/Feature/UserAccessUiTest.php`

## Result
- Backend is stable for Milestone 1 scope and testable via automated feature tests.
- Full Month 1 verification (goals, scope, deliverables, clear separation): see `docs/month1-verification.md`.
- Line-by-line completion checklist: see `docs/discovery-stabilization-completion.md`.
