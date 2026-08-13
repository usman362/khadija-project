@props([
    'toolKey',
    'toolName',
    'formId',
])

@php
    /*
     * Checklist row 226 — turning a tool result into a request.
     *
     * Phase 1 was one outcome from three tools, to prove the handoff before
     * committing to five outcomes across twelve. This is Phase 2: three
     * outcomes, on the tools whose inputs describe an event. Which tools, and
     * why the other four are excluded, is recorded on
     * ClientBsrController::FROM_TOOL.
     *
     * The two legs still not here, and why:
     *   Attach to an existing event — already built, on the event page itself
     *     ("Add to my event"), which is where the client is choosing an event.
     *   Direct Offer — needs a professional, and none of these tools names
     *     one. Best Match does, which is why its handoff is a different leg.
     *
     * The facts a request needs — what kind of event, when, how many guests,
     * what budget, where — are the tool's own INPUTS, not its output. So this
     * reads the tool's form at submit time rather than depending on each tool
     * to hand them over. One component, seven tools, no per-tool JavaScript.
     *
     * The three buttons are not presented as equals. Bidding is the ordinary
     * route and leads; the other two are alternatives beside it, because a
     * row of three identical buttons makes a client stop and decide where
     * there is usually nothing to decide.
     */
    $user = auth()->user();
@endphp

@if($user?->hasRole('client'))
@once
@push('styles')
<style>
    .pab { border: 1.5px solid #2563eb; background: rgba(37,99,235,.05); border-radius: 14px; padding: 15px 16px; margin: 18px 0; }
    .pab-head { display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 800; color: var(--text-primary, #111827); margin-bottom: 4px; }
    .pab-note { font-size: 11.5px; color: var(--text-muted, #6b7280); margin-bottom: 11px; line-height: 1.45; }
    .pab-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .pab-btn { border: none; border-radius: 10px; padding: 10px 18px; font-size: 13px; font-weight: 800; color: #fff;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8); cursor: pointer; }
    .pab-btn:disabled { opacity: .5; cursor: not-allowed; }
    .pab-alt { border: 1.5px solid #c7d2fe; background: #fff; border-radius: 10px; padding: 9px 14px;
        font-size: 12.5px; font-weight: 700; color: #1e3a8a; cursor: pointer; }
    .pab-alt:hover { background: #eef2ff; }
    /* 3:1 is the large-text threshold and these are 12.5px, so the focus ring
       is sized against the 4.5:1 rule like the body text it sits in. */
    .pab-btn:focus-visible, .pab-alt:focus-visible { outline: 3px solid #1d4ed8; outline-offset: 2px; }
</style>
@endpush
@endonce

<div class="pab" data-pab>
    <div class="pab-head">📣 Turn this into a request</div>
    <div class="pab-note">
        What you entered above is carried across — you can change any of it before it goes out,
        and nothing is published until you say so.
    </div>
    <form method="POST" action="{{ route('client.bsr.from-tool') }}">
        @csrf
        <input type="hidden" name="tool_key" value="{{ $toolKey }}">
        <input type="hidden" name="tool_name" value="{{ $toolName }}">
        <input type="hidden" name="event_type"  data-pab-field="event_type">
        <input type="hidden" name="event_date"  data-pab-field="event_date">
        <input type="hidden" name="guest_count" data-pab-field="guest_count">
        <input type="hidden" name="budget"      data-pab-field="budget">
        <input type="hidden" name="location"    data-pab-field="location">

        <div class="pab-actions">
            {{-- The outcome travels as the button's own value, so the choice
                 is made by the click and there is nothing to keep in sync. --}}
            <button type="submit" name="outcome" value="bidding" class="pab-btn">
                Post as a bidding request →
            </button>
            <button type="submit" name="outcome" value="emergency" class="pab-alt">
                I need this urgently
            </button>
            <button type="submit" name="outcome" value="draft" class="pab-alt">
                Save as a draft
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    var wrap = document.querySelector('[data-pab]');
    var form = document.getElementById(@json($formId));
    if (!wrap || !form) return;

    // The tools spell the same fact differently — a budget is `total_budget`
    // on one and `budget` on another, a date is `date` or `event_date`. The
    // request only cares about the fact.
    var ALIASES = {
        event_type:  ['event_type'],
        event_date:  ['event_date', 'date'],
        guest_count: ['guest_count'],
        budget:      ['budget', 'total_budget'],
        location:    ['location']
    };

    // On submit, whichever button was pressed: the copy is the same for all
    // three outcomes, because they carry the same facts to different places.
    wrap.querySelector('form').addEventListener('submit', function () {
        wrap.querySelectorAll('[data-pab-field]').forEach(function (input) {
            var names = ALIASES[input.getAttribute('data-pab-field')] || [];
            for (var i = 0; i < names.length; i++) {
                var src = form.querySelector('[name="' + names[i] + '"]');
                if (src && String(src.value).trim() !== '') { input.value = src.value; return; }
            }
            input.value = '';
        });
    });
})();
</script>
@endif
