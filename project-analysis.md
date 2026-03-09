# Khadija Project — Full Codebase Analysis

## Project Overview

**Khadija** is a Laravel 12 event management and booking platform with a domain-driven service architecture. It facilitates a three-party workflow between **Admins**, **Clients**, and **Suppliers** — managing events, bookings, messaging, and agreement tracking with full role-based access control.

**Tech Stack:** PHP 8.2+, Laravel 12, Spatie Permission 6.x, SQLite (dev) / MySQL (staging), Vite 7, Bootstrap 5 + Tailwind CSS 4, NobleUI dashboard template.

---

## Architecture

The project follows a clean, layered architecture:

```
app/
├── Domain/                      # Business logic layer
│   ├── Auth/
│   │   ├── Contracts/           # Service interfaces
│   │   ├── DataTransferObjects/ # DTOs
│   │   ├── Enums/               # RoleName enum
│   │   ├── Events/              # UserRegistered
│   │   ├── Listeners/           # LogUserRegistered
│   │   └── Services/            # EloquentUserRegistrationService
│   └── Messaging/
│       ├── Events/              # MessageInserted
│       └── Listeners/           # LogMessageInserted
├── Http/
│   └── Controllers/
│       ├── Auth/                # 6 auth controllers (login, register, etc.)
│       ├── Dashboard/           # 7 page controllers (UI/Blade)
│       ├── EventController      # REST API (JSON)
│       ├── BookingController    # REST API (JSON)
│       └── MessageController    # REST API (JSON)
├── Models/                      # Eloquent models (User, Event, Booking, Message, AgreementLog)
├── Policies/                    # EventPolicy, BookingPolicy, MessagePolicy
└── Providers/                   # AppServiceProvider, DomainServiceProvider
```

**Key Design Patterns:**
- Service contracts with dependency injection (UserRegistrationServiceInterface)
- Event-driven side effects (UserRegistered, MessageInserted)
- DTOs for clean data transfer (RegisterUserData)
- Policy-based authorization at model level
- Immutable audit trail (AgreementLog is append-only, Messages are insert-only)

---

## Database Schema (13 migrations)

### Core Business Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| **events** | Event management | title, description, status, is_published, published_at, starts_at, ends_at, client_id, supplier_id, source |
| **bookings** | Booking lifecycle | event_id, client_id, supplier_id, status (requested→confirmed→completed/cancelled), source |
| **messages** | Insert-only messaging | event_id, booking_id, sender_id, recipient_id, body, source (NO updated_at) |
| **agreement_log** | Immutable audit trail | subject_type, subject_id, from_status, to_status, changed_by, notes (NO updated_at) |

### Relationships

- **User** → has many: created events, client events, supplier events, bookings, sent/received messages
- **Event** → belongs to: creator, client, supplier; has many: bookings, messages
- **Booking** → belongs to: event, client, supplier, creator; has many: messages, agreement logs
- **Message** → belongs to: event, booking, sender, recipient

### Source Tracking

All three core tables (events, bookings, messages) have a `source` column: `user` (default), `ai`, or `system` — designed for future AI integration where AI-derived data is distinguished from user-confirmed data.

---

## Roles & Permissions (27 permissions)

### Three Roles

| Role | Scope | Key Capabilities |
|------|-------|-----------------|
| **Admin** | Full access (all 27 permissions) | Manage all resources, users, roles, permissions |
| **Client** | 14 permissions | Create/manage own events and bookings, send messages, view agreement log |
| **Supplier** | 11 permissions | View/update assigned events and bookings, send messages |

### Permission Categories

- **Dashboard:** dashboard.view
- **Events:** view_any, view, create, update, delete, publish (7)
- **Bookings:** view_any, view, create, update (4)
- **Messages:** view_any, view, create (3, no edit/delete by design)
- **Agreement Log:** view_any (1)
- **Admin-only:** users.*, roles.*, permissions.* (12)

### Ownership Rules (Policies)

- **EventPolicy:** Clients can only update own events in [pending, confirmed, published] status; only clients can publish their events; delete limited to [pending, published]
- **BookingPolicy:** Suppliers can update if supplier_id matches and status in [requested, confirmed]; same for clients
- **MessagePolicy:** Users can only view messages where they are sender or recipient
- Admin bypasses all policy restrictions via `Gate::before`

---

## Routes (Dual API + UI)

### REST API (JSON responses)
- `GET/POST /events`, `GET/PATCH/DELETE /events/{event}`
- `POST /events/{event}/publish`, `GET /events/{event}/details`
- `GET/POST /bookings`, `GET/PATCH /bookings/{booking}`
- `GET/POST /messages`, `GET /messages/{message}`

### Dashboard UI (Blade views)
- `/app/events`, `/app/bookings`, `/app/messages`, `/app/agreement-log`
- `/app/users`, `/app/roles`, `/app/permissions`
- Each route protected by `permission:` middleware

---

## Frontend

- **Dashboard:** NobleUI template with sidebar navigation, theme switcher, notifications
- **Landing page:** Tailwind CSS responsive design
- **Auth pages:** Bootstrap-styled login, register, password reset
- **Build:** Vite 7 + laravel-vite-plugin, Sass, Bootstrap 5, Tailwind CSS 4
- **Interactive features:** Modal forms for CRUD operations, source filtering, pagination

---

## Testing (4 feature test files + 2 basic tests)

| Test File | Coverage |
|-----------|----------|
| EventPolicyTest | Client/supplier create permissions, cross-client visibility, admin delete |
| EventBookingMessageFlowTest | Full lifecycle: event→publish→booking→message, message isolation, agreement log |
| RolesPermissionsCrudTest | Admin CRUD for permissions/roles/users, client access denial |
| UserAccessUiTest | Admin user management page access, role/permission assignment |

Run: `php artisan test` or `composer test`

---

## Key Observations

### Strengths

1. **Clean architecture** — Domain layer properly separated from HTTP/persistence layers with service contracts and DTOs
2. **Comprehensive RBAC** — Fine-grained 27-permission system with policy-level ownership checks
3. **Immutable audit trail** — Agreement log and insert-only messages ensure data integrity
4. **Future-ready** — Source tracking (user/ai/system) prepares for AI integration without schema changes
5. **Dual interface** — Both REST API and Blade UI serve different integration needs
6. **Event-driven** — Business events (UserRegistered, MessageInserted) allow extensible side effects
7. **Well-documented** — 7 architecture/design docs covering schema, roles, ownership, and testing

### Areas for Improvement

1. **Test coverage** — Only 4 feature tests; no unit tests for services, no edge case coverage for controllers or domain logic
2. **No API authentication** — REST API uses session auth only; no token-based auth (Sanctum/Passport) for external consumers
3. **No validation Form Requests** — Validation is inline in controllers rather than extracted to FormRequest classes
4. **Missing event deletion cascade** — Deleting an event cascades to bookings/messages at DB level, but no domain event is fired for cleanup
5. **No pagination on dashboard stats** — The main dashboard queries could become expensive with large datasets
6. **Environment mismatch** — .env.example defaults to MySQL but config/database.php defaults to SQLite; could confuse setup
7. **No API versioning** — REST endpoints aren't versioned (e.g., `/api/v1/events`), which could be an issue for future consumers
8. **Supplier auto-assignment** — Booking controller copies supplier_id from the event if not provided, but there's no validation that the supplier exists or is active
9. **No rate limiting** — No throttling on API or form endpoints beyond Laravel defaults
10. **Frontend dependencies mixed** — Both Bootstrap 5 and Tailwind CSS 4 are present; the welcome page uses Tailwind while the dashboard uses Bootstrap/NobleUI

---

## Seeded Users

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Client | client@example.com | password |
| Supplier | supplier@example.com | password |

New registrations automatically receive the **Client** role.

---

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
composer run dev
```

---

*Analysis generated on March 9, 2026*
