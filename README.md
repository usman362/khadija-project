# Khadija Project Backend

Laravel 12 backend foundation with:
- Domain-driven service wiring (`Domain / Services / Events`)
- Session auth baseline with role-ready authorization
- Local + staging environment templates
- High-level DB architecture docs

## Milestone 1 status
- Clean backend foundation: done
- Architecture locked: done

## Quick start
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
composer run dev
```

## Architecture docs
- `docs/architecture.md`
- `docs/db-high-level-design.md`

## Roles & middleware
- Seeded roles: `admin`, `manager`, `staff`, `customer`
- Route protection middleware alias:
```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    // protected routes
});
```
