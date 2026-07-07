# AI IQ — Deep 3-Mode: Handoff / Continuation Guide

This doc lets any AI assistant (Cursor, Claude Code, Copilot) continue the
**AI IQ membership** work in the **exact same pattern**. Read this fully before
touching any tool.

---

## 0. What this is

Every AI tool has 3 membership "levels" (Peter's DIY wording):

| Level key | Label shown to user | Meaning |
|-----------|--------------------|---------|
| `manual`  | **Do It Myself**            | NO AI. User builds/enters everything by hand. No compute call. |
| `semi`    | **Help Me Plan**           | AI drafts a suggestion that is **editable** — user tweaks before using. |
| `maximum` | **Coordinate It For Me**   | AI builds the whole thing, **read-only**. |

Tiers are ALREADY enforced platform-wide by middleware (`ai.level` on every
`Route::post('/ai-tools/…')`). "Deep 3-mode" = giving each tool a bespoke
in-page experience for the 3 levels. It is polish, not gating.

**Launch flag:** `AI_FEATURES_FREE_FOR_ALL=true` in `.env` → everyone resolves
to `maximum` right now, so nothing is blocked in production.

---

## 1. Core files (don't rebuild these — they exist)

- `config/ai-levels.php` — plan→levels map, `tool_modes`, `labels`, `descriptions`.
- `app/Domain/AiFeatures/AiAccess.php` — resolver. Key method:
  `AiAccess::level(User $user, string $toolKey): 'none'|'manual'|'semi'|'maximum'`.
- `app/Http/Middleware/EnsureAiLevel.php` — alias `ai.level`, blocks < semi.
- Controllers: `app/Http/Controllers/Ai*Controller.php`.
- Views: `resources/views/ai-tools/*.blade.php` and
  `resources/views/client/ai-tools/*.blade.php`.

**Tool key** = the 2nd URL segment, e.g. `/ai-tools/timeline-builder` → key
`timeline-builder`.

---

## 2. The exact pattern (copy from a DONE tool)

**Best full reference to copy:** `resources/views/ai-tools/event-planner.blade.php`
+ `app/Http/Controllers/AiEventPlannerController.php` (has all 3 modes: manual
builder, semi editable, max read-only). Other done examples: `timeline-builder`,
`vendor-matchmaking`, `budget-allocator`, `pricing-assistant`.

### 2a. Controller `show()` — add level resolution

```php
$level = \App\Domain\AiFeatures\AiAccess::level($request->user(), '<tool-key>');
if ($request->user()?->isAdmin() && in_array($request->query('preview'), ['manual', 'semi', 'maximum'], true)) {
    $level = $request->query('preview');
}
// ...then pass 'level' => $level into the view data array.
```

### 2b. View — top of `@section('content')`

```blade
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', '<one-line manual description>'],
        'semi'    => ['Help Me Plan', '<TOOL BRAND COLOR>', '<one-line semi description>'],
        'maximum' => ['Coordinate It For Me', '#16a34a', '<one-line max description>'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="<root-class>" data-level="{{ $level }}">

    {{-- Membership-level banner (standard, copy verbatim, keep the color var) --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:16px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:<brand-strong>;text-decoration:none;">Upgrade for more AI →</a>@endunless
    </div>
```

### 2c. Branch the body

```blade
@if($isManual)
    {{-- Do It Myself: a hand-built form/list. NO AI form, NO compute call.
         Hide any "AI recommendations / representative AI dashboard" here. --}}
@else
    {{-- AI form (semi + max). Button label + subtitle conditional: --}}
    <button ...>{{ $isSemi ? '✨ Suggest …' : '🤖 Build … For Me' }}</button>
@endif
```

### 2d. JS

```js
const LEVEL = document.querySelector('.<root-class>')?.dataset.level || 'maximum';
```
- In the render function, branch: **semi** → render editable `<input>`s (with a
  live recomputed total where relevant); **maximum** → read-only text.
- Manual mode gets its OWN `(function(){ ... })()` builder IIFE (seed a few
  starter rows + an "Add" button + per-row remove).
- **Null-guard every listener** (`document.getElementById('x')?.addEventListener`)
  because elements differ per level. The existing AI-form IIFE must keep
  `if (!form) return;` so it no-ops in manual mode.

---

## 3. Compliance rules (Peter — MUST follow, non-negotiable)

- ❌ No "24/7", no guarantees, no invented metrics/percentages presented as fact,
  no "expert advice" claims.
- ✅ Representative/sample data is fine, but frame model-derived numbers as
  estimates, not live facts.
- ❌ Do NOT auto-submit bids / auto-book — always keep a human approval step
  (Platform Disclaimer).

---

## 4. Verify EVERY tool live before saying "done"

1. Run: `php artisan serve --port=8123` (or your dev server).
2. Log in as admin: `admin@example.com` / `password`.
3. Hit each level via admin preview override:
   - `/ai-tools/<tool>?preview=manual`
   - `/ai-tools/<tool>?preview=semi`
   - `/ai-tools/<tool>?preview=maximum`
4. Confirm for each: correct banner label; manual shows the hand-built
   builder and NO AI form; semi shows editable inputs; maximum is read-only.
   Browser console must be clean.
5. Blade compiles (quick check):
   ```bash
   php artisan view:clear
   php -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); app("blade.compiler")->compileString(file_get_contents("resources/views/ai-tools/<tool>.blade.php")); echo "OK\n";'
   ```

---

## 5. Commit convention

One commit per tool. Message style:

```
AI IQ: <Tool Name> deep 3-mode

- Do It Myself (manual): <what>
- Help Me Plan (semi): <what, editable>
- Coordinate It For Me (maximum): <what, read-only>

Verified live (admin preview): <manual / semi / max results>.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>
```
Then `git push origin main`.

---

## 6. Remaining work

### A. Deep 3-mode — DONE (7): Package Builder, Proposal Writer, Budget
Allocator, Pricing Assistant, Timeline Builder, Vendor Matchmaking, Event Planner.

### A. Deep 3-mode — TODO (do highest-value first):
- Checklist Generator
- Guest Capacity Planner
- Theme & Style Advisor
- Venue Analyzer
- Review Writer
- Contract Assistant
- Message Assistant
- Translator
- Staffing Planner *(professional)*
- Bid Optimizer *(professional)*
- Availability Optimizer *(professional)*
- Portfolio Optimizer *(professional)*
- Upsell Assistant *(professional)*

### B. AI IQ core (in-scope):
- Phase 4 — free-beta usage caps for clients/influencers ("N free actions/month"
  via `AiFeatureGate` / `AiFeatureUsage`).
- Phase 5 — client-facing pricing/membership page (only admin side exists now;
  Peter's upgrade pricing is a $5 diff between tiers).

### C. General:
- Full QA testing pass (all user journeys).
- Backend builds for representative subsystems (wire remaining sample data to
  real models).

### D. 🔴 NEW / BILLABLE — DO NOT START without written scope + client sign-off:
- **Event ↔ AI-tool integration** (Peter confirmed intent): "Add to my event"
  button on each tool, store generated data on the event, open tools pre-filled
  from inside Post an Event / My Events. **Must be level-gated** (free = manual
  entry, Semi/Max = auto-attach). Scope + quote separately.
- Ticketing / complaint system.
- Notification engine (~60+ triggers) — depends on unbuilt payments/escrow/
  subscription/automation systems.

---

## 7. Golden rules

- Continue **only** the in-scope items (A, B, C). Do **not** begin any item in
  section D — it is billable and needs the client to agree scope/payment first.
- Verify live before claiming anything is done (empirical testing has caught
  real bugs a code-read missed).
- Keep changes page-scoped; don't touch shared layouts unless the task is
  explicitly a layout fix.
