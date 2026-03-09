# Milestone 1: Project Setup & Architecture

## Stack and baseline
- Framework: Laravel 12 (`laravel/framework:^12.0`) on PHP 8.2+
- Auth baseline: `laravel/ui` server-rendered auth flow
- Database: MySQL for local/staging profiles

## Environment strategy
- Local template: `.env.example`
- Staging template: `.env.staging.example`
- Local defaults:
  - `APP_ENV=local`
  - `APP_DEBUG=true`
  - DB name `khadija_local`
- Staging defaults:
  - `APP_ENV=staging`
  - `APP_DEBUG=false`
  - DB name `khadija_staging`
  - stronger logging level (`LOG_LEVEL=info`)

## Clean architecture boundaries
- `app/Domain/*`: business-facing contracts, DTOs, enums, events, services
- `app/Http/*`: transport layer (controllers, middleware)
- `app/Models/*`: persistence models and relationships
- `app/Providers/DomainServiceProvider.php`: service binding gateway

Implemented domain layer for auth:
- Contract: `UserRegistrationServiceInterface`
- DTO: `RegisterUserData`
- Service: `EloquentUserRegistrationService`
- Event: `UserRegistered`

## Auth and roles (current)
- Auth: Laravel session-based (`web` guard), `laravel/ui` for login/register
- Roles: Spatie Permission package; roles `admin`, `client`, `supplier` (see `App\Domain\Auth\Enums\RoleName`)
- Authorization: Permission-based middleware on routes (e.g. `permission:events.view_any`), plus policy checks (`EventPolicy`, `BookingPolicy`, `MessagePolicy`)
- Admin bypass: `Gate::before` in `AppServiceProvider` grants all abilities to users with role `admin`

## Event-driven architecture
- Domain events: `UserRegistered` (Auth), `MessageInserted` (Messaging)
- Listeners: `LogUserRegistered`, `LogMessageInserted`; registered in `AppServiceProvider::boot()`
- New side effects (e.g. notifications, audit) should be added as listeners rather than in controllers

## Immutable message log
- Messages are insert-only: no update or delete endpoints; `messages` table has no `updated_at`
- Every new message dispatches `MessageInserted` for downstream processing (logging, future notifications)

## REST + event services
- REST APIs: `EventController`, `BookingController`, `MessageController` (resource-style under `auth` middleware)
- Event details: `GET /events/{event}/details` with full relations
- Domain events are dispatched from the application layer (e.g. after creating a message); no separate “event service” process in Month 1

## Clear separation of concerns
- **Messaging:** `app/Domain/Messaging/`, message CRUD (insert-only), `MessageInserted` event. No edit/delete of messages.
- **Agreement orchestration:** Booking lifecycle (status: requested → confirmed | cancelled | completed). Append-only `agreement_log` table records every booking state change (create + status update); see `App\Models\AgreementLog`.
- **Scheduling:** Event model fields `starts_at`, `ends_at`. Scheduling is part of event management; no separate scheduling service in Month 1.
- **Payments:** Not in scope for Month 1; to be introduced in a later milestone with clear ownership rules (see `docs/ownership-rules.md`).

## Next milestone hooks already prepared
- `UserRegistered` domain event to connect onboarding listeners (email, audit log, CRM sync)
- Service contract allows swapping registration implementation without touching controllers
- Ownership rules for AI-derived vs user-confirmed data: `docs/ownership-rules.md`
