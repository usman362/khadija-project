# GigResource — Feature Scope & Build Status

**Consolidated from client feedback (Peter Zylstra), June 2026**
_Prepared after reviewing every point against the current codebase._

This document maps everything raised across the recent feedback (the workflow diagrams,
AI-tools matrix, gig-creation flow, bidding board, and premium-redesign direction) against
what is **already built**, what **needs fixing/enhancing**, and what is **genuinely new work**.

---

## 1. Executive summary

- **The core engine already exists** — roughly 70% of the platform's backend is in place
  (booking lifecycle, proposals, agreements, payments, reviews, gig/event creation, multi-service
  requests, and 6 AI tools with membership-tier gating).
- **Most of the client's vision is presentation + UX + AI-layer expansion** on top of that
  engine — not a rebuild from scratch.
- **The biggest genuinely-new backend pieces are four:** Packages, Sealed Bidding, the AI
  Agreement guided flow (with AI auto-fill), and ~14 new AI tools.
- **Fastest quick wins** (good to show at the meeting): the AI-tools client/professional
  placement fix, and the first premium-redesigned page.

**Legend:** ✅ Built · ⚙️ Partial — exists but needs fix/enhance · 🆕 New build

---

## 2. Already built — the engine ✅

| Capability | Notes |
|---|---|
| Booking lifecycle | State machine: `requested → confirmed → completed` (+ cancelled), with per-actor rules |
| Proposals | Send / accept / reject / status update (professional → client) |
| Multi-Service requests (MSR) | Client + professional multi-service controllers |
| Messaging | Conversations, threads, attachments |
| Payments | Payment model + earnings |
| Reviews & ratings | Review model, auto-badge logic |
| Agreement document | Detailed contract view (event details, services, financials, payment schedule, T&Cs) |
| Agreement actions | Generate from booking, accept, reject, PDF download, renegotiation/cancellation wizard |
| Client gig/event creation | Create + publish event; professionals respond with proposals |
| 6 AI tools | Budget Allocator, Vendor Matchmaking, Review Writer, Pricing Assistant, Proposal Writer, Staffing Planner |
| Membership tiers + AI gating | "More AI at higher tiers" — already documented & enforced |

---

## 3. Needs fixing / enhancing ⚙️

| Item | What needs to happen | Size |
|---|---|---|
| **AI-tools placement** | Client menu currently lists all 6 tools; the 3 professional tools open in the client layout. Fix: each side shows only its own tools in its own theme; move **Review Writer + Staffing Planner to "both"**. | Small / quick |
| **Public pages too plain** | `/browse`, `/events-categories`, Team & Staffing, etc. exist but look bare → premium, detailed redesign. | Medium (frontend) |
| **Request types** | MSR exists; **SSR (single)** and **ESR (emergency)** + urgency levels need to be formalized as typed flows that slightly vary the form/document. | Small–Medium |

---

## 4. New work 🆕

### 4a. Frontend / UX (engine stays, presentation is new)

- **Flash-card wizard (clients only).** Each question/section on its own page, with back/forward
  navigation so clients stay focused and can correct answers; all answers reassemble into one
  document at the end. _(Professionals keep normal subpages — flash-cards are client-side only.)_
- **Complete 11-step "Create a Gig Request" flow:** Gig Summary → Event Information → Services
  Needed → Budget Center → Bidding Rules → Preferred Professionals → Files & Inspiration →
  Questions for Pros → AI Optimization → Preview → Publish.
- **Main Bidding Board (central):** all gigs in one place; filters by request type
  (All / ESR / SSR / MSR / Invite-Only / Bookmarked), AI Match %, Market Insights, and live stats.
- **Live "how professionals see it" preview** + live stats (views, bids received, questions,
  competition level, average response time).
- **Premium visual redesign** carried across all public pages.

### 4b. New backend (logic / database — not just UI)

- **Packages** — professionals define package offerings (set scope + price); clients order them
  directly. _(Does not exist today; bookings are per-event at a negotiated price.)_
- **Sealed Bidding + bidding modes** — Open Bidding, Verified Only, Invite Only, AI Match, Hybrid,
  and Sealed (bids hidden until reveal).
- **Counter-offer** step in the negotiation.
- **AI Agreement guided 3-phase flow** — Phase 1 Discovery & AI evidence collection (from chats,
  proposals, files) → Phase 2 Collaboration & Negotiation (version highlights, repeat-until-agreed)
  → Phase 3 Execution & Finalization (e-signatures, activate, deliver copies, secure archive), plus
  **AI auto-fill of "green" sections with a confidence score**. _(Guardrail: AI-filled sections stay
  editable and require both parties to confirm before signing — legal document.)_
- **~14 new AI tools** (see matrix below).

---

## 5. AI Tools — full matrix (per client's spec)

| AI Tool | User | Status | Purpose |
|---|---|---|---|
| AI Budget Allocator | Client | ✅ | Allocate event budget across services & categories |
| AI Vendor Matchmaking | Client | ✅ | Find best professionals by budget, location, ratings, style, availability |
| AI Event Planner | Client | 🆕 | Organize the event from planning through completion |
| AI Timeline Builder | Client | 🆕 | Event timeline incl. setup, schedule, teardown |
| AI Venue Analyzer | Client | 🆕 | Review venue, recommend vendors/equipment/logistics |
| AI Checklist Generator | Client | 🆕 | Personalized event planning checklist |
| AI Guest Capacity Planner | Client | 🆕 | Estimate staffing, food, beverages, venue capacity |
| AI Theme & Style Advisor | Client | 🆕 | Recommend colors, décor, themes, styles |
| AI Pricing Assistant | Professional | ✅ | Competitive pricing from labor, travel, equipment, demand, market |
| AI Proposal Writer | Professional | ✅ | Generate proposals, cover letters, bid descriptions |
| AI Package Builder | Professional | 🆕 | Create & optimize service packages for sale |
| AI Portfolio Optimizer | Professional | 🆕 | Recommend portfolio improvements to win more bookings |
| AI Availability Optimizer | Professional | 🆕 | Scheduling / calendar optimization |
| AI Upsell Assistant | Professional | 🆕 | Suggest add-ons & package upgrades to raise revenue |
| AI Bid Optimizer | Professional | 🆕 | Recommend best bid amount & strategy to improve win rate |
| AI Staffing Planner | Both | ✅ | How many people are needed for the event/service |
| AI Review Writer | Both | ✅ | Help both sides write fair, detailed reviews |
| AI Contract Assistant | Both | 🆕 | Explain contracts, summarize clauses, highlight key items |
| AI Message Assistant | Both | 🆕 | Write professional messages, replies, follow-ups |
| AI Translator | Both | 🆕 | Translate conversations, proposals, documents |

**Totals:** 6 built · 14 new · 20 total.
The 3 deterministic professional tools (Pricing / Proposal / Staffing) are free on every tier;
the LLM-assisted tools are gated by membership tier.

---

## 6. Key workflows (one shared engine, different entry points)

The **AI Agreement**, **Direct Offer / Request**, and **Order / Package** flows are largely the
**same journey** seen from different starting points. We build **one order engine** and layer these
on top — not three separate systems.

**AI Agreement — 3 phases**
1. Discovery & AI evidence collection
2. Collaboration & negotiation (version highlights, repeat-until-agreed)
3. Execution & finalization (e-sign → active → deliver copies → secure archive)

**Order / Offer / Request — lifecycle**
New order → review & questions → quote/adjust → client acceptance → contract & deposit →
planning → collaboration → event day → deliverables → client approval → final payment →
reviews → archive. _(Backend lifecycle already exists; request types, counter-offer, packages,
and the guided UI are new.)_

**Client gig creation** — the flash-card 11-step wizard above, feeding the Main Bidding Board.

---

## 7. Recommended sequencing

1. **Quick wins first** (visible at the meeting):
   - AI-tools client/professional placement fix.
   - First premium-redesigned public page (browse or categories) for sign-off.
2. **Client gig-creation experience:** flash-card wizard + request types (SSR/MSR/ESR) +
   Main Bidding Board + bidding modes (incl. sealed).
3. **Packages feature** (professional offerings + client ordering).
4. **AI Agreement guided flow** + AI auto-fill + e-signatures.
5. **New AI tools** — prioritize highest-value ones, build in phases (not all 14 at once).

---

## 8. Bottom line

- The foundation is solid; this is mostly an **experience + AI expansion** program, not a rebuild.
- Four items are real new backend: **Packages, Sealed Bidding, AI Agreement guided flow + AI-fill,
  and the new AI tools**.
- Everything ties into a **single order engine** with a polished, premium, client-first UX on top.
