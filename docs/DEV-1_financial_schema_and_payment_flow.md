# DEV-1 — Financial DB schema and payment-state flow

**For:** PM / Owner review · **From:** Usman (Developer) · **Date:** 2026-09-03

Submitted for approval under DEV-1 (B-1 and B-2). Sections 6 items G-1 to G-13
are blocked until this is reviewed.

Everything below is written against the schema that exists today, not a design
on paper. Where a thing already exists it says so; where it does not, it is
marked **NEW** and the reason is given.

---

## 1 — What already exists

| Table | Rows carry | Status |
|---|---|---|
| `bookings` | event, client, supplier, status, **price** (the agreed figure), currency, booked_at | exists |
| `finalizations` | agreed_price, deposit_amount, both parties' signatures, per-booking terms | exists |
| `payments` | user, gateway, status, amount, currency, payment_method, gateway_session_id, gateway_payment_id, metadata, failure_reason, completed_at | exists |
| `payouts` | user, amount, currency, method, status, reference, requested_at, paid_at | exists |
| `held_fund_entries` | booking, event, **kind**, **direction**, amount, state, processor_reference, **reverses**, occurred_at, recorded_by | exists, unused |
| `event_extensions` | the Live Event Upgrade record, with a status | exists |
| `cancellation_requests` | the cancellation and its quoted refund | exists |

`held_fund_entries` is the important one. It is already an append-only money
ledger: `direction` in/out, `kind` deposit / balance / release / refund /
commission / adjustment, `state` pending/settled, and `reverses` pointing at
the entry a reversal undoes. **It has never been written to.** Most of what
G-1 to G-13 asks for is this table being used rather than a new design.

---

## 2 — What is missing (NEW)

### 2.1 `bookings.work_authorized_at` — timestamp, nullable  *(G-2)*

The gate for a professional starting work. Set only inside the atomic Payment
Secured transaction (§3). Both the UI and the server-side API must refuse work
commencement while it is null; a UI-only check is not a gate.

### 2.2 `bookings.commission_rate` — decimal(4,2), nullable  *(G-11)*

The professional's tier rate **snapshotted at capture**, not looked up later.
A professional who moves from Pro to Elite must not retroactively change what
was taken from a booking that already settled. Null until capture.

### 2.3 `live_event_timeline_entries` — NEW table  *(G-3)*

Append-only. No UPDATE, no DELETE, ever.

| column | type | note |
|---|---|---|
| id | bigint pk | |
| event_id | fk → events | |
| booking_id | fk → bookings, nullable | |
| actor_id | fk → users, nullable | null = system |
| kind | string(40) | upgrade_requested, upgrade_approved, payment_secured, work_authorized, extended, expired … |
| payload | json | what changed |
| occurred_at | timestamp | |
| created_at | timestamp | |

Enforced in the application layer, and at the database layer with a trigger
that raises on UPDATE or DELETE. Application-only enforcement is one careless
`->update()` away from being untrue.

### 2.4 `payment_intents` — NEW table  *(G-4, G-5, G-6)*

The server's own record of a payment attempt, separate from `payments`, which
records money that moved.

| column | type | note |
|---|---|---|
| id | bigint pk | |
| booking_id | fk → bookings | |
| upgrade_id | fk → event_extensions, nullable | |
| **idempotency_key** | string(64) **unique** | `create_pi_{id}` / `capture_pi_{id}` / `cancel_pi_{id}` / `cancel_grace_{id}`, v4 UUID suffix |
| provider | string(20) | stripe |
| provider_intent_id | string(80), nullable | |
| state | string(24) | see §3 |
| amount_authorized | decimal(10,2) | |
| amount_captured | decimal(10,2), nullable | |
| authorized_at / captured_at / expires_at / cancelled_at | timestamps, nullable | `expires_at` = authorized_at + 7 days |
| attempts | unsigned tinyint, default 0 | the 3-attempt limit |
| last_error | text, nullable | |

The unique index on `idempotency_key` is what makes a retried webhook or a
double-clicked button harmless: the second insert fails, and failing is the
correct outcome.

### 2.5 `webhook_deliveries` — NEW table  *(G-4)*

| column | type |
|---|---|
| id, provider, provider_event_id (**unique**), type, payload json, signature_verified bool, received_at, processed_at nullable, attempts, last_error |

Stripe retries. Without a unique provider event id, a retry processes twice.

### 2.6 Reimbursements  *(G-12)*

**No new table.** A reimbursement is a `held_fund_entries` row with
`kind = adjustment`, `direction = out`. It is never added to
`bookings.price`, so `approved_booking_total` cannot absorb it, and it appears
as its own line on both dashboards because it is its own row.

---

## 3 — Payment-state flow

States on `payment_intents.state`:

```
draft → authorizing → authorized → capturing → captured
                 ↓            ↓           ↓
              failed      expired     capture_failed
                              ↓
                          cancelled
```

**Authorization.** Client confirms. One `payment_intents` row is created with
`create_pi_{uuid}`. Card details are entered on the provider's hosted page and
never reach this server. On success: `state=authorized`, `authorized_at` set,
`expires_at = authorized_at + 7 days`.

**Retries.** `attempts` increments on each failure. At **3** the intent moves
to `failed` and no further attempt is accepted for that booking without an
Admin action. *(The suspension process itself is #8 — a PM decision, not built.)*

**Expiry (G-6).** A background job selects due intents `FOR UPDATE`. If the
row's state is no longer `authorized` when the lock is acquired, it rolls back
and exits — so running the job twice is the same as running it once. An
expired hold moves to `expired`; the booking returns to unsecured and the
client is told, rather than the hold lapsing silently.

**Capture / Payment Secured (G-1).** One database transaction, all-or-none:

1. amendment `status = effective`
2. `bookings.price` updated to the approved total
3. `bookings.work_authorized_at = now()`
4. `bookings.commission_rate` = the professional's tier rate **as of now**
5. `payment_intents.state = captured`, `amount_captured`, `captured_at`
6. `held_fund_entries`: `in` / `deposit` or `balance`; `out` / `commission`
7. `live_event_timeline_entries`: `payment_secured`, then `work_authorized`

Partial application is not permitted. If any step raises, none of it happened.

**Webhooks (G-4).** The endpoint verifies the Stripe signature, writes a
`webhook_deliveries` row, and returns 2xx **within 5 seconds**. All processing
is queued. A duplicate `provider_event_id` returns 2xx and does nothing.

**Payout.** After capture and after the event completes, a `payouts` row is
raised for `amount_captured` minus the commission taken at step 6 — read from
`bookings.commission_rate`, never recalculated from the professional's tier
today.

---

## 4 — Three answers needed before any of this is built

1. **D-11 — provider.** Every record here says Stripe; the PM answer names
   Square. The code has a Stripe gateway and a PayPal gateway and no Square
   gateway at all. This document assumes Stripe. If Square is the decision,
   §2.4 and §3 change.
2. **#8 — what suspension after 3 failed attempts actually means.** The limit
   is buildable now; the process is not defined.
3. **D-3 / G-13 — the $2.99 fee.** Recorded as its own `payments` row with
   `metadata.kind = client_request_fee`, separate from the deposit, and the
   answer is non-refundable in every case. Confirming that here means the
   ledger can be written once and not revisited.

---

## 5 — Migration order, once approved

1. `bookings.work_authorized_at`, `bookings.commission_rate`
2. `live_event_timeline_entries` + the UPDATE/DELETE trigger
3. `payment_intents`, `webhook_deliveries`
4. Backfill: nothing. Existing bookings get null, which reads as "not
   authorized under this flow", which is true of every one of them.
