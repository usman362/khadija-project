# GigResource — Button Style Guide

> Developer Feedback v1.1 §4.2. Peter's note: _"I wonder if we should have standardized
> colors for the buttons… but allow the sizes to vary."_
>
> **The rule:** button **colour is fixed by role context**; button **size varies by
> importance** (primary CTA → large, secondary action → small). Every page must follow this
> so a user can tell their role context from the button colour alone.

---

## 1. Role-based colour palette

The brand colour is decided by **where the button lives**, not by the developer's taste.

| Context | Brand colour | Hex | CSS variable (in that layout) |
|---|---|---|---|
| **Client** dashboard | Orange | `#f97316` | `var(--accent-orange)` |
| **Professional** dashboard | Blue | `#2563eb` | `var(--accent-blue)` |
| **Public** site (logged-out) | Orange = client action, Blue = professional action | `#f97316` / `#2563eb` | `var(--orange)` / `var(--blue)` |
| **Admin** dashboard | Indigo | `#6366f1` | `var(--accent-blue)` |

**Always reference the CSS variable, never hard-code the hex in new components.** The
variable already resolves correctly for light and dark mode in each layout.

### Shared semantic colours (same in every layout)

These override the role colour **only** when the meaning is universal:

| Meaning | Colour | Hex | Variable |
|---|---|---|---|
| Success / confirm / paid | Green | `#10b981` | `var(--accent-green)` |
| Warning / pending | Amber | `#f59e0b` | `var(--accent-yellow)` |
| Danger / delete / cancel | Red | `#ef4444` | — (use `#ef4444`) |

> A "Delete account" button is **red on both dashboards** — destructive intent beats role
> colour. A "Save" or "Submit" button uses the **role colour** (orange for client, blue for
> professional).

---

## 2. Variants

| Variant | Use for | Fill | Text |
|---|---|---|---|
| **Primary** | The single most important action on the view (Save, Create, Send) | Solid role colour | White |
| **Secondary / Outline** | Supporting actions next to a primary (Cancel, Back) | Transparent, 1px border | Role colour or muted |
| **Ghost / Text** | Low-emphasis inline actions (Edit, View all) | None | Muted → role colour on hover |
| **Danger** | Irreversible/destructive actions | Solid `#ef4444` (or red outline) | White |
| **Success** | Positive confirmations (Mark as paid, Approve) | Solid `var(--accent-green)` | White |

**One primary button per view.** If two actions look equally important, one of them is
actually secondary — demote it to outline.

---

## 3. Size scale (this is what's allowed to vary)

| Size | Padding | Font size | Use |
|---|---|---|---|
| **Large** (`-lg`) | `13px 28px` | `15px` | Hero CTAs, primary form submit |
| **Medium** (default) | `10px 24px` | `14px` | Standard buttons, most actions |
| **Small** (`-sm`) | `7px 14px` | `13px` | Inline/table actions, toolbar buttons |

Shared across all sizes:
- `font-weight: 600` (public site uses `700`)
- `border-radius: var(--radius-sm)` in dashboards (`10px` on public)
- `display: inline-flex; align-items: center; gap: 8px;` (so an icon + label line up)
- `cursor: pointer;` and `transition: var(--transition);`

---

## 4. States

| State | Style |
|---|---|
| **Hover** | `opacity: 0.9; transform: translateY(-1px);` (solid) — or border/colour shift (outline) |
| **Active** | `transform: translateY(1px);` |
| **Disabled** | `opacity: 0.5; cursor: not-allowed;` — no hover transform |
| **Focus** | Visible focus ring: `box-shadow: 0 0 0 3px <role-colour at 15% alpha>;` (WCAG 2.4.7) |

---

## 5. Canonical CSS (the standard to adopt going forward)

New components should use this pattern. It anchors on the layout's role variable, so the
**same markup is orange on the client side and blue on the professional side** with zero
extra code.

```css
/* Base — shared by every button */
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 24px;                 /* medium / default */
    font-size: 14px; font-weight: 600;
    border: 1px solid transparent;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
}
.btn:active { transform: translateY(1px); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

/* Brand var is set ONCE per layout:
   client.blade.php      → :root { --btn-brand: var(--accent-orange); }
   professional.blade.php → :root { --btn-brand: var(--accent-blue); } */

/* Primary — solid role colour */
.btn-primary { background: var(--btn-brand); color: #fff; }
.btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }

/* Secondary — outline in the role colour */
.btn-outline { background: transparent; border-color: var(--border-color); color: var(--text-primary); }
.btn-outline:hover { border-color: var(--btn-brand); color: var(--btn-brand); }

/* Ghost — text only */
.btn-ghost { background: transparent; color: var(--text-muted); padding-left: 8px; padding-right: 8px; }
.btn-ghost:hover { color: var(--btn-brand); }

/* Danger — destructive, overrides role colour */
.btn-danger  { background: #ef4444; color: #fff; }
.btn-success { background: var(--accent-green); color: #fff; }

/* Size modifiers (colour stays fixed, size varies) */
.btn-lg { padding: 13px 28px; font-size: 15px; }
.btn-sm { padding: 7px 14px;  font-size: 13px; }

.btn:focus-visible { outline: none; box-shadow: 0 0 0 3px color-mix(in srgb, var(--btn-brand) 25%, transparent); }
```

```html
<!-- Same markup, different colour per dashboard -->
<button class="btn btn-primary btn-lg">Save changes</button>
<button class="btn btn-outline">Cancel</button>
<button class="btn btn-danger btn-sm">Delete</button>
```

---

## 6. Existing button classes → variant map

These component-scoped classes already exist. New work should fold into the `.btn` system
above; until then, this is what each maps to so they stay colour-consistent:

| Class | Where | Maps to |
|---|---|---|
| `.form-submit.client-submit` | auth/register | Primary (orange) `-lg` |
| `.form-submit.pro-submit` | auth/register | Primary (blue) `-lg` |
| `.pf-btn` | client/professional profile | Primary (role colour) |
| `.np-btn` | notification preferences | Primary (role colour) |
| `.lp-btn-orange` | public site | Primary (orange, gradient) |
| `.lp-btn-blue` | public site | Primary (blue, gradient) |
| `.lp-btn-outline` | public site | Secondary / Outline |

---

## 7. Checklist before shipping a page

- [ ] Every solid button uses the **role colour** for its dashboard (orange = client, blue = professional).
- [ ] Exactly **one primary** button per view; supporting actions are outline/ghost.
- [ ] Destructive actions are **red**, regardless of role.
- [ ] Size reflects importance — don't make a minor action a large button.
- [ ] Colour comes from a **CSS variable**, not a hard-coded hex.
- [ ] Button has a visible **focus ring** for keyboard users.
