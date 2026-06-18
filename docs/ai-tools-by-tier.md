# GigResource — AI Tools by Membership Tier

> Developer Feedback v1.1 §8.3. Peter's direction: _"GigResource's value proposition is AI
> integration at every level, not human-managed assistance. The platform should offer more
> AI tools to users with higher membership tiers."_

This documents every AI tool, which tier unlocks it, and how the gating works.

---

## 1. The tier matrix

| AI Tool | Type | Starter | Professional | Enterprise |
|---|---|---|---|---|
| **AI Review Writer** | LLM-assisted | ✅ 10 / month | ✅ Unlimited | ✅ Unlimited |
| **AI Budget Allocator** | LLM-assisted | 🔒 — | ✅ 30 / month | ✅ Unlimited |
| **AI Vendor Matchmaking** | LLM-assisted | 🔒 — | ✅ 30 / month | ✅ Unlimited |
| AI Pricing Assistant | Deterministic | ✅ Free | ✅ Free | ✅ Free |
| AI Proposal Writer | Deterministic | ✅ Free | ✅ Free | ✅ Free |
| AI Staffing Planner | Deterministic | ✅ Free | ✅ Free | ✅ Free |
| AI Chatbot | LLM-assisted | Daily limit | Daily limit | Daily limit |

**Progression:** Starter gets the entry-level writer (capped). Professional unlocks the
premium planning tools with a monthly quota. Enterprise removes all caps.

**Deterministic tools** (Pricing Assistant, Proposal Writer, Staffing Planner) make **no LLM
call and consume no quota**, so they stay free on every tier — they're "AI-assisted" but
cost nothing to run. They are intentionally NOT plan-gated.

**Verified** by simulation (flag off, one throwaway subscription per plan): no-subscription →
all locked; Starter → Review 10/mo only; Professional → Review unlimited + Budget/Vendor
30/mo; Enterprise → all unlimited.

---

## 2. How gating works

The plumbing already exists under `app/Domain/AiFeatures/`:

- **`AiFeatureCode`** — canonical feature codes: `ai.review_writer`, `ai.budget_allocator`,
  `ai.vendor_matchmaking`.
- **`AiFeatureGate`** (service) — `authorize()` (throws if not allowed / over quota),
  `recordUsage()` (logs a row in `ai_feature_usages`), `status()` (badge data for views).
- **`User::aiFeatureAccess($code)`** — resolves the user's active subscription → plan →
  `plan_features` row matching the code with `is_included = true`. Returns
  `['enabled' => bool, 'quota' => int]` where **quota 0 = unlimited**.
- **`plan_features`** table — each AI entitlement is a row carrying `feature_code` +
  `quota_monthly` + `is_included`. **Seeded by `MembershipPlanSeeder`** (the AI rows live in
  each plan's `features` array as structured entries — see §8.3 comments there).

### Enforcement points (one per gated tool)
| Tool | Controller | Calls gate |
|---|---|---|
| Budget Allocator | `BudgetAllocatorService` (via controller) | `authorize` + `recordUsage` |
| Vendor Matchmaking | `AiVendorMatchmakingController@match` | `authorize` + `recordUsage` |
| Review Writer | `AiReviewWriterController@compose` | `authorize` + `recordUsage` |

Each tool's `show()` passes `$status` to its view, which renders
`partials/_ai_quota_badge.blade.php` (unlimited / N-left / limit-reached / locked-upgrade).

---

## 3. ⚠️ Activation — the launch switch

Gating is **fully wired but currently dormant**, by design.

`.env` → `AI_FEATURES_FREE_FOR_ALL=true` makes **every authenticated user get unlimited
access to all AI tools, regardless of plan** (`User::aiFeatureAccess()` short-circuits at the
top). This is intentional for the soft-launch / client UAT period — the same posture as the
payments go-live lock.

**To activate tier-gating at launch:**
1. Set `AI_FEATURES_FREE_FOR_ALL=false` in production `.env` (it is already `false` in
   `.env.example`).
2. `php artisan config:clear` (the value is read via `env()`).
3. From that point, users without an active subscription are locked out, and quotas apply
   per the matrix above.

> Do this together with the membership/Stripe go-live — until paid plans are live, flipping
> the flag would lock out demo accounts that have no subscription.

Admins always have unlimited access (bypass the flag).

---

## 4. Adding a new gated AI tool

1. Add a constant to `AiFeatureCode` (+ `all()` + `label()`).
2. Add a `plan_features` row per plan in `MembershipPlanSeeder` — a structured entry
   `['feature' => '…', 'feature_code' => 'ai.x', 'quota_monthly' => N]` (omit / `is_included
   => false` to lock a tier). Re-run `php artisan db:seed --class=MembershipPlanSeeder`.
3. In the controller: inject `AiFeatureGate`; call `authorize()` before the work and
   `recordUsage()` after; pass `status()` to the view and include
   `partials._ai_quota_badge`.
