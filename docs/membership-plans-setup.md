# Membership Plans Module Setup Guide

## Run Migrations

```bash
php artisan migrate
```

This creates:
- `membership_plans` table — plan definitions with pricing, limits, and display settings
- `plan_features` table — feature list items for each plan
- `user_subscriptions` table — tracks user plan subscriptions with status and expiry

## Seed Permissions

```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RolePermissionSeeder
```

New permissions added:
- `membership_plans.view_any` — browse plans (Client, Supplier, Admin)
- `membership_plans.subscribe` — subscribe/cancel plans (Client, Supplier, Admin)
- `membership_plans.create` — create plans (Admin only)
- `membership_plans.update` — edit plans (Admin only)
- `membership_plans.delete` — delete plans (Admin only)

## Seed Default Plans

```bash
php artisan db:seed --class=MembershipPlanSeeder
```

Seeds 3 plans: Starter (free), Professional ($29.99/mo), Enterprise ($99.99/mo).

## Routes

| URL | Method | Controller | Description |
|-----|--------|-----------|-------------|
| `/app/membership-plans` | GET | MembershipPlanPageController@index | Browse plans (card view) |
| `/app/membership-plans/{plan}/subscribe` | POST | MembershipPlanPageController@subscribe | Subscribe to a plan |
| `/app/membership-plans/cancel` | POST | MembershipPlanPageController@cancel | Cancel active subscription |
| `/app/membership-plans/history` | GET | MembershipPlanPageController@history | Subscription history |
| `/app/admin/membership-plans` | GET | AdminMembershipPlanController@index | Admin: manage plans |
| `/app/admin/membership-plans` | POST | AdminMembershipPlanController@store | Admin: create plan |
| `/app/admin/membership-plans/{plan}` | PATCH | AdminMembershipPlanController@update | Admin: update plan |
| `/app/admin/membership-plans/{plan}` | DELETE | AdminMembershipPlanController@destroy | Admin: delete plan |

## Sidebar

Two new sidebar items:
- **Membership Plans** (crown icon) — visible to all roles with `membership_plans.view_any`
- **Manage Plans** (settings icon) — visible only to Admin (has `membership_plans.create`)

## Run Tests

```bash
php artisan test --filter=MembershipPlanTest
```

## What Changed

### New Files
- `database/migrations/2026_03_10_100001_create_membership_plans_table.php`
- `database/migrations/2026_03_10_100002_create_plan_features_table.php`
- `database/migrations/2026_03_10_100003_create_user_subscriptions_table.php`
- `app/Models/MembershipPlan.php`
- `app/Models/PlanFeature.php`
- `app/Models/UserSubscription.php`
- `app/Policies/MembershipPlanPolicy.php`
- `app/Http/Controllers/Dashboard/MembershipPlanPageController.php`
- `app/Http/Controllers/Dashboard/AdminMembershipPlanController.php`
- `resources/views/dashboard/membership-plans/index.blade.php`
- `resources/views/dashboard/membership-plans/history.blade.php`
- `resources/views/dashboard/membership-plans/admin.blade.php`
- `resources/views/dashboard/membership-plans/_form.blade.php`
- `database/seeders/MembershipPlanSeeder.php`
- `tests/Feature/MembershipPlanTest.php`

### Modified Files
- `app/Models/User.php` — added subscriptions relationship and helpers
- `app/Providers/AppServiceProvider.php` — registered MembershipPlanPolicy
- `database/seeders/PermissionSeeder.php` — added 5 membership plan permissions
- `database/seeders/RolePermissionSeeder.php` — granted view_any + subscribe to Client/Supplier
- `routes/web.php` — added membership plan routes
- `resources/views/layouts/dashboard.blade.php` — added sidebar links
