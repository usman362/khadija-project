# Testing the Dashboard

## Why some modules are hidden

The sidebar only shows links you have **permission** to use. Your role decides which permissions you have.

| Role    | Seeded user            | Password | Sidebar: what you see |
|---------|------------------------|----------|------------------------|
| **Admin**   | admin@example.com     | password | **All:** Dashboard, Events, Bookings, Messages, Agreement Log, **Users**, **Roles**, **Permissions** |
| **Client**  | client@example.com    | password | Dashboard, Events, Bookings, Messages, Agreement Log *(no Users/Roles/Permissions)* |
| **Supplier**| supplier@example.com  | password | Same as Client |

So:

- If you are logged in as **Client** or **Supplier**, you will **not** see **Users**, **Roles**, or **Permissions** in the sidebar. That is by design.
- To see **all** modules (including Users, Roles, Permissions), log in as **Admin**.

---

## 1. Fresh setup (see all modules when testing)

From the project root:

```bash
# Install deps (if needed)
composer install

# Env and key
cp .env.example .env
php artisan key:generate

# DB: migrate and seed (creates roles, permissions, and test users; resets permission cache)
php artisan migrate --seed
```

Log in at `/login` with:

- **Email:** `admin@example.com`  
- **Password:** `password`  

You should see the full sidebar: Dashboard, Events, Bookings, Messages, Agreement Log, Users, Roles, Permissions.

---

## 2. If you already have the app running but still don’t see some modules

1. **Re-seed** (this also resets the permission cache)

   ```bash
   php artisan db:seed
   ```

2. **Log in as Admin**

   - Email: `admin@example.com`  
   - Password: `password`  

3. **Confirm you’re on the right account**

   - Check the profile dropdown (top right): it shows the current user name.
   - Only the **Admin** user sees Users, Roles, and Permissions.

---

## 3. Test users (after seeding)

| Email              | Password | Role    | Use for |
|--------------------|----------|---------|--------|
| admin@example.com  | password | Admin   | Full access; all sidebar modules. |
| client@example.com | password | Client  | Events, Bookings, Messages, Agreement Log only. |
| supplier@example.com | password | Supplier | Same as Client. |

New **registrations** get the **Client** role, so they also won’t see Users, Roles, or Permissions.

---

## 4. Quick checklist

- [ ] Ran `php artisan migrate --seed`
- [ ] Logged in with **admin@example.com** / **password**
- [ ] Sidebar shows: Dashboard, Events, Bookings, Messages, Agreement Log, Users, Roles, Permissions

If all are done and a module is still missing, run `php artisan permission:cache-reset` and log in again. You can also check `database/seeders/RolePermissionSeeder.php` and `database/seeders/PermissionSeeder.php` to confirm the permission exists and is assigned to the admin role.
