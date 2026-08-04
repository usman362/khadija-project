# Category taxonomy V2

Sir Peter's rebuilt category tree, imported alongside the live one so it can be
checked before anything switches.

Source: `02_Trackers/GigResource_Category_Taxonomy_V2_2026-08-02.xlsx` in the
handoff pack, extracted to `database/seeders/data/taxonomy_v2.json`.

## What V2 is

Not a rename of the current tree — a different shape.

| Tier | Count | What it is |
|---|---|---|
| Event Types | 106 | what the client is hosting — Wedding, Christmas Party |
| Service Categories | 27 | what they browse next — Catering, DJs, Photography |
| Services | 241 | the bookable thing — Buffet Catering, Wedding DJs |

A Service Category is **not** owned by one Event Type; nearly every event needs
catering. What connects them is the **archetype**: each Event Type belongs to
one of 13, and each archetype marks every Service Category Essential, Common or
Occasional. That is the `category_relevance` table (139 rows).

51 of the 241 services carry a `cross_fit_alt` — a second category they could
just as reasonably sit under.

## Two trees, one table

`categories.taxonomy_version` is `v1` (the 360 live rows) or `v2` (374 new).
A **global scope** on the model filters every query to `config('taxonomy.version')`.

It is a global scope rather than a query scope deliberately: of roughly fifty
places that query categories, a third never call `->active()`, so relying on
call sites being updated is how the other tree would leak onto a live page.

To see across both — only the import and switch commands should —
use `Category::anyTaxonomy()`.

## Commands

```bash
php artisan taxonomy:import-v2            # build/refresh the v2 tree
php artisan taxonomy:import-v2 --prune    # also delete v2 rows the sheet dropped
php artisan taxonomy:switch --check       # what still points at the old tree
php artisan taxonomy:switch --remap       # re-home those links, then re-check
```

Import is idempotent — it matches on slug within v2, so re-running after the
sheet changes updates in place. Nothing it does touches v1.

## Going live

The blocker is not the categories, it is what points at them: **156**
professional links, plus events, packages and bids. Switching without re-homing
those leaves professionals listed under nothing.

`taxonomy:switch` refuses while any remain, so the order is:

1. `taxonomy:import-v2`
2. Check `database/seeders/data/taxonomy_v1_to_v2_map.json` — V2 was written
   from scratch and reuses none of the old titles, so only 2 of 26 matched by
   name. The rest are proposed there, with two genuine judgement calls listed
   under `_uncertain` (chiefly **Music & Entertainment**, the largest group at
   24 professionals — musicians or performers?).
3. `taxonomy:switch --remap`
4. `TAXONOMY_VERSION=v2` in `.env`, then `php artisan config:clear`

Rolling back is setting it to `v1` again — v1 rows are never deleted.

## Approval

Khadijah approved the design on 2026-08-04, and flagged in the same note that
per R45 this is a design deliverable "pending Peter's review/approval (not
PM's), given how many direct pivots Peter personally drove on this." The
checklist row is still assigned to PM.

Everything above is therefore built but **not switched on**. If Sir Peter
revises the sheet, re-export it to `taxonomy_v2.json` and re-run the import —
names are data, and the structure does not change with them.
