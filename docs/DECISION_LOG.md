# Project Decision & Roadmap

Single source of truth for what has been decided, what is built, and what is
still waiting on someone. Proposed by Khadijah (2026-07-31) so the same topics
stop being re-opened across chats.

**Owner:** Khadijah (Project Manager) — updates this after each meeting.
**Seeded:** 2026-07-31 by Ali, from the code and the 36-rule master, not from memory.

## How to read it

| Status | Means |
|---|---|
| **Built** | In the code and verified against it. Safe to demo. |
| **Approved — not built** | Decided, nobody is blocked, work not started. |
| **In progress** | Being built now. |
| **Under review** | Proposed, no decision yet. |
| **Blocked** | Cannot start until someone answers the question in Notes. |

One rule for this file to stay useful: **status comes from the code, not from
the chat.** Several items below were agreed weeks ago and never built; two were
built and then quietly half-undone. That is the gap this document exists to close.

---

## 1. Blocked — waiting on Sir Peter

These are the only things stopping work. Everything else can proceed.

| # | Item | Priority | Question that unblocks it |
|---|---|---|---|
| B1 | Client request fee — when it is charged | High | $2.99 upfront when the request is posted, or at finalization? Code currently charges at **finalization** (`config/payments.php`). Changing it later means re-doing refund logic. |
| B2 | Bidding window values (R37) | High | How long does a BSR stay open? How long for an ESR? Nothing is in config, so the Admin page for this cannot be built. |
| B3 | Live Event Upgrades — money rules | High | Four answers needed: how held funds are recorded, whether an overtime rate is mandatory on every bid, geofence rules for on-site upgrades, and the real fee (the "15% vs your standard 10%" in the spec matches neither the 3/5/1.5% commission nor the $2.99 request fee). |
| B4 | AI modules — keep or remove | High | R29 forbids any feature that *claims or uses* AI. The naming sweep is done, but three modules still genuinely call OpenAI: **AI Agreement generation**, the **support chatbot**, and the **"AI-assisted marketplace"** line in the registration disclaimer. This is not a rename — it is keep-or-remove. Sir Peter's own FAC-13 lists "D2 remove-AI" as done, so this may simply have been missed. |
| B5 | Brand button contrast | Low | White text on brand orange `#f97316` is 2.8:1 — below the accessibility standard we already committed to. Needs a darker orange for buttons, or dark text. |

---

## 2. Approved — not built

Decided and unblocked. This is the build queue.

| # | Item | Priority | Notes |
|---|---|---|---|
| A1 | **Money ledger** | **Highest** | `booking_ledger` does not exist. Release Milestone, refunds, disputes and Live Event Upgrades all sit on top of it. Nothing in this group can be built first. |
| A2 | Refunds | High | Needs A1. |
| A3 | Disputes | High | Needs A1 and the 4-step resolution flow already specified. |
| A4 | Change orders | Medium | Needs A1. |
| A5 | **My Gigs + Contracts → one workspace** | Medium | Khadijah 2026-07-31, Sir Peter agreed. Verified: they are genuinely different data (My Gigs reads `Event.supplier_id` = the work; Contracts reads `Booking.supplier_id` = the agreement and the money). Merging is a UI change, not a data change. |
| A6 | **Live Event Upgrades (LEU)** | Medium | Agreed as a **platform-wide feature**, not a request type — available on BSR, ESR, DSR and Packages once an event is Live. Blocked by B3 for the money rules, then needs A1. |
| A8 | **Per-service budgets on a multi-service request** | Under review | Asked of Khadijah 2026-07-31. Bids are already per service and the platform already issues one contract per service (R12). What is missing is a budget per service — the event carries one range — and a board that lists each service as its own row. Three product calls in it: whether the client must split the budget, whether the split must reconcile to the total, and whether N services become N board cards. |
| A7 | Tool → Request: "Post as BSR" | Medium | Clickable prototype exists at `/client/prototype/tool-to-request` (writes nothing). Only the BSR leg is in the first pass. |
| A8 | Restrict marketplace for out-of-area accounts | — | **Done 2026-07-31.** Moved to Built. |
| A9 | Co-Op / Team removal | — | **Done 2026-07-31.** Moved to Built. |

---

## 3. Built

Verified against the code, most recent first. Safe for Sir Peter to screenshot.

| Item | Notes |
|---|---|
| Client inbox brought up to the professional one | Filters, details panel, templates, working attachments. |
| Co-Op / Team removed completely | Decided 15 Jul; the UI went then, the column and 8 stale rows survived until 31 Jul. |
| Multi-Service Requests folded into the Bidding Board | It was the board's `scope=multi` filter as a second page. |
| Expansion waitlist (admin) | Where out-of-area signups are coming from. |
| Service-area gate | Out-of-area accounts register and browse but cannot transact. |
| R29 naming sweep | 51 views. Tools are rules and calculators; only the labels were wrong. |
| Tool → Request prototype | For design review. Writes nothing, marked as a prototype on every screen. |
| Find Professionals — one page | Search and Browse were two pages doing one job. |
| Request types: BSR / ESR / DSR, with SSR/MSR as scope | 5 cards → 4, both sides. |
| Registration: everyone signs up, eligibility comes after | Sir Peter's rule, 30 Jul. |
| R10 client fee actually collected | It was displayed in three places and charged nowhere. |
| Homepage editable by an admin | Words, images and video, without a developer. |
| Bookings module on real data | |
| BSR wizard · bid builder · proposal comparison · finalization | |
| Sealed bids (R8) | |
| 7-state geo restriction (R9/R26) | |

---

## 4. Where the chat record and the code disagreed

Kept as a list because it is the argument for this document existing.

| What was believed | What was true |
|---|---|
| "Co-Op removed platform-wide" (15 Jul) | UI removed; the column and 8 rows naming partners survived to 31 Jul. |
| "D2 remove-AI — done" (FAC-13) | Three modules still call OpenAI. See B4. |
| Client request fee is live | Displayed in three places, charged nowhere, until 30 Jul. |
| Demo professionals are in the launch states | All 10 were outside them; a column default had filed every account as out-of-area. |
| The Tools page cannot group by suite | It could — `AiToolCatalog::suites()` already existed. (My error, corrected.) |
| Direct Offer is limited to one service | A6 caps at one professional **per service**; the form already accepted several. |

---

## 5. Locked rules — do not re-open without Sir Peter

From `GIGRESOURCE_RULES_MASTER.docx` (36 rules). These are settled; changing one
is a decision, not a preference.

- **R29** — No AI anywhere, client-, professional- or influencer-facing. Every "smart", "recommended" or "matched" feature must be fixed, documented, rules-based logic.
- **R8** — Sealed bids. No bid amount or outcome aggregate leaks to competing professionals.
- **R10** — Fee model. Commission is paid by the professional at payout: Starter 5% / Professional 3% / Elite 1.5%.
- **A6** — Direct Offer: one professional per service, multiple services allowed.
- **A16** — Tools keep plain names, no "AI"/"IQ" prefix. The suite is "GigResource IQ".
- **Q14** — Badges are Verified Business · ID Verified · Insured · Top Rated · Repeat-Hired. The Escrow badge is retired; the wording is "In Secure Payment".
- **R9 / R26** — Seven jurisdictions only.
- **R12** — One contract per service.
- **R7** — One platform clock.

---

## 6. Changelog

| Date | Change |
|---|---|
| 2026-07-31 | Created and seeded from the code (Ali). Handed to Khadijah to own. |
