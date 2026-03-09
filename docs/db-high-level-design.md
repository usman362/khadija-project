# High-Level DB Design (Milestone 1)

## Core tables
- `users`
  - Laravel auth identity table
  - Key fields: `id`, `name`, `email`, `password`, timestamps
- `events`
  - Key fields: `id`, `title`, `description`, `status`, `is_published`, `published_at`, `starts_at`, `ends_at`, `created_by`, `client_id`, `supplier_id`, `source`, timestamps
  - `source`: `user` | `ai` | `system` (ownership; default `user`)
  - Indexes: `client_id`+`status`, `supplier_id`+`status`
- `bookings`
  - Key fields: `id`, `event_id`, `client_id`, `supplier_id`, `created_by`, `status`, `notes`, `booked_at`, `source`, timestamps
  - `source`: `user` | `ai` | `system` (default `user`)
  - Status: requested, confirmed, cancelled, completed
  - Indexes: `event_id`+`status`, `client_id`+`status`, `supplier_id`+`status`
- `messages`
  - Insert-only (no `updated_at`)
  - Key fields: `id`, `event_id`, `booking_id`, `sender_id`, `recipient_id`, `body`, `source`, `created_at`
  - `source`: `user` | `ai` | `system` (default `user`)
  - Indexes: `event_id`+`created_at`, `booking_id`+`created_at`
- `agreement_log`
  - Append-only (no `updated_at`). Immutable log of agreement/booking state changes.
  - Key fields: `id`, `subject_type`, `subject_id`, `from_status`, `to_status`, `changed_by`, `notes`, `created_at`
  - Index: `subject_type`, `subject_id`, `created_at`

## Spatie Permission tables
- `roles`: role catalog (`admin`, `client`, `supplier`); guard `web`
- `permissions`: permission names (e.g. `events.view_any`, `bookings.create`)
- `model_has_roles`, `role_has_permissions`, `model_has_permissions`: many-to-many mappings

## Supporting Laravel tables
- `password_reset_tokens`
- `sessions`
- `jobs`, `job_batches`, `failed_jobs`
- `cache`, `cache_locks`

## Access model
- One user can hold multiple roles (Spatie). Authorization: permission checks on routes + policy checks on models (Event, Booking, Message).
- Event/booking/message visibility: admin sees all; client/supplier see only where they are client_id or supplier_id (or sender/recipient for messages).
