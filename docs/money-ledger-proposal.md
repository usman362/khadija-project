# GigResource — Money-Tracking Model (A1)
**Proposal for Owner + PM review · 2026-08-22 · nothing in this area is built until this is approved**

Peter's instruction (Decision Log, item 7 / A1): before refunds, milestone
payments and payouts are built, agree ONE financial ledger model that tracks
every money event as a **discrete, separately-auditable record** — and never
represent the picture by editing a single booking-total field. This is that
proposal.

---

## 1. The one idea

**A booking total is never stored and edited. It is always the sum of a list of
money events.** Every charge, fee, commission, upgrade, refund and payout is one
append-only row. To correct something you add a reversing row — you never edit or
delete history. The "current total" is a question we answer by adding up the
list, so it can never silently drift from what actually happened.

This is the opposite of the trap Peter named: one `booking.total` field that gets
overwritten, where last month's number is gone and nothing reconciles.

---

## 2. What exists today (and why it is not enough)

- `payments` — a gateway charge, but tied to a subscription (`user_subscription_id`
  is required), so it cannot cleanly record a booking fee, an upgrade, or a refund.
- `payouts` — a professional withdrawal, flat (`amount`, `status`), no link to
  which bookings funded it.
- `Commission` — calculates the 5 / 3 / 1.5 % share, but nothing records it.

There is no place that ties **request fee → booking → commission → payout** as
auditable line items. That is the gap.

---

## 3. Proposed tables

### 3a. `ledger_entries` — the spine (append-only)

One row per money event. Never updated after it settles; corrections are new rows.

| Column | Purpose |
|---|---|
| `id` | |
| `event_id` (nullable) | the request this concerns |
| `booking_id` (nullable) | the booking this concerns |
| `party_user_id` | whose money this entry is about (client, professional, or GigResource) |
| `entry_type` | one of the nine kinds below |
| `amount` | decimal(12,2), always positive |
| `sign` | `+` money in / `-` money out, from GigResource's books |
| `currency` | |
| `state` | `pending` → `secured` → `released` / `refunded` / `failed` |
| `subject_type` / `subject_id` | polymorphic link (Booking, BookingUpgrade, Milestone, Payment, Payout) |
| `payment_id` (nullable) | the gateway charge that backs it |
| `reverses_id` (nullable) | if this row corrects an earlier one |
| `metadata` (json) | provider refs, tax jurisdiction, notes |
| `occurred_at` | |

**`entry_type` values — Peter's nine required records, one each:**
1. `booking_charge` — original booking amount
2. `platform_fee` — client platform fee (incl. the $2.99 request fee)
3. `commission` — GigResource's share (the professional's tier rate)
4. `upgrade` — a mid-event upgrade or change order (each its own row, never merged)
5. `provider_fee` — Stripe/PayPal fee
6. `tax` — applicable tax
7. `refund` / `adjustment` — a reversal or correction
8. `milestone_funding` / `milestone_release` — money parked for, then released to, a stage
9. `payout` — money sent to the professional

### 3b. `booking_upgrades` — each upgrade as its own record (B3 rule)

The Owner confirmed (B3): a mid-event upgrade is an **amendment to the existing
booking**, shown to the client as one running total, but recorded internally as
**separate line items** — never collapsed. Payment must be **secured before** the
upgrade is confirmed.

| Column | Purpose |
|---|---|
| `id`, `booking_id`, `requested_by` | |
| `description`, `amount` | what and how much |
| `commission_rate` | the pro's ordinary tier rate — no special upgrade fee (B3) |
| `status` | `requested` → `approved` → `payment_secured` → `confirmed` (or `rejected`) |
| `secured_payment_id` | the charge that gated confirmation |
| timestamps | |

The client-facing **booking total is derived**: original `booking_charge` +
Σ confirmed `upgrade` rows. There is no `booking.modified_total` field.

### 3c. `milestones` (only when milestone payments are built)

`id`, `booking_id`, `label`, `amount`, `state` (`planned` → `funded` → `released`),
`funded_at`, `released_at`. Each funding and release is also a `ledger_entries` row.

### 3d. `payments` — one small change

Make `user_subscription_id` nullable and add `purpose` (`subscription`,
`request_fee`, `booking`, `upgrade`, `refund`), so a payment can back a ledger
entry that is not a subscription. Existing subscription payments are unaffected.

---

## 4. Payment-state flow (happy path)

```
Client posts a request
        └─ platform_fee entry (pending → secured)      ← the $2.99, see §6
Client finalises a bid
        └─ booking_charge entry (secured; deposit held)
        └─ commission entry (accrued, not yet taken)
Mid-event: pro requests an upgrade
        └─ upgrade row (requested → approved by client)
        └─ payment secured  ──►  upgrade confirmed, booking total recomputed
Work marked complete
        └─ milestone_release / final release
        └─ provider_fee + tax entries recorded
        └─ payout entry (booking + upgrades − commission − fees)
Something goes wrong
        └─ refund entry that REVERSES the specific entries, by reference
```

Money owed, held, earned and paid are all **read** by filtering the ledger by
`state` and `party`. None of them is a stored field anyone overwrites.

---

## 5. How this satisfies the rule

- **Discrete & auditable** — every one of the nine is its own row with its own
  state and its own provider reference.
- **Nothing merged** — upgrades never fold into the booking total; the total is a
  sum, computed on read.
- **Nothing overwritten** — corrections are reversing rows, so the history
  reconciles and an auditor can trace every dollar.

---

## 6. Open questions the Owner still needs to settle

1. **The $2.99 timing (B1).** The Decision Log confirms it is charged at
   **publish**, not at finalisation. But A1 also lists the $2.99 as a ledger item
   to design here first — so the two items overlap. **Recommendation:** build the
   fee as a `platform_fee` ledger entry created at publish, once this model is
   approved. It is intentionally NOT relocated yet, to avoid building money
   records ahead of this review.
2. **Who holds the money** — does GigResource hold funds in escrow (e.g. Stripe
   Connect / separate charges + transfers), or does the provider hold and we only
   record? This decides whether "held" is real custody or a bookkeeping state.
3. **Refund policy** — windows and who can trigger. (Note: §12 dispute deadlines
   are reserved for attorney review; no screen states one, and this model should
   not hard-code one either.)
4. **Payout timing** — on completion, on a schedule, or on request; any minimum
   threshold.
5. **Taxes** — do we compute/collect, or record only? Which jurisdictions in V1.

---

## 7. Build order once approved

1. `ledger_entries` + the read helpers (money owed / held / earned / paid).
2. Backfill the existing request-fee and any finalisation charges as entries.
3. `booking_upgrades` + the "secure before confirm" gate (unblocks B3 billing).
4. Move the $2.99 to publish as a `platform_fee` entry (B1).
5. Refunds (reversing entries), then milestones, then payout reconciliation.

Nothing above is started until the Owner and PM approve this model.
