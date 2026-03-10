# AI Agreements Module Setup Guide

## Run Migrations

```bash
php artisan migrate
```

This creates the `agreements` table with fields for booking linkage, AI-generated content, dual-party acceptance tracking, versioning, and audit metadata.

## Seed Permissions

```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RolePermissionSeeder
```

New permissions added:
- `agreements.view_any` — browse agreements (Client, Supplier, Admin)
- `agreements.generate` — generate AI agreements (Client, Supplier, Admin)
- `agreements.accept` — accept/reject agreements (Client, Supplier, Admin)

## Configure OpenAI (Optional)

Add to your `.env` file:

```
OPENAI_API_KEY=sk-your-api-key-here
OPENAI_MODEL=gpt-4o
```

If no API key is configured, the system generates professional template-based agreements as a fallback.

## Routes

| URL | Method | Controller | Description |
|-----|--------|-----------|-------------|
| `/app/agreements` | GET | AgreementPageController@index | Browse agreements |
| `/app/agreements/{agreement}` | GET | AgreementPageController@show | View agreement details |
| `/app/agreements/generate/{booking}` | POST | AgreementPageController@generate | Generate AI agreement |
| `/app/agreements/{agreement}/accept` | POST | AgreementPageController@accept | Accept agreement |
| `/app/agreements/{agreement}/reject` | POST | AgreementPageController@reject | Reject agreement |
| `/app/agreements/regenerate/{booking}` | POST | AgreementPageController@regenerate | Regenerate new version |

## How It Works

1. **Client and Supplier chat** about a booking through the messenger
2. Either party clicks **"AI Agreement"** on the bookings page
3. The system reads the chat history and generates a professional service agreement
4. Both parties **review** the extracted terms and full agreement content
5. **Client accepts** and **Supplier accepts** (order doesn't matter)
6. Once both accept → agreement becomes **Fully Accepted** → booking auto-confirms
7. If rejected, either party can **regenerate** a new version

## Agreement Status Flow

```
draft → pending_review → client_accepted ─┐
                       → supplier_accepted ─┤→ fully_accepted
                       → rejected (can regenerate)
```

## Sidebar

New sidebar item: **AI Agreements** (file-signature icon) — visible to all roles with `agreements.view_any`

## Run Tests

```bash
php artisan test --filter=AgreementTest
```

## What Changed

### New Files
- `database/migrations/2026_03_10_200001_create_agreements_table.php`
- `app/Models/Agreement.php`
- `app/Policies/AgreementPolicy.php`
- `app/Domain/Agreements/Services/AgreementGeneratorService.php`
- `app/Http/Controllers/Dashboard/AgreementPageController.php`
- `resources/views/dashboard/agreements/index.blade.php`
- `resources/views/dashboard/agreements/show.blade.php`
- `tests/Feature/AgreementTest.php`

### Modified Files
- `app/Models/Booking.php` — added agreements(), latestAgreement(), activeAgreement() relationships
- `app/Providers/AppServiceProvider.php` — registered AgreementPolicy
- `database/seeders/PermissionSeeder.php` — added 3 agreement permissions
- `database/seeders/RolePermissionSeeder.php` — granted all agreement permissions to Client/Supplier
- `routes/web.php` — added 6 agreement routes
- `resources/views/layouts/dashboard.blade.php` — added AI Agreements sidebar link
- `resources/views/dashboard/bookings/index.blade.php` — added "AI Agreement" button per booking
- `resources/views/landing.blade.php` — added AI Agreement showcase section
- `config/services.php` — added OpenAI configuration
- `.env.example` — added OPENAI_API_KEY and OPENAI_MODEL
