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
| ~~B4~~ | ~~AI modules — keep or remove~~ | — | **Resolved 2026-08-22 (evidence-based; awaiting Sir Peter's ratification of the rulebook edit).** The premise was wrong in two ways. (1) It is not three modules — **five** services genuinely POST to `api.openai.com`: AI Agreement generation, the support chatbot, Budget Planner, Best Match, and Review Builder (each with a rules-based fallback when no key is set). (2) The registration disclaimer's **"AI-assisted marketplace"** line is not a leak to remove — it is the honest, required disclosure, and matches Sir Peter's own AI-first direction (AI IQ levels, AI Agreement Builder, and the AI email-answering plan of 2026-08-22). Decision: **KEEP** — the platform is AI-assisted and says so. R29's blanket "no AI" reading is retired (see R29 note below). The FAC-13 "D2 remove-AI done" claim was simply false. No feature removed; the code comments that wrongly called the AI tools "calculators" were corrected. |
| B6 | **A pro awarded two services on one request gets one booking** | High | Not a decision — a defect found while answering A10. `Booking` is keyed on (event, supplier) with no service, so awarding a second service to the same professional on the same request either does nothing (`firstOrCreate` on bid accept) or overwrites the first price (`updateOrCreate` on finalize). Reachable today: multi-service requests already take per-service bids. Needs a decision on whether to fix narrowly now or fold into A10. |
| ~~B5~~ | ~~Brand button contrast~~ | — | **Resolved 2026-08-13.** Sir Peter approved via Ali. Measured worse than first reported — 2.26:1, not 2.8, once read at the light end of the button's gradient. Fixed sitewide with on-white colour variants; `--orange` and `--blue` themselves are untouched. See `docs/contrast-audit.md`. |

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
| A10 | **Each service in a multi-service request as its own gig** | **Approved — Phase 2** | Khadijah, 2026-08-02: own budget, bids, milestones and contract per service; different professionals can win different services in one event. Not to delay current priorities, but recorded now so it is not redesigned later. Of the four: **bids are already per service** (unique on event+supplier+category); **budget, milestones and contract are not**. Milestones need A1. See B6 — the contract half is a live bug, not just a gap. |
| A7 | Tool → Request: "Post as BR" | — | **Phase 1 built 2026-08-13.** Live on Budget Planner, Guided Event Planner and Timeline Builder; the endpoint refuses any fourth tool, so Phase 2 stays a decision rather than a drift. What crosses over is what the CLIENT typed, never the tool's output. The clickable prototype of all five outcomes remains at `/client/prototype/tool-to-request` and still needs Sir Peter's walkthrough. |
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
| R29 naming sweep | 51 views relabelled to hide the word "AI" — but the tools it renamed genuinely call OpenAI, so the sweep hid a real capability rather than a mislabelled one. Reversed in principle by B4 (2026-08-22): the platform is AI-assisted and says so. |
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
| "D2 remove-AI — done" (FAC-13) | False — five services call OpenAI. Resolved by B4 (2026-08-22): AI is KEPT and disclosed, not removed. |
| Client request fee is live | Displayed in three places, charged nowhere, until 30 Jul. |
| Demo professionals are in the launch states | All 10 were outside them; a column default had filed every account as out-of-area. |
| The Tools page cannot group by suite | It could — `AiToolCatalog::suites()` already existed. (My error, corrected.) |
| One contract per service (R12) is implemented | The *rule* is settled; the data model is not. A booking is per event+supplier. My error, 2026-08-02, corrected the same day. |
| Direct Offer is limited to one service | A6 caps at one professional **per service**; the form already accepted several. |

---

## 4b. Decided 2026-08-13

Taken in the Chat thread with Sir Peter and Khadijah on 13 August. Recorded here
because a decision that lives only in a chat scroll is a decision nobody can
find in three weeks.

| Item | Decision | Who |
|---|---|---|
| **Category images — art style** | **Illustrations, one consistent style, no exceptions.** Sir Peter's reasoning carried it: roughly 609 images and growing, each photograph needing its own licence, and some categories ("Emergency Services", "Consultation") having no honest photograph at all. The developer had recommended photographs and changed that recommendation on the volume argument. The condition is that a single exception restarts the mixed-set problem this fixes. | Sir Peter, developer agreed |
| **Category images — no words or logos** | **No text and no logos inside any image, ever.** Category names render as real page text underneath. Words inside an image cannot be translated, cannot be read aloud, and go stale when a category is renamed — which V2 just did to 360 of them. | Sir Peter + Khadijah, both |
| **R46 rename — expanded scope** | Extended from the two originally locked places to a global sweep of BSR→BR, ESR→ER, DSR→DR. Sir Peter to lock as a dated R46 amendment. **Deliberately excluded:** routes, class names, config keys, stored `source` values — those are identifiers, not labels. **SSR/MSR also excluded** — they are the scope inside a request, not request types. | Sir Peter approved, built |
| **Popular Cities on the homepage** | Build it, but a city appears only once it holds **2 or more** professionals, and the section hides entirely when none qualify. Threshold is one constant. Cities-vs-states still open. | Sir Peter |
| **Tax + payout identity architecture** | Prefer the payment provider collecting and verifying sensitive tax/identity data, with GigResource storing only a verification status. **Endorsed as direction, not yet actionable** — see the finding below. | Sir Peter, pending CPA |

### The finding that changes the tax/identity plan

Checked on 13 August, in response to Sir Peter's question about whether
influencer payouts run through the same connected-account flow as professional
payouts. **Neither does.** Both `payouts` and `influencer_payout_requests` are
plain internal records — amount, method, a typed account string, and a status an
administrator marks as paid. Stripe is wired for taking money **in** (Checkout,
for memberships and reactivation) and there is no Connect integration at all.

This does not contradict the architecture above; it means the paying-out side
has to exist before a provider can collect anything on our behalf. It belongs in
front of the CPA, because it changes who is responsible for the forms in the
meantime.

---

## 4c. Decided 2026-08-19

Taken from the 9-item open-items QA (PM comments). Built the three that were
signed off to build; left the rest.

| Item | Decision | Who |
|---|---|---|
| **R46 labels** | User-facing names are **BR / ER / DR** everywhere a person reads them. Routes, class names, config keys and stored `source` values stay. SSR / MSR stay as the *scope* inside a request (one service vs several), not as types. | Signed off, built |
| **R44 category images** | No names, prices or logos inside the picture. The category title is page text. Art style stays **illustrations** (already locked 13 Aug). Admin upload now carries that instruction. | Signed off, built |
| **Insurance matrix** | Schema only: Required / Conditional / Not Required + insurance type + Tier A/B/C. **Do not enforce Required from those cells** until broker and attorney sign off. Live gate remains the named list in compliance config. | Signed off, schema only |
| **Tax / SSN fields** | Do not build. Payment provider collects; GigResource stores a status later. | Do not build |
| **Dispute legal wording, ID consent copy, admin bidding-window UI** | Do not build in this pass. | Do not build |

---

## 5. Locked rules — do not re-open without Sir Peter

From `GIGRESOURCE_RULES_MASTER.docx` (36 rules). These are settled; changing one
is a decision, not a preference.

- **R29** — ~~No AI anywhere.~~ **Retired 2026-08-22 (B4).** Never true on the ground: the AI tools always called OpenAI, and the owner has since directed *more* AI, not less. What actually held, and still holds, is two narrower rules that were both filed under R29 and must not be lost with it:
  - **R29a — a person decides disputes.** No automated ruling, no AI verdict; the dispute engine ranks and guides, a human decides. Enforced in `DecisionGuide`, `DisputeStates`, `RepeatOffenderHistory`. **This survives.**
  - **R29b — no invented authority.** A tool's output is a suggestion the user can change, never a locked or "official" figure, whether it came from a calculator or an AI-assisted tool. **This survives.**
  - The dead part was the marketing claim that the product is AI-free / "all rules-based." The disclaimer already discloses "AI-assisted"; that is now the canonical framing.
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
| 2026-08-13 | Added §4b for the decisions taken in Chat on 13 August. B5 resolved and struck. A7 moved to built (Phase 1). Recorded the payouts finding behind the tax/identity plan. |
| 2026-08-19 | Added §4c for the 9-item QA sign-off. Remaining user-facing copy swept to BR / ER / DR. Category image upload now tells admin not to bake names into the picture. Insurance matrix columns added as draft storage only — live Required list unchanged. Reconciled workbook (27 + 241) loaded onto those draft cells; live gate still the named list, now matching a V2 service via its parent. |
