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
| ~~B1~~ | ~~Client request fee — when it is charged~~ | — | **Answered 2026-08-22 (Owner doc):** charged at **final submission / publish** of the request, not at finalization. Drafts are free — the fee fires only on the final publish/submit action. Applies to all request types (BR/ER/DR) unless a type documents an exception. |
| ~~B2~~ | ~~Bidding window values (R37)~~ | — | **Answered 2026-08-22 (Owner doc):** Normal request **48h default**, client may set a shorter deadline. Emergency **2h default**, bids shown in real time as submitted (no holding until close). **Event-date guard:** if the event date falls within the default window, the system auto-shortens rather than letting the window run past the event. Supersedes the 2026-07-31 5-day / 24h values. |
| B3 | Live Event Upgrades — money rules | High | **Investigated 2026-08-22; four concrete choices below, each with a recommendation grounded in what is already built. Still needs Sir Peter's yes/adjust — the answers are his, not in the code. Nothing built until then: there is no upgrade/reimbursement data model, and a money dashboard on invented figures is exactly the trap this codebase avoids.** **(1) How held funds are recorded.** The mockup shows "Secure Payment Held" but there is NO escrow model — the pro dashboard's "$7,850 held" is a hardcoded placeholder. Options: (a) an upgrade adds to the existing agreement/booking as one running total, one payout at the end; (b) each upgrade is its own mini-charge. *Rec: (a)* — least new machinery, and it is what "Current Amount Owed" already implies. **(2) Overtime rate mandatory on every bid?** Options: mandatory field on every bid, or optional. *Rec: optional* — a pro MAY set one; if not, a live overrun is just a normal upgrade the client approves. Mandatory taxes every bid for a case most events never hit. **(3) Geofence for on-site upgrades.** Options: GPS-verify the pro is at the venue, or treat "on-site" as a status the pro sets. *Rec: status only at v1* — there is no lat/long presence check anywhere (R38 is state-level), and geofencing an unlaunched feature is heavy. Revisit on abuse. **(4) The upgrade fee — the contradiction.** The spec's "15% vs your standard 10%" matches NOTHING shipped: the real commission is 5/3/1.5% by tier (`Commission::rateFor`), and there is no 10% anywhere. Options: invent a special upgrade fee, or an upgrade carries the SAME tier commission as all other money. *Rec: same commission* — an upgrade is just more money through the same agreement, taxed once at payout on the final total. The spec's 10/15% predates the tiered model and should be dropped. |
| ~~B4~~ | ~~AI modules — keep or remove~~ | — | **Resolved 2026-08-22 (evidence-based; awaiting Sir Peter's ratification of the rulebook edit).** The premise was wrong in two ways. (1) It is not three modules — **five** services genuinely POST to `api.openai.com`: AI Agreement generation, the support chatbot, Budget Planner, Best Match, and Review Builder (each with a rules-based fallback when no key is set). (2) The registration disclaimer's **"AI-assisted marketplace"** line is not a leak to remove — it is the honest, required disclosure, and matches Sir Peter's own AI-first direction (AI IQ levels, AI Agreement Builder, and the AI email-answering plan of 2026-08-22). Decision: **KEEP** — the platform is AI-assisted and says so. R29's blanket "no AI" reading is retired (see R29 note below). The FAC-13 "D2 remove-AI done" claim was simply false. No feature removed; the code comments that wrongly called the AI tools "calculators" were corrected. |
| ~~B6~~ | ~~A pro awarded two services on one request gets one booking~~ | — | **Fixed 2026-08-22 (commit 5fb7f01).** Narrow fix, not folded into A10: bookings and finalizations gained the `category_id` the bid always had, keyed (event, supplier, category) at all three award points. SSR (null category) stays one booking. Migration ordered for MySQL (old FK-backed unique can't drop first) — verified forward+rollback on MySQL, since the SQLite suite can't catch it. A10's display layer (event stamping, per-service My Gigs rows) still open. |
| ~~B5~~ | ~~Brand button contrast~~ | — | **Resolved 2026-08-13.** Sir Peter approved via Ali. Measured worse than first reported — 2.26:1, not 2.8, once read at the light end of the button's gradient. Fixed sitewide with on-white colour variants; `--orange` and `--blue` themselves are untouched. See `docs/contrast-audit.md`. |

---

## 2. Approved — not built

Decided and unblocked. This is the build queue.

| # | Item | Priority | Notes |
|---|---|---|---|
| A1 | **Money ledger** | **Highest** | `booking_ledger` does not exist. Release Milestone, refunds, disputes and Live Event Upgrades all sit on top of it. Nothing in this group can be built first. **Owner instruction 2026-08-22:** ACTION REQUIRED — submit a proposed DB schema + payment-state flow FIRST; do NOT build any money-tracking until the Owner reviews and approves. Ledger must track as discrete, separately-auditable records: original booking amount, client platform fees (incl. the $2.99), professional commission, upgrades/change-orders (each separate, never merged into the booking total), payment-provider fees, taxes, refunds/adjustments, milestone funding+releases, and payouts. Do not represent the picture by modifying a single booking-total field. |
| A2 | Refunds | High | Needs A1. |
| A3 | Disputes | High | Needs A1 and the 4-step resolution flow already specified. |
| A4 | Change orders | Medium | Needs A1. |
| A5 | **My Gigs + Contracts → one workspace** | Medium | Khadijah 2026-07-31, Sir Peter agreed. Verified: they are genuinely different data (My Gigs reads `Event.supplier_id` = the work; Contracts reads `Booking.supplier_id` = the agreement and the money). Merging is a UI change, not a data change. |
| A6 | **Live Event Upgrades (LEU)** | Medium | Agreed as a **platform-wide feature**, not a request type — available on BSR, ESR, DSR and Packages once an event is Live. Blocked by B3 for the money rules, then needs A1. **B3 rules CONFIRMED 2026-08-22 (Owner doc), all BUILD:** (1) upgrade = amendment to the existing booking (one client-facing total = original + each approved upgrade), but internal record keeps original + each upgrade as SEPARATE line items — do not collapse; upgrade payment secured before the upgrade is confirmed. (2) Overtime rate CONDITIONAL — required for time-based services (DJ, bartender, photographer, security, coordinator, musician) via a rate or explicit 'not available'; not forced on fixed-deliverable services (cake/floral drop-off). (3) On-site: upgrade flow available only once the booking is active/on-site AND the pro has done a check-in/arrival confirmation; no GPS in V1. (4) Upgrade commission = the ordinary tier rate (5/3/1.5%), no special upgrade fee. NOTE: the billing/payment halves of (1) overlap A1's money-ledger hold — build the mechanics (overtime field, check-in gate) now; the money line-items wait on the A1 schema review. |
| A10 | **Each service in a multi-service request as its own gig** | **Approved — Phase 2** | Khadijah, 2026-08-02: own budget, bids, milestones and contract per service; different professionals can win different services in one event. Not to delay current priorities, but recorded now so it is not redesigned later. Of the four: **bids are already per service** (unique on event+supplier+category); **budget, milestones and contract are not**. Milestones need A1. See B6 — the contract half is a live bug, not just a gap. **Progress 2026-08-22 (commit 4ce6dbc):** awarding one service of a multi-service request no longer closes the whole event — `booted()` stamps `event.supplier_id` only on FULL award, so half-awarded requests stay on the board (`Event::isFullyAwarded`). **Different-pros gap CLOSED 2026-08-22 (commit 4d52796):** `Event::scopeOpenForBids` makes the board and `RequestLifecycle::statusFor` service-aware — a request is on the board while a named service has no confirmed booking; a two-pro split now reads AWARDED and leaves the board despite the null `supplier_id`. **Still open:** budget-per-service, and milestones (blocked on A1). |
| A7 | Tool → Request: "Post as BR" | — | **Phase 1 built 2026-08-13.** Live on Budget Planner, Guided Event Planner and Timeline Builder; the endpoint refuses any fourth tool, so Phase 2 stays a decision rather than a drift. What crosses over is what the CLIENT typed, never the tool's output. The clickable prototype of all five outcomes remains at `/client/prototype/tool-to-request` and still needs Sir Peter's walkthrough. |
| A8 | Restrict marketplace for out-of-area accounts | — | **Done 2026-07-31.** Moved to Built. |
| A9 | Co-Op / Team removal | — | **Done 2026-07-31.** Moved to Built. |

---

## 3. Built

Verified against the code, most recent first. Safe for Sir Peter to screenshot.

| Item | Notes |
|---|---|
| **/browse 500'd for anyone who had opened a professional's profile** | 2026-08-25, reported from production (`TypeError: htmlspecialchars(): Argument #1 must be of type string, array given`, browse.blade.php:645). The Recently Viewed rail read `$profile->portfolio` **raw** and took the first entry. That column holds two shapes — a structured entry from an upload (`type`/`featured`/`hero`/`square`) and a bare URL string on older rows — so the first entry came back as an ARRAY and `<img src="{{ $rvImg }}">` threw. Every seeded profile carries the structured shape, so the page died for **any** visitor who had opened one profile and gone back to browse. The same page's search cards already used `portfolioHeroUrls()`, the one accessor that knows both shapes; the rail now uses it too. The three other views that touch the column all guard with `is_array()` — this rail was the only unguarded read. Regression tests were confirmed to fail against the old line before the fix went in. |
| **Client Portfolio removed** | 2026-08-25, on Ali's instruction: *"my portfolio client ka nhi hota"* — a portfolio is a professional's shop window, work they are selling, and a client is not selling anything. Removed in full: the sidebar item, the dashboard's "View my public profile" CTA (now Profile & Settings), the route, `ClientPortfolioController`, both views, `config/client-portfolio.php` and its test file. `App\Support\ClientStats` stays — the Dashboard and Reports both read it. **Flag for Sir Peter:** this page was built to **Rule R53**, the client's public "third tier" (Dashboard and Profile private, Portfolio public), so R53 now has no surface. The rule is his; if he still wants it, this is a rebuild, not a toggle. |
| **"Resolve Without Filing" opened raw JSON** | 2026-08-25, reported from production. The button linked to `route('messages.index')` — which is not the inbox page but `MessageController@index`, a **JSON API** returning a paginated list of message rows. The name is the trap: it reads exactly like the page anyone would want. Two other views had the same link (the dispute rows, and the package page's ✉ Message). There are two inbox PAGES, one per portal, so the choice now lives in `App\Support\Inbox` — a view asks for the inbox rather than guessing a route name, and a client lands in `/client/messages` while a professional lands in `/professional/messages`. A test scans every Blade for the JSON route so it cannot come back. |
| **Payments page: a dead 280px strip down the right** | 2026-08-25, reported by Ali as "width full nahi". Three cards were removed from a `<div class="pay-bottom">` on 2026-08-15 and the closing tag went with them, leaving it open — so the tag meant to close `.pay-main` closed `.pay-bottom` instead, and the right rail ended up **nested inside** the main column. `.pay-layout` had one child, its 280px second track reserved and permanently empty; Quick Actions rendered at the bottom of the page instead of beside the ledger. Tag closed, dead `.pay-bottom` CSS removed, and a test counts div opens against closes inside `.pay-main`. Note for the next person: the first version of the comment explaining this quoted Blade's own close-comment sequence, which ended the comment early and rendered the rest as page copy — the same trap as the site-wide logo link (commit `1d004d3`). |
| **Chat attachments never worked at all** | 2026-08-25, reported by Ali. A file is uploaded when picked and held until the message is sent, so between the two there is an attachment with no message — but `message_attachments.message_id` was NOT NULL with a foreign key, and the upload controller inserted **`0`** into it, with a comment calling it temporary, then nulled it on the next line. MySQL never reached the next line: 0 is not a message, the constraint rejected the INSERT, and **every upload 500'd**. The paperclip opened a file dialog and nothing else ever happened. Column is nullable now (`nullOnDelete`), plus an `uploaded_by` so an unsent file has an owner. Second fault behind it: `download()` 404'd whenever an attachment had no message — which is every attachment while it sits in the composer, so even a successful upload could not be previewed. Verified end to end in the browser: upload 201 → send 201 → linked → download 200. |
| **Emoji picker had ten characters** | 2026-08-25. All ten were event-planning shorthand (👍🙏🎉💐…) in a 176px box. People write to each other in these threads. Now `App\Support\EmojiCatalog`: 178 emoji in four groups, with a search that filters on the emoji's **name** — "party" finds 🎉, and the names double as the accessible label. Not the full Unicode set on purpose: this is a marketplace where clients and professionals arrange paid work. Two layout faults came with it — the JS toggled `display: flex`, which would have laid the search box, tabs and grid out side by side, and `left: 0` put 132px of a 306px panel past the composer's edge (the old 176px box fitted either way, which is why nobody noticed). |
| **Bookings status tiles wrapped onto two lines** | 2026-08-25. Seven tiles, `grid-template-columns: repeat(6, ...)` — "Awaiting Completion" was added as a seventh and the grid was never widened, so Cancelled sat alone on a second row. The tiles ARE the status filter, and a filter that looks like two rows reads as two groups. Now seven columns, stepping to 4 → 3 → 2 as the viewport narrows. A test compares the declared tile count against the CSS column count, so the next tile cannot silently wrap again. |
| **Professional profile: the avatar was sliced off** | 2026-08-25. The avatar straddles the banner, and the banner's gallery tiles are absolutely positioned inside it — a positioned element paints above static content whatever the source order, so the tiles covered the 60px of the avatar that overlaps and the picture looked cut along the photos' bottom edge. The avatar is positioned now (`z-index: 3`, clearing the tiles and the two icon buttons at 2). Separately the name and the Request a Quote row cleared the banner by **four pixels**; 84px instead of 64px gives a clear 24px. |
| **Date picker: every past date was invisible** | 2026-08-25, reported by Ali on the Emergency Request form. `_datepicker.blade.php` shipped as a **dark** theme with a short light "override" bolted on — and the site is light and stamps `data-theme="light"` by default, so the patch was what ran. It reached four selectors and missed the two that set white text with `!important`: `.flatpickr-day.flatpickr-disabled { color: rgba(255,255,255,.20) !important }` and `.flatpickr-time input { color: #fff !important }`. White on white. On ER **thirty** days carry `flatpickr-disabled` — every date before the earliest allowed — and all thirty rendered invisible, so the calendar opened with three empty rows above the first pickable date and read as broken; the clock showed as a stray fragment for the same reason. Rewritten light-first with the layout's own tokens (each with a literal fallback — a picker must not need a variable to be readable), dark as the override. Verified in the browser: 42 day cells, 0 unreadable, both themes. Affects all four layouts and every date field on the site. |
| **Direct Request — "Choose Professional" did not work** | 2026-08-25, reported by Ali. Three faults stacked. (1) `$selectedPro` fell back to `$pros->first()`, so picking a service quietly addressed the form to whoever the database returned first — the client could send a request to somebody they never chose. (2) The Service section renders `@unless($selectedPro)`, and because (1) always set one, choosing a service made the service picker **vanish** — a one-way door with no way back. (3) With nobody matching, or before any service was chosen, the page still rendered a focusable "Send to" select with **no options in it**, directly under a sentence saying there was nobody; it now shows a real empty state naming the service and the state, with two ways out. Plus: the control carried `aria-label="id }}' > —"`, a botched find/replace read aloud verbatim by screen readers, now a proper label. A professional named in the URL is still honoured — but re-checked against R38, because a link is not a bypass. |
| **BR wizard step 7 rebuilt against Sir Peter's mockup — and step 8 was unreachable** | 2026-08-25. The wizard said "Step 7 of 8" and had no eighth step: `furthestAllowed` listed six completable steps for a seven-completable-step wizard, so Continue on step 7 redirected to Review and Review's own guard bounced straight back. Publishing still worked (save() has no such guard) — what was missing was the client's chance to read what they were about to publish. Separately, the step's two controls carried `.bw-input`, a class the wizard's stylesheet never defines, so they rendered as raw browser widgets. Step 7 now asks for **event date, start time and end time** as the mockup does (end-before-start rolls to the next day — a reception running to 1am), counts a 0/500 note, offers clickable nearby dates that set the date, and pulls a proposal deadline back if the client moves the event earlier. **Not built from the mockup:** the Available / Limited / Not Confirmed / Unavailable buckets and the EXCELLENT strength gauge — three of those four states do not exist in our data and the rating is not measured. **Also not built:** the Time Zone picker — R38 puts client and professional in the same state by design, and a control nothing converts by is a control that only looks like it works. New: when nobody matches at all, the page says which service in which state has no professionals and offers two ways out, rather than three zeros. |
| **Files on a request — upload, preview, remove** | 2026-08-25. BR wizard step 6 said "Attachments aren't available yet" and drew a dashed box; the client counted it as one of eight steps and it did nothing. Now a real dropzone: images preview as thumbnails, PDFs open in a tab, everything else gets a named tile. Files go to the **private** disk and are served by a controller — never a public URL, because a floor plan or a guest list is not something to leave on a guessable path. The type is read off the file, not its name. A professional can open them **only once the request is published**, and once it is, the client can no longer remove one — pulling a document out from under a bid already sent moves the goalposts. Uploads happen before the Event row exists (the wizard keeps state in the session) and are adopted by the event on save or publish. Also live on the event's Files tab and, read-only, on the professional's gig page. |
| **Package purchase path (audit row 25)** | 2026-08-25. A package had a price, a page, and a "Request this Package" button that opened the Direct Request form — a different product, in which the package's own price appeared nowhere. Now `/client/packages/{package}/book`: fixed price, date, R38 state gate, own-package and draft blocked, no double-booking on one date. It creates the booking at `requested`, **not** confirmed — an instant-book that commits the professional before they have seen the date would be a screen promising what the other side never agreed to. No tiers, no add-ons, no fee line: none of those has been decided, and a number on a checkout is a number the client will hold us to. No payment is taken; the confirmation says so in as many words. |
| **Transaction detail (audit row 17)** | 2026-08-25. `/client/payments/{booking}` — what the money is for, its status history, and one plain sentence per state about whether anything has moved. Nothing is derived, split or forecast. |
| **Payments and Spending were reporting $0 on real money** | 2026-08-25, found while building row 17. `ClientFinanceController::priceColumn()` looked for `bookings.total_amount` then `bookings.agreed_price`; neither column has ever existed. It returned null, every sum was skipped, and both pages showed $0 and dashes while all 62 bookings carried a price in `bookings.price`. Same defect as the Total Spent card: a real number, not read. |
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
