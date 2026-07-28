@extends('layouts.client')

@section('title', 'Create a Bidding Service Request')
@section('page-title', 'Create a Request')

{{-- Screen 1 of the client BSR set — the 7-step create wizard.

     One wizard, not two: a BSR is the same request type whether it needs one
     service or several. Scope (SSR / MSR) follows from how many services get
     picked in step 1, which is literally what single vs multi service means, so
     it isn't asked as a separate question and there is no "switch to the other
     form" detour. --}}

@section('content')
<style>
    .bw-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
    .bw-h { font-size: 24px; font-weight: 800; color: var(--text-primary); }
    .bw-sub { font-size: 13.5px; color: var(--text-secondary); margin-top: 5px; max-width: 620px; line-height: 1.6; }
    .bw-acts { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

    .bw-steps { display: flex; gap: 4px; overflow-x: auto; padding-bottom: 4px; margin-bottom: 20px; }
    .bw-step { flex: 1 1 0; min-width: 108px; text-align: center; text-decoration: none; }
    .bw-dot { width: 30px; height: 30px; margin: 0 auto 6px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12.5px; font-weight: 800; background: var(--bg-subtle, rgba(0,0,0,.05)); color: var(--text-muted); border: 1px solid var(--border-color); }
    .bw-step.done .bw-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
    .bw-step.on .bw-dot { background: #f97316; border-color: #f97316; color: #fff; }
    .bw-step small { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-muted); }
    .bw-step.on small { color: #f97316; }
    .bw-step.done small { color: var(--text-secondary); }

    .bw-grid { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 20px; align-items: start; }
    @media (max-width: 1000px) { .bw-grid { grid-template-columns: minmax(0,1fr); } }

    .bw-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 22px 24px; }
    .bw-card h3 { font-size: 18px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
    .bw-card .lede { font-size: 13.5px; color: var(--text-secondary); line-height: 1.65; margin-bottom: 18px; }
    .bw-field { margin-bottom: 16px; }
    .bw-field label { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
    .bw-field .req { color: #dc2626; }
    .bw-field input[type=text], .bw-field input[type=number], .bw-field input[type=datetime-local],
    .bw-field select, .bw-field textarea {
        width: 100%; background: var(--bg-page, transparent); border: 1px solid var(--border-color);
        border-radius: 10px; padding: 10px 12px; font-size: 13.5px; color: var(--text-primary); font-family: inherit;
    }
    .bw-field textarea { min-height: 130px; resize: vertical; line-height: 1.6; }
    .bw-help { font-size: 12px; color: var(--text-muted); margin-top: 5px; line-height: 1.5; }
    .bw-two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 620px) { .bw-two { grid-template-columns: 1fr; } }

    .bw-opts { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
    .bw-opt { position: relative; border: 1px solid var(--border-color); border-radius: 12px; padding: 13px 14px; cursor: pointer; }
    .bw-opt input { position: absolute; opacity: 0; }
    .bw-opt b { display: block; font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 3px; }
    .bw-opt span { font-size: 11.5px; color: var(--text-muted); line-height: 1.45; }
    .bw-opt:has(input:checked) { border-color: #f97316; box-shadow: 0 0 0 1px #f97316 inset; background: rgba(249,115,22,.05); }

    .bw-svc { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 8px; max-height: 320px; overflow-y: auto; padding: 4px; border: 1px solid var(--border-color); border-radius: 12px; }
    .bw-svc label { display: flex; gap: 8px; align-items: center; padding: 8px 10px; border-radius: 9px; font-size: 13px; color: var(--text-secondary); cursor: pointer; }
    .bw-svc label:has(input:checked) { background: rgba(249,115,22,.09); color: var(--text-primary); font-weight: 700; }

    .bw-scope { display: flex; gap: 8px; align-items: center; background: rgba(37,99,235,.07); border: 1px solid rgba(37,99,235,.2); border-radius: 11px; padding: 10px 14px; font-size: 12.5px; color: var(--text-secondary); margin-top: 12px; }
    .bw-scope b { color: var(--text-primary); }

    .bw-nav { display: flex; justify-content: space-between; gap: 10px; margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--border-color); flex-wrap: wrap; }
    .bw-btn { border: 1px solid var(--border-color); background: transparent; border-radius: 10px; padding: 10px 18px; font-size: 13px; font-weight: 700; color: var(--text-secondary); text-decoration: none; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; }
    .bw-btn.go { background: #f97316; border-color: #f97316; color: #fff; font-weight: 800; }

    .bw-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 18px; margin-bottom: 14px; }
    .bw-rail-card h4 { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 10px; }
    .bw-rule { display: flex; gap: 9px; margin-bottom: 12px; }
    .bw-rule:last-child { margin-bottom: 0; }
    .bw-rule i { font-style: normal; font-size: 15px; }
    .bw-rule b { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .bw-rule span { font-size: 11.5px; color: var(--text-muted); line-height: 1.5; }
    .bw-bar { height: 6px; border-radius: 99px; background: var(--bg-subtle, rgba(0,0,0,.07)); overflow: hidden; margin: 8px 0 6px; }
    .bw-bar i { display: block; height: 100%; background: #f97316; }

    .bw-rev { display: flex; justify-content: space-between; gap: 16px; padding: 9px 0; border-bottom: 1px solid var(--border-color); font-size: 13px; }
    .bw-rev:last-of-type { border-bottom: 0; }
    .bw-rev span { color: var(--text-muted); font-weight: 600; }
    .bw-rev b { color: var(--text-primary); text-align: right; }
    .bw-confirm { display: flex; gap: 9px; align-items: flex-start; background: rgba(249,115,22,.06); border: 1px solid rgba(249,115,22,.25); border-radius: 11px; padding: 12px 14px; margin-top: 16px; font-size: 12.5px; color: var(--text-secondary); line-height: 1.6; }
</style>

@if(session('status'))
    <div class="cl-card" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px 16px;margin-bottom:16px;font-size:13.5px;">✅ {{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="cl-card" style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;margin-bottom:16px;font-size:13.5px;">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

@php
    $keys = array_keys($steps);
    $prev = $stepIndex > 0 ? $keys[$stepIndex - 1] : null;
    $pct  = (int) round(($stepIndex / (count($steps) - 1)) * 100);
@endphp

<div class="bw-top">
    <div>
        <div class="bw-h">Create a Bidding Service Request</div>
        <p class="bw-sub">Tell professionals what you need, when and where — they review it and send you sealed proposals to compare.</p>
    </div>
    <div class="bw-acts">
        @if($draftId)<span style="font-size:12px;color:#16a34a;font-weight:700;">✓ Draft saved</span>@endif
        <a class="bw-btn" href="{{ route('client.events.index') }}">Exit</a>
    </div>
</div>

<div class="bw-steps">
    @foreach($steps as $key => $label)
        @php $i = $loop->index; @endphp
        <a class="bw-step {{ $i === $stepIndex ? 'on' : ($i < $stepIndex ? 'done' : '') }}"
           href="{{ $i <= $stepIndex ? route('client.bsr.step', $key) : 'javascript:void(0)' }}">
            <span class="bw-dot">{{ $i < $stepIndex ? '✓' : $i + 1 }}</span>
            <small>{{ $label }}</small>
        </a>
    @endforeach
</div>

<form method="POST" action="{{ route('client.bsr.save', $step) }}">
@csrf
<div class="bw-grid">
    <div class="bw-card">

    {{-- ── 1 · Service ─────────────────────────────────────── --}}
    @if($step === 'service')
        <h3>What do you need?</h3>
        <p class="lede">Pick one service for a single-service request, or several for a multi-service one — it's the same request either way.</p>

        <div class="bw-field">
            <label>Services <span class="req">*</span></label>
            <div class="bw-svc" id="bwSvc">
                @foreach($categories as $c)
                    <label>
                        <input type="checkbox" name="services[]" value="{{ $c->id }}"
                               @checked(in_array($c->id, (array) ($data['services'] ?? [])))>
                        {{ $c->name }}
                    </label>
                @endforeach
            </div>
            <div class="bw-scope">
                <span id="bwScope">Pick your services — the scope follows automatically.</span>
            </div>
        </div>

        <div class="bw-two">
            <div class="bw-field">
                <label>Event type</label>
                <select name="event_type">
                    <option value="">Not sure yet</option>
                    @foreach($eventTypes as $t)
                        <option value="{{ $t->name }}" @selected(($data['event_type'] ?? '') === $t->name)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bw-field">
                <label>This request is for <span class="req">*</span></label>
                <select name="organization_type">
                    @foreach($orgTypes as $k => $l)
                        <option value="{{ $k }}" @selected(($data['organization_type'] ?? 'individual') === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bw-field">
            <label>Request characteristic <span class="req">*</span></label>
            <div class="bw-opts">
                @foreach($characteristics as $k => [$label, $desc])
                    <label class="bw-opt">
                        <input type="radio" name="characteristic" value="{{ $k }}"
                               @checked(($data['characteristic'] ?? 'standard') === $k)>
                        <b>{{ $label }}</b><span>{{ $desc }}</span>
                    </label>
                @endforeach
            </div>
            {{-- No "Emergency" option: emergency is its own request type (ESR),
                 which broadcasts on a rush timeline, not a flavour of a BSR. --}}
            <p class="bw-help">Need it urgently because something fell through? <a href="{{ route('client.esr.create') }}" style="color:#f97316;font-weight:700;">Post an emergency request</a> instead.</p>
        </div>

    {{-- ── 2 · Event details ───────────────────────────────── --}}
    @elseif($step === 'event')
        <h3>About the event</h3>
        <p class="lede">The basics professionals need to know before they can price anything.</p>

        <div class="bw-field">
            <label>Request name <span class="req">*</span></label>
            <input type="text" name="title" value="{{ $data['title'] ?? '' }}" placeholder="e.g. Annual Company Picnic">
        </div>
        <div class="bw-two">
            <div class="bw-field">
                <label>Event date &amp; time</label>
                <input type="datetime-local" name="starts_at" value="{{ $data['starts_at'] ?? '' }}">
                <p class="bw-help">Leave blank if the date is still flexible.</p>
            </div>
            <div class="bw-field">
                <label>Guest count</label>
                <input type="number" name="guest_count" min="1" value="{{ $data['guest_count'] ?? '' }}" placeholder="150">
            </div>
        </div>
        <div class="bw-two">
            <div class="bw-field">
                <label>Location</label>
                <input type="text" name="location" value="{{ $data['location'] ?? '' }}" placeholder="Baltimore, MD">
            </div>
            <div class="bw-field">
                <label>Venue</label>
                <input type="text" name="venue" value="{{ $data['venue'] ?? '' }}" placeholder="Outdoor park (confirmed)">
            </div>
        </div>

    {{-- ── 3 · Requirements ────────────────────────────────── --}}
    @elseif($step === 'requirements')
        <h3>What should professionals know?</h3>
        <p class="lede">This is what they read before deciding whether to bid, and what they price against. The more specific, the more accurate the proposals.</p>
        <div class="bw-field">
            <label>Description <span class="req">*</span></label>
            <textarea name="description" placeholder="What the event is, what you need delivered, anything that would change the price…">{{ $data['description'] ?? '' }}</textarea>
            <p class="bw-help">At least a couple of sentences. Include anything that affects scope — access, timings, equipment, dietary needs.</p>
        </div>

    {{-- ── 4 · Budget ──────────────────────────────────────── --}}
    @elseif($step === 'budget')
        <h3>Budget</h3>
        <p class="lede">A range is optional, but requests that show one get more accurate proposals. Professionals may bid above it with an explanation.</p>
        <div class="bw-two">
            <div class="bw-field">
                <label>Budget from</label>
                <input type="number" name="budget_min" min="0" step="1" value="{{ $data['budget_min'] ?? '' }}" placeholder="800">
            </div>
            <div class="bw-field">
                <label>Budget to</label>
                <input type="number" name="budget_max" min="0" step="1" value="{{ $data['budget_max'] ?? '' }}" placeholder="1200">
            </div>
        </div>
        <div class="bw-scope">
            💡 <span>Posting is free. A <b>$2.99</b> service fee applies only when you finalize with a professional — and nothing at all if you don't book.</span>
        </div>

    {{-- ── 5 · Proposal settings ───────────────────────────── --}}
    @elseif($step === 'proposals')
        <h3>How proposals work</h3>
        <p class="lede">When bidding closes, and what professionals can do while it's open.</p>

        <div class="bw-field">
            {{-- R37: no window value may be prefilled until GigResource approves
                 one, so with none configured the client sets the deadline. --}}
            <label>Proposal deadline @unless($defaultWindowDays)<span class="req">*</span>@endunless</label>
            <input type="datetime-local" name="proposal_deadline" value="{{ $data['proposal_deadline'] ?? '' }}">
            <p class="bw-help">
                @if($defaultWindowDays)
                    Leave blank to use the standard {{ $defaultWindowDays }}-day window.
                @else
                    Choose when proposals close. No standard window has been approved yet, so this can't be set for you.
                @endif
                A deadline can never fall after the event date — if it would, it's pulled back automatically.
            </p>
        </div>

        <div class="bw-field">
            <label class="bw-opt" style="display:block;">
                <input type="checkbox" name="sealed_proposals" value="1" @checked((bool) ($data['sealed_proposals'] ?? true))>
                <b>Sealed proposals</b>
                <span>Each amount is visible only to you and the professional who sent it. Competitors can't see each other's bids. Recommended.</span>
            </label>
        </div>
        <div class="bw-field">
            <label class="bw-opt" style="display:block;">
                <input type="checkbox" name="questions_enabled" value="1" @checked((bool) ($data['questions_enabled'] ?? true))>
                <b>Allow clarifying questions</b>
                <span>Professionals can ask you something before bidding. Answers go only to the person who asked.</span>
            </label>
        </div>

    {{-- ── 6 · Files ───────────────────────────────────────── --}}
    @elseif($step === 'files')
        <h3>Files</h3>
        <p class="lede">Briefs, floor plans and reference documents.</p>
        {{-- No upload control: requests have no attachment model yet, so a file
             picker here would take the file and drop it. Said plainly instead. --}}
        <div style="border:1px dashed var(--border-color);border-radius:12px;padding:36px 20px;text-align:center;">
            <b style="display:block;font-size:14.5px;color:var(--text-primary);margin-bottom:6px;">Attachments aren't available yet</b>
            <p style="font-size:13px;color:var(--text-muted);line-height:1.6;max-width:420px;margin:0 auto;">
                You can publish without them. Once a professional is in touch, share documents in the message thread —
                everything else about your request works normally.
            </p>
        </div>

    {{-- ── 7 · Review ──────────────────────────────────────── --}}
    @elseif($step === 'review')
        @php
            $svcNames = $categories->whereIn('id', (array) ($data['services'] ?? []))->pluck('name');
            $isMulti  = count((array) ($data['services'] ?? [])) >= 2;
        @endphp
        <h3>Review &amp; publish</h3>
        <p class="lede">This is what professionals will see. You can edit any of it after publishing, right up until you choose someone.</p>

        <div class="bw-rev"><span>Request type</span><b>BSR — open to bidding</b></div>
        <div class="bw-rev"><span>Scope</span><b>{{ $isMulti ? 'MSR — multi-service' : 'SSR — single service' }}</b></div>
        <div class="bw-rev"><span>Services</span><b>{{ $svcNames->implode(', ') ?: '—' }}</b></div>
        <div class="bw-rev"><span>Name</span><b>{{ $data['title'] ?? '—' }}</b></div>
        <div class="bw-rev"><span>Event date</span><b>{{ ! empty($data['starts_at']) ? \Illuminate\Support\Carbon::parse($data['starts_at'])->format('M j, Y · g:i A') : 'Flexible' }}</b></div>
        <div class="bw-rev"><span>Location</span><b>{{ $data['location'] ?? '—' }}{{ ! empty($data['venue']) ? ' · ' . $data['venue'] : '' }}</b></div>
        <div class="bw-rev"><span>Guests</span><b>{{ ! empty($data['guest_count']) ? number_format($data['guest_count']) : '—' }}</b></div>
        <div class="bw-rev"><span>Budget</span><b>
            @if(! empty($data['budget_min']) || ! empty($data['budget_max']))
                ${{ number_format((float) ($data['budget_min'] ?? 0)) }} – ${{ number_format((float) ($data['budget_max'] ?? 0)) }}
            @else Not stated @endif
        </b></div>
        <div class="bw-rev"><span>Proposal deadline</span><b>{{ ! empty($data['proposal_deadline']) ? \Illuminate\Support\Carbon::parse($data['proposal_deadline'])->format('M j, Y · g:i A') : ($defaultWindowDays ? 'Standard ' . $defaultWindowDays . '-day window' : 'Not set') }}</b></div>
        <div class="bw-rev"><span>Proposals</span><b>{{ ($data['sealed_proposals'] ?? true) ? 'Sealed' : 'Open' }} · questions {{ ($data['questions_enabled'] ?? true) ? 'allowed' : 'off' }}</b></div>

        @if(! empty($data['description']))
            <div style="margin-top:16px;">
                <label style="font-size:12.5px;font-weight:800;color:var(--text-primary);">Description</label>
                <p style="font-size:13.5px;color:var(--text-secondary);line-height:1.7;white-space:pre-line;margin-top:6px;">{{ $data['description'] }}</p>
            </div>
        @endif

        <label class="bw-confirm">
            <input type="checkbox" name="confirm" value="1" style="margin-top:2px;">
            <span>I've checked these details. Publishing notifies eligible professionals and opens the request for proposals — it's free, and the $2.99 fee applies only if I finalize with someone.</span>
        </label>
    @endif

        <div class="bw-nav">
            <div style="display:flex;gap:8px;">
                @if($prev)<a class="bw-btn" href="{{ route('client.bsr.step', $prev) }}">Back</a>@endif
                <button type="submit" name="action" value="draft" class="bw-btn">Save draft</button>
            </div>
            <button type="submit" name="action" value="next" class="bw-btn go">
                {{ $step === 'review' ? 'Publish request' : 'Continue' }}
            </button>
        </div>
    </div>

    {{-- ── Right rail ──────────────────────────────────────── --}}
    <aside>
        <div class="bw-rail-card">
            <h4>Step {{ $stepIndex + 1 }} of {{ count($steps) }}</h4>
            <div class="bw-bar"><i style="width: {{ $pct }}%;"></i></div>
            <p style="font-size:11.5px;color:var(--text-muted);">{{ $pct }}% complete</p>
        </div>

        <div class="bw-rail-card">
            <h4>How this works</h4>
            <div class="bw-rule"><i>🔒</i><div><b>Sealed proposals</b><span>Amounts are visible only to you and the professional who sent them.</span></div></div>
            <div class="bw-rule"><i>💸</i><div><b>Free to post</b><span>$2.99 applies only when you finalize with a professional. Nothing books, nothing charged.</span></div></div>
            <div class="bw-rule"><i>✏️</i><div><b>Editable until you choose</b><span>Change details or back out any time before you select someone.</span></div></div>
            <div class="bw-rule"><i>⏱️</i><div><b>Deadline before the event</b><span>Proposals always close before the event date.</span></div></div>
        </div>
    </aside>
</div>
</form>

@if($step === 'service')
<script>
(function () {
    // Scope is derived, not asked. Show the client what their picks mean as
    // they make them, so "SSR" and "MSR" aren't jargon they have to decode.
    var box = document.getElementById('bwSvc');
    var out = document.getElementById('bwScope');
    if (!box || !out) return;
    function sync() {
        var n = box.querySelectorAll('input:checked').length;
        out.innerHTML = n === 0
            ? 'Pick your services — the scope follows automatically.'
            : (n === 1
                ? 'One service — this will post as an <b>SSR</b> (single service request).'
                : n + ' services — this will post as an <b>MSR</b> (multi-service request). Professionals bid per service.');
    }
    box.addEventListener('change', sync);
    sync();
})();
</script>
@endif
@endsection
