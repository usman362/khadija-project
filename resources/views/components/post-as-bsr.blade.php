@props([
    'toolKey',
    'toolName',
    'formId',
])

@php
    /*
     * Checklist row 226, Phase 1 — "Post as BSR" from a tool result.
     *
     * The five-outcome vision (BSR / ESR / Direct Offer / Save Draft / Attach
     * to Existing Event) is not built here on purpose: the approved Phase 1
     * scope is this one leg from three tools, to prove the handoff before
     * committing to five outcomes across twelve tools. "Attach to Existing
     * Event" is the leg that already works.
     *
     * The facts a request needs — what kind of event, when, how many guests,
     * what budget, where — are the tool's own INPUTS, not its output. So this
     * reads the tool's form at submit time rather than depending on each tool
     * to hand them over. One component, three tools, no per-tool JavaScript.
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
    .pab-btn { border: none; border-radius: 10px; padding: 10px 18px; font-size: 13px; font-weight: 800; color: #fff;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8); cursor: pointer; }
    .pab-btn:disabled { opacity: .5; cursor: not-allowed; }
    .pab-btn:focus-visible { outline: 3px solid #93c5fd; outline-offset: 2px; }
</style>
@endpush
@endonce

<div class="pab" data-pab>
    <div class="pab-head">📣 Turn this into a request</div>
    <div class="pab-note">
        Post it as a bidding request and professionals come to you with prices. What you entered above is
        carried across — you can change any of it before it goes out, and nothing is published until you say so.
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
        <button type="submit" class="pab-btn" data-pab-btn>Post as a bidding request →</button>
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
