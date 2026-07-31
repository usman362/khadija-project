# Deploying to production

cPanel host, document root is `public_html` with an `index.php` shim pointing at
`public/`. There is no pipeline — this is the manual sequence.

---

## The short version

```bash
php artisan down
# 1. get the code up (git pull, or upload)
php artisan migrate --force
php artisan permission:cache-reset
php artisan optimize:clear
php artisan up
```

Then, only if the release says so, run the specific seeders listed under
[Seeders](#seeders). **Never run bare `php artisan db:seed`** — see the warning
there.

---

## Three things that will bite

### 1. Code and migrations must land together

Both orders break the site in the gap between them:

* migration first → old code looks for the old shape, database has the new one
* code first → new code looks for the new shape, database has the old one

So `php artisan down` before, `php artisan up` after. It is thirty seconds.

### 2. `permission:cache-reset` is not optional

Spatie caches roles and permissions for **24 hours** (`config/permission.php`).
Any release that touches roles or permissions — the `supplier` → `professional`
rename is one — will look like it did not work until this runs. `optimize:clear`
does **not** cover it; it is a separate cache.

### 3. `public/build` is not in git

The compiled CSS/JS is gitignored, so pulling the code does not update the
assets. Either:

```bash
npm ci && npm run build
```

on the server if node is available, or build locally and upload `public/build`.
Skip it and the site loads with the previous release's styling.

---

## Seeders

**Do not run `php artisan db:seed`.** It calls `DatabaseSeeder`, which includes
`CategorySeeder`, which begins by truncating `category_event` and deleting every
category. That table is what records **which services each event asked for** —
running it on live data destroys that, silently, for every event on the
platform.

`PolicyPageSeeder` is in the same list and uses a plain `create()`, so it
duplicates the policy pages on a second run.

Run seeders one at a time, and only when a release needs one:

| Seeder | When | Safe to re-run |
|---|---|---|
| `PageSectionSeeder` | First deploy of the editable homepage. Without it the admin Website Content page has nothing to edit. | Yes — `updateOrCreate` |
| `PermissionSeeder` · `RolePermissionSeeder` | Only when a permission or role is added. | Yes — `findOrCreate` |
| `MembershipPlanSeeder` | Only when plans or their features change. | Yes — `updateOrCreate` |
| `DemoUsersSeeder` · `DemoProfessionalsSeeder` · `DemoPackagesSeeder` | Demo data only. Fine while the site is pre-launch; **stop once real users exist**. | Mostly |
| `CategorySeeder` | Rebuilding the category tree from scratch, deliberately, on a site with no real events. | **No — destructive, see above** |
| `PolicyPageSeeder` | First install only. | **No — duplicates** |

```bash
php artisan db:seed --class=PageSectionSeeder --force
```

**Order:** always after `migrate`. `RolePermissionSeeder` uses
`Role::findOrCreate('professional')` — run it before the rename migration and it
creates a *second* role next to `supplier`, splitting users across two rows.

---

## After deploying

```bash
php artisan migrate:status | tail -20
```

Everything should read `Ran`. Then check the role rename landed:

```bash
php artisan tinker --execute='foreach (\Spatie\Permission\Models\Role::withCount("users")->get() as $r) echo $r->name." = ".$r->users_count.PHP_EOL;'
```

Expect `professional` with the user count on it, and no `supplier` row at all.

Then open the site as a client and as a professional. Logged-in users keep a
session naming the old role; that is handled — `activeRole()` checks the stored
value against the user's real roles and falls back when it does not match. A
dual-role account lands in client mode and needs one click on *Switch to
Professional*.

---

## Rolling back

```bash
php artisan migrate:rollback --step=1
php artisan permission:cache-reset
```

Revert the code in the same breath. A rolled-back database under new code is the
same broken state as the gap in point 1.

---

## Environment

No new `.env` keys are required. Settings added recently — the bidding windows
in `config/bsr.php` — read from `env()` but carry approved defaults, so they work
untouched. Set them in `.env` only to override:

```
BSR_DEFAULT_WINDOW_DAYS=5
ESR_DEFAULT_WINDOW_HOURS=24
```

If `public/storage` is missing on a fresh server, `php artisan storage:link`.
