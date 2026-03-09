# Roles & Permissions (Spatie)

This project uses `spatie/laravel-permission` for role and permission management.

## Roles
- `admin`
- `client`
- `supplier`

## Permission set
- `dashboard.view`
- `events.view_any`, `events.view`, `events.create`, `events.update`, `events.delete`, `events.publish`
- `bookings.view_any`, `bookings.view`, `bookings.create`, `bookings.update`
- `messages.view_any`, `messages.view`, `messages.create`
- `agreement_log.view_any` (view immutable agreement/booking state change log)

## Seed setup
Roles and permissions are seeded in:
- `database/seeders/RolesTableSeeder.php`

Default users are assigned roles in:
- `database/seeders/DatabaseSeeder.php`

## Notes
- Messages are insert-only by design (no edit/delete endpoints).
- UI for Events/Bookings uses Add/Edit modals over full-width tables.
