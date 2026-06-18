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
- `docs/button-style-guide.md` — role-based button colours (client = orange, professional = blue), variants & sizes
- `docs/ai-tools-by-tier.md` — which AI tools unlock at each membership tier + the `AI_FEATURES_FREE_FOR_ALL` launch switch
- `docs/address-verification.md` — risk-based address verification + hybrid geolocation (free filter live; paid provider go-live-gated)

## Roles & middleware
- Seeded roles: `admin`, `manager`, `staff`, `customer`
- Route protection middleware alias:
```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    // protected routes
});
```
