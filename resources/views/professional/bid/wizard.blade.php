@extends('layouts.professional')

@section('title', 'Submit Your Bid')
@section('page-title', 'Submit Your Bid')

{{-- Screen 4 of the professional BSR set — the six-step proposal builder.

     The board's inline form takes an amount and a note, which is enough to
     place a number. This collects what the client's Compare screen actually
     asks them to weigh: an itemised price, availability, the delivery plan and
     the terms. Nothing reaches the client until the last step. --}}

@section('content')
<style>
    .bd-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
    .bd-h { font-size: 23px; font-weight: 800; color: var(--text-primary); }
    .bd-sub { font-size: 13.5px; color: var(--text-secondary); margin-top: 4px; max-width: 620px; line-height: 1.6; }

    .bd-opp { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 18px; margin-bottom: 18px; }
    .bd-opp-h { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; margin-bottom: 8px; }
    .bd-opp-h b { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .bd-tag { font-size: 10.5px; font-weight: 800; border-radius: 6px; padding: 3px 9px; }
    .bd-tag.BSR { background: rgba(37,99,235,.14); color: var(--info-text); }
    .bd-tag.ESR { background: rgba(220,38,38,.16); color: var(--bad-text); }
    .bd-tag.DSR { background: rgba(124,58,237,.16); color: var(--accent-text); }
    .bd-tag.sc  { background: rgba(148,163,184,.16); color: var(--text-secondary); }
    .bd-opp-meta { display: flex; gap: 18px; flex-wrap: wrap; font-size: 12.5px; color: var(--text-secondary); font-weight: 600; }
    .bd-dl { color: var(--warn-text); font-weight: 800; }

    .bd-steps { display: flex; gap: 4px; overflow-x: auto; padding-bottom: 4px; margin-bottom: 18px; }
    .bd-step { flex: 1 1 0; min-width: 104px; text-align: center; text-decoration: none; }
    .bd-dot { width: 29px; height: 29px; margin: 0 auto 6px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; background: rgba(148,163,184,.14); color: var(--text-muted); border: 1px solid var(--border-color); }
    .bd-step.done .bd-dot { background: #15803d; border-color: #16a34a; color: #fff; }
    .bd-step.on .bd-dot { background: #2563eb; border-color: #2563eb; color: #fff; }
    .bd-step small { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); }
    .bd-step.on small { color: var(--info-text); }

    .bd-grid { display: grid; grid-template-columns: minmax(0,1fr) 290px; gap: 18px; align-items: start; }
    @media (max-width: 1000px) { .bd-grid { grid-template-columns: minmax(0,1fr); } }

    .bd-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 22px 24px; }
    .bd-card h3 { font-size: 17px; font-weight: 800; color: var(--text-primary); margin-bottom: 5px; }
    .bd-card .lede { font-size: 13px; color: var(--text-secondary); line-height: 1.65; margin-bottom: 18px; }
    .bd-f { margin-bottom: 16px; }
    .bd-f label { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
    .bd-f .req { color: var(--bad-text); }
    .bd-f input[type=text], .bd-f input[type=number], .bd-f textarea {
        width: 100%; background: transparent; border: 1px solid var(--border-color); border-radius: 10px;
        padding: 10px 12px; font-size: 13.5px; color: var(--text-primary); font-family: inherit;
    }
    .bd-f textarea { min-height: 130px; resize: vertical; line-height: 1.6; }
    .bd-help { font-size: 11.5px; color: var(--text-muted); margin-top: 5px; line-height: 1.5; }

    .bd-budget { background: rgba(22,163,74,.09); border: 1px solid rgba(22,163,74,.25); border-radius: 12px; padding: 13px 15px; margin-bottom: 16px; }
    .bd-budget b { display: block; font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .bd-budget span { font-size: 11.5px; color: var(--text-secondary); }

    .bd-items { border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
    .bd-item { display: grid; grid-template-columns: minmax(0,1fr) 130px 34px; gap: 8px; padding: 8px 10px; border-bottom: 1px solid var(--border-color); align-items: center; }
    .bd-item:last-child { border-bottom: 0; }
    .bd-item input { border: 0 !important; background: transparent !important; padding: 6px 4px !important; }
    .bd-x { border: 0; background: transparent; color: var(--text-muted); cursor: pointer; font-size: 15px; }
    .bd-add { border: 1px dashed var(--border-color); background: transparent; border-radius: 9px; padding: 8px 14px; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); cursor: pointer; margin-top: 9px; font-family: inherit; }

    .bd-money { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; background: rgba(148,163,184,.07); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px 16px; margin-top: 16px; }
    @media (max-width: 620px) { .bd-money { grid-template-columns: 1fr; } }
    .bd-money div span { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .3px; }
    .bd-money div b { font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .bd-money .neg b { color: var(--bad-text); }
    .bd-money .net b { color: #4ade80; }

    .bd-check { display: flex; gap: 9px; align-items: flex-start; border: 1px solid var(--border-color); border-radius: 11px; padding: 12px 14px; font-size: 12.5px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 14px; }
    .bd-check b { color: var(--text-primary); }

    .bd-nav { display: flex; justify-content: space-between; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-color); flex-wrap: wrap; }
    .bd-btn { border: 1px solid var(--border-color); background: transparent; border-radius: 10px; padding: 10px 18px; font-size: 13px; font-weight: 700; color: var(--text-secondary); text-decoration: none; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; }
    .bd-btn.go { background: #2563eb; border-color: #2563eb; color: #fff; font-weight: 800; }

    .bd-rail { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 15px 17px; margin-bottom: 13px; }
    .bd-rail h4 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 10px; }
    .bd-el { display: flex; justify-content: space-between; gap: 10px; font-size: 12px; padding: 5px 0; }
    .bd-el span { color: var(--text-muted); }
    .bd-el b { font-weight: 700; }
    .bd-el.ok b { color: #4ade80; }
    .bd-el.no b { color: var(--warn-text); }
    .bd-rem { display: flex; gap: 8px; font-size: 11.5px; color: var(--text-muted); line-height: 1.5; margin-bottom: 8px; }

    .bd-rev { display: flex; justify-content: space-between; gap: 16px; padding: 9px 0; border-bottom: 1px solid var(--border-color); font-size: 13px; }
    .bd-rev:last-of-type { border-bottom: 0; }
    .bd-rev span { color: var(--text-muted); font-weight: 600; }
    .bd-rev b { color: var(--text-primary); text-align: right; }
</style>

@if(session('status'))
    <div style="background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:#4ade80;padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:13.5px;">✅ {{ session('status') }}</div>
@endif
@if($errors->any())
    <div style="background:rgba(220,38,38,.12);border:1px solid rgba(220,38,38,.3);color:var(--bad-text);padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:13.5px;">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

@php
    $keys = array_keys($steps);
    $prev = $stepIndex > 0 ? $keys[$stepIndex - 1] : null;
    $ceiling = $event->budget_max ?: $event->budget;
@endphp

<div class="bd-top">
    <div>
        <div class="bd-h">Submit Your Bid</div>
        <p class="bd-sub">Your price, availability, plan and terms. Nothing here is visible to the client until you submit.</p>
    </div>
    <a class="bd-btn" href="{{ route('professional.bidding-board.index') }}">Exit</a>
</div>

<div class="bd-opp">
    <div class="bd-opp-h">
        <b>{{ $event->title }}</b>
        <span class="bd-tag {{ $type }}">{{ $type }}</span>
        <span class="bd-tag sc">{{ $scope }}</span>
    </div>
    <div class="bd-opp-meta">
        @if($event->starts_at)<span>📅 {{ $event->starts_at->format('M j, Y') }}</span>@endif
        @if($event->location)<span>📍 {{ $event->location }}</span>@endif
        @if($event->guest_count)<span>👥 {{ number_format($event->guest_count) }} guests</span>@endif
        <span>{{ $event->categories->pluck('name')->implode(', ') }}</span>
        @if($event->proposal_deadline)
            <span class="bd-dl">⏱️ Closes {{ $event->proposal_deadline->format('M j, g:i A') }} · {{ $event->proposal_deadline->diffForHumans() }}</span>
        @endif
    </div>
</div>

<div class="bd-steps">
    @foreach($steps as $key => $label)
        @php $i = $loop->index; @endphp
        <a class="bd-step {{ $i === $stepIndex ? 'on' : ($i < $stepIndex ? 'done' : '') }}"
           href="{{ $i <= $stepIndex ? route('professional.bid.step', [$event, $key]) : 'javascript:void(0)' }}">
            <span class="bd-dot">{{ $i < $stepIndex ? '✓' : $i + 1 }}</span>
            <small>{{ $label }}</small>
        </a>
    @endforeach
</div>

<form method="POST" action="{{ route('professional.bid.save', [$event, $step]) }}">
@csrf
<div class="bd-grid">
    <div class="bd-card">

    {{-- ── 1 · Price ───────────────────────────────────────── --}}
    @if($step === 'price')
        <h3>Set your price</h3>
        <p class="lede">You can bid within the client's range or above it with an explanation. Break the number down if it helps them understand what they're paying for.</p>

        @if($event->budget_min || $event->budget_max)
            <div class="bd-budget">
                <span>Client budget range</span>
                <b>${{ number_format((float) ($event->budget_min ?? 0)) }} – ${{ number_format((float) ($event->budget_max ?? $event->budget)) }}</b>
                <span>You may bid within or above this range.</span>
            </div>
        @endif

        <div class="bd-f">
            <label>Your bid amount <span class="req">*</span></label>
            <input type="number" name="amount" id="bdAmount" min="1" step="1" value="{{ $data['amount'] ?? '' }}" placeholder="1150">
        </div>

        <div class="bd-f">
            <label>Price breakdown <span style="font-weight:600;color:var(--text-muted);">(optional)</span></label>
            <div class="bd-items" id="bdItems">
                @php $items = $data['breakdown'] ?? []; @endphp
                @forelse($items as $it)
                    <div class="bd-item">
                        <input type="text" name="item_label[]" value="{{ $it['label'] }}" placeholder="e.g. Staffing">
                        <input type="number" name="item_cost[]" value="{{ $it['cost'] }}" min="0" placeholder="0">
                        <button type="button" class="bd-x" onclick="this.closest('.bd-item').remove()">×</button>
                    </div>
                @empty
                    <div class="bd-item">
                        <input type="text" name="item_label[]" placeholder="e.g. Food &amp; beverages">
                        <input type="number" name="item_cost[]" min="0" placeholder="0">
                        <button type="button" class="bd-x" onclick="this.closest('.bd-item').remove()">×</button>
                    </div>
                @endforelse
            </div>
            <button type="button" class="bd-add" id="bdAdd">+ Add price item</button>
            <p class="bd-help">Rows left blank are ignored. The bid amount above is what the client sees as your price.</p>
        </div>

        <div class="bd-f" id="bdOver" style="display:none;">
            <label>Above-budget explanation <span class="req">*</span></label>
            <textarea name="above_budget_reason" style="min-height:90px;" placeholder="Explain the additional value, scope or costs…">{{ $data['above_budget_reason'] ?? '' }}</textarea>
        </div>

        <label class="bd-check">
            <input type="checkbox" name="sealed_ack" value="1" @checked(! empty($data['amount']))>
            <span><b>I understand my bid is sealed.</b> Only you and the client can see the amount, terms, files and negotiation. Competing professionals cannot.</span>
        </label>

        <div class="bd-money">
            <div><span>Gross bid</span><b id="bdGross">${{ number_format((int) ($data['amount'] ?? 0)) }}</b></div>
            <div class="neg"><span>Commission ({{ $rate }}%)</span><b id="bdFee">−$0</b></div>
            <div class="net"><span>Estimated net payout</span><b id="bdNet">$0</b></div>
        </div>
        <p class="bd-help">Commission is deducted at payout according to your membership plan. Submitting and negotiating is free.</p>

    {{-- ── 2 · Availability ────────────────────────────────── --}}
    @elseif($step === 'availability')
        <h3>Confirm availability</h3>
        <p class="lede">The client is comparing people who can actually do the date. Say so plainly, and flag anything conditional.</p>

        <label class="bd-check">
            <input type="checkbox" name="available_confirmed" value="1" @checked(! empty($data['available_confirmed']))>
            <span><b>I am available on {{ $event->starts_at?->format('M j, Y') ?? 'the requested date' }}</b>@if($event->location) in {{ $event->location }}@endif and can deliver the services requested.</span>
        </label>

        <div class="bd-f">
            <label>Anything the client should know about timing?</label>
            <textarea name="availability_note" style="min-height:100px;" placeholder="e.g. I can hold this date for 5 days, or I'd need access from 2pm for setup…">{{ $data['availability_note'] ?? '' }}</textarea>
        </div>

    {{-- ── 3 · Service plan ────────────────────────────────── --}}
    @elseif($step === 'plan')
        <h3>How will you deliver it?</h3>
        <p class="lede">This is the part that wins work. The client is told to compare scope and qualifications, not only price — give them something to compare.</p>
        <div class="bd-f">
            <label>Service plan <span class="req">*</span></label>
            <textarea name="plan" placeholder="What you'll provide, how many people, what equipment, how the day runs…">{{ $data['plan'] ?? '' }}</textarea>
            <p class="bd-help">Include what's covered and what isn't — unstated exclusions are where disputes start.</p>
        </div>

    {{-- ── 4 · Terms ───────────────────────────────────────── --}}
    @elseif($step === 'terms')
        <h3>Timeline &amp; terms</h3>
        <p class="lede">Deliverables, milestones, deposit expectations, cancellation — anything you'd want agreed before you start.</p>
        <div class="bd-f">
            <label>Terms <span style="font-weight:600;color:var(--text-muted);">(optional but recommended)</span></label>
            <textarea name="terms" placeholder="e.g. 30% deposit to hold the date, balance due 7 days before. Setup from 2pm. Cancellation inside 14 days forfeits the deposit.">{{ $data['terms'] ?? '' }}</textarea>
            <p class="bd-help">These become the starting point if the client selects you and you move to an agreement.</p>
        </div>

    {{-- ── 5 · Files ───────────────────────────────────────── --}}
    @elseif($step === 'files')
        <h3>Files</h3>
        <p class="lede">Sample menus, past work, insurance certificates.</p>
        {{-- No upload control: bids have no attachment model, so a picker here
             would take the file and drop it. Said plainly instead. --}}
        <div style="border:1px dashed var(--border-color);border-radius:12px;padding:34px 20px;text-align:center;">
            <b style="display:block;font-size:14px;color:var(--text-primary);margin-bottom:6px;">Attachments aren't available yet</b>
            <p style="font-size:12.5px;color:var(--text-muted);line-height:1.6;max-width:420px;margin:0 auto;">
                You can submit without them. If the client has questions, you can share documents in the message thread.
            </p>
        </div>

    {{-- ── 6 · Review ──────────────────────────────────────── --}}
    @elseif($step === 'review')
        <h3>Review &amp; submit</h3>
        <p class="lede">This is what the client sees. You can edit it until the deadline, or until an exclusive negotiation starts.</p>

        <div class="bd-rev"><span>Your bid</span><b>${{ number_format((int) ($data['amount'] ?? 0)) }}</b></div>
        @if($rate)<div class="bd-rev"><span>Commission ({{ $rate }}%)</span><b>−${{ number_format((int) ($data['amount'] ?? 0) - (int) ($net ?? 0)) }}</b></div>@endif
        <div class="bd-rev"><span>Estimated net payout</span><b>${{ number_format((int) ($net ?? 0)) }}</b></div>
        <div class="bd-rev"><span>Available on the date</span><b>{{ ! empty($data['available_confirmed']) ? 'Confirmed' : 'Not confirmed' }}</b></div>
        @if(! empty($data['breakdown']))
            <div class="bd-rev"><span>Breakdown</span><b>{{ count($data['breakdown']) }} {{ Str::plural('line', count($data['breakdown'])) }} · ${{ number_format(collect($data['breakdown'])->sum('cost')) }}</b></div>
        @endif

        @foreach([['plan', 'Service plan'], ['terms', 'Terms'], ['availability_note', 'Availability note']] as [$k, $label])
            @if(! empty($data[$k]))
                <div style="margin-top:14px;">
                    <label style="font-size:12.5px;font-weight:800;color:var(--text-primary);">{{ $label }}</label>
                    <p style="font-size:13px;color:var(--text-secondary);line-height:1.7;white-space:pre-line;margin-top:5px;">{{ $data[$k] }}</p>
                </div>
            @endif
        @endforeach

        <div class="bd-f" style="margin-top:18px;">
            <label>Message to the client <span style="font-weight:600;color:var(--text-muted);">(optional)</span></label>
            <textarea name="note" style="min-height:80px;" placeholder="A short note to go with your proposal…">{{ $data['note'] ?? '' }}</textarea>
        </div>

        {{-- R8: this is a real one-way door. The copy says it cannot be undone,
             so once it is set the control is locked rather than left tickable —
             the server ignores an attempt to turn it back off either way. --}}
        @if(! empty($existing?->is_public))
            <div class="bd-check" style="border-color:rgba(37,99,235,.35);">
                <span>🔓 <b>Your bid amount is public.</b> This was a one-way choice and can't be reversed.</span>
            </div>
        @else
            <label class="bd-check">
                <input type="checkbox" name="is_public" value="1">
                <span><b>Make my bid amount public.</b> Off by default, and sealed is recommended. <b>Once made public it cannot be undone.</b></span>
            </label>
        @endif

        <label class="bd-check" style="border-color:rgba(37,99,235,.35);background:rgba(37,99,235,.07);">
            <input type="checkbox" name="confirm" value="1">
            <span>I've checked my proposal. Submitting sends it to the client — it stays sealed from other professionals, and I can edit it until the deadline.</span>
        </label>
    @endif

        <div class="bd-nav">
            <div style="display:flex;gap:8px;">
                @if($prev)<a class="bd-btn" href="{{ route('professional.bid.step', [$event, $prev]) }}">Back</a>@endif
                <button type="submit" name="action" value="draft" class="bd-btn">Save draft</button>
            </div>
            <button type="submit" name="action" value="next" class="bd-btn go">
                {{ $step === 'review' ? 'Submit proposal' : 'Continue' }}
            </button>
        </div>
    </div>

    {{-- ── Right rail ──────────────────────────────────────── --}}
    <aside>
        <div class="bd-rail">
            <h4>Eligibility &amp; access</h4>
            @foreach($eligibility as [$label, $value, $ok])
                <div class="bd-el {{ $ok ? 'ok' : 'no' }}"><span>{{ $label }}</span><b>{{ $value }}</b></div>
            @endforeach
        </div>

        <div class="bd-rail">
            <h4>Your bid status</h4>
            <div class="bd-el"><span>State</span><b style="color:{{ $existing && $existing->submitted_at ? '#4ade80' : '#f59e0b' }};">
                {{ $existing && $existing->submitted_at ? 'Submitted' : 'Draft — not sent' }}
            </b></div>
            @if($event->proposal_deadline)
                <div class="bd-el"><span>Deadline</span><b>{{ $event->proposal_deadline->format('M j, g:i A') }}</b></div>
                <div class="bd-el"><span>Time left</span><b>{{ $event->proposal_deadline->diffForHumans(null, true) }}</b></div>
            @endif
        </div>

        <div class="bd-rail">
            <h4>Reminders</h4>
            <div class="bd-rem">🔒 Bids are sealed — competitors can't see your amount, terms or files.</div>
            <div class="bd-rem">🧾 One active bid per request. Saving again updates the same one.</div>
            <div class="bd-rem">✏️ Editable until the deadline, or until exclusive negotiation begins.</div>
            <div class="bd-rem">💸 Free to submit and negotiate. Commission is deducted at payout.</div>
        </div>
    </aside>
</div>
</form>

@if($step === 'price')
<script>
(function () {
    // Live money maths and the above-budget prompt. The same rules are enforced
    // server-side — this just avoids making the professional guess.
    var RATE    = {{ (float) $rate }};
    var CEILING = {{ $ceiling ? (float) $ceiling : 0 }};
    var amount  = document.getElementById('bdAmount');
    var over    = document.getElementById('bdOver');
    var gross   = document.getElementById('bdGross');
    var fee     = document.getElementById('bdFee');
    var net     = document.getElementById('bdNet');
    var items   = document.getElementById('bdItems');

    function money(n) { return '$' + Math.round(n).toLocaleString(); }

    function sync() {
        var a = parseFloat(amount.value) || 0;
        var f = a * RATE / 100;
        gross.textContent = money(a);
        fee.textContent   = '−' + money(f);
        net.textContent   = money(a - f);
        // Only ask for a justification when the bid actually exceeds the range.
        if (over) over.style.display = (CEILING > 0 && a > CEILING) ? '' : 'none';
    }

    amount.addEventListener('input', sync);
    sync();

    document.getElementById('bdAdd').addEventListener('click', function () {
        var row = items.firstElementChild.cloneNode(true);
        row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
        row.querySelector('.bd-x').onclick = function () { row.remove(); };
        items.appendChild(row);
    });
})();
</script>
@endif
@endsection
