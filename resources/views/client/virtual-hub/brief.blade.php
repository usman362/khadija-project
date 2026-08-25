@extends('layouts.client')

@section('title', 'Virtual & Hybrid Event Brief')
@section('page-title', 'Virtual & Hybrid Event Brief')
@section('page-subtitle', 'Post your event and technical needs.')

@push('styles')
<style>
    /* ═══════════ Virtual & Hybrid Event Brief — posting form ═══════════
       Matches the client's "Virtual & Hybrid Event Brief" mockup:
       a 4-section gig brief (Event Details · Technical Environment ·
       Production & Staffing · Budget & Bidding) with a 3-step header.
       UI scaffold — persistence + bidding backend is a follow-up. */

    /* Step header */
    .vhb-stepper { display: flex; align-items: center; gap: 0; margin-bottom: 22px; padding: 4px 2px; }
    .vhb-step { display: inline-flex; align-items: center; gap: 11px; flex-shrink: 0; }
    .vhb-step-num {
        width: 34px; height: 34px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 800; flex-shrink: 0;
        background: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-muted);
    }
    .vhb-step-label { font-size: 13px; font-weight: 700; color: var(--text-muted); white-space: nowrap; }
    .vhb-step.is-active .vhb-step-num { background: #f97316; border-color: #f97316; color: #fff; box-shadow: 0 4px 10px rgba(249,115,22,0.35); }
    .vhb-step.is-active .vhb-step-label { color: var(--brand-text); }
    .vhb-step.is-done .vhb-step-num { background: #f97316; border-color: #f97316; color: #fff; }
    .vhb-step-line { flex: 1; height: 2px; background: var(--border-color); margin: 0 16px; border-radius: 2px; min-width: 30px; }
    .vhb-step-line.is-active { background: #f97316; }

    /* 4-column card grid */
    .vhb-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; align-items: start; }
    .vhb-card {
        background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: var(--radius); padding: 20px 18px; min-width: 0;
    }
    .vhb-card-head { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
    .vhb-card-ico {
        width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
        background: rgba(249,115,22,0.12); color: var(--brand-text);
        display: flex; align-items: center; justify-content: center;
    }
    .vhb-card-ico svg { width: 20px; height: 20px; }
    .vhb-card-title { font-size: 12.5px; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.3px; line-height: 1.3; }
    .vhb-card-sub { font-size: 11.5px; color: var(--text-muted); line-height: 1.45; margin: 0 0 16px; }

    /* Fields */
    .vhb-field { margin-bottom: 14px; }
    .vhb-label { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
    .vhb-label .req { color: var(--brand-text); font-weight: 800; }
    .vhb-input, .vhb-select {
        width: 100%; height: 40px; padding: 0 12px;
        border-radius: 9px; border: 1px solid var(--border-color);
        background: var(--bg-card-hover); color: var(--text-primary);
        font-size: 12.5px; font-family: inherit; outline: none;
        text-overflow: ellipsis;
    }
    .vhb-input:focus, .vhb-select:focus { border-color: #f97316; }
    .vhb-input::placeholder { color: var(--text-muted); }
    .vhb-select { -webkit-appearance: none; appearance: none; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%2394a3b8' stroke-width='2.5' viewBox='0 0 24 24'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center; padding-right: 30px; }
    [data-theme="dark"] .vhb-select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23cbd5e1' stroke-width='2.5' viewBox='0 0 24 24'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); }

    /* Input with icon(s) */
    .vhb-iwrap { position: relative; }
    .vhb-iwrap .ico-l, .vhb-iwrap .ico-r { position: absolute; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); pointer-events: none; }
    .vhb-iwrap .ico-l { left: 12px; }
    .vhb-iwrap .ico-r { right: 12px; }
    .vhb-iwrap.has-l .vhb-input { padding-left: 34px; }
    .vhb-iwrap.has-r .vhb-input { padding-right: 34px; }

    /* Sub-section heading inside a card */
    .vhb-subhead { font-size: 12px; font-weight: 700; color: var(--text-primary); margin: 2px 0 11px; line-height: 1.4; }
    .vhb-subhead .req { color: var(--brand-text); font-weight: 800; }
    .vhb-subhead .hint { font-weight: 500; color: var(--text-muted); font-size: 11px; }

    /* Option lists (radios + checkboxes) */
    .vhb-opts { display: flex; flex-direction: column; gap: 11px; margin-bottom: 12px; }
    .vhb-opt { display: flex; align-items: flex-start; gap: 10px; cursor: pointer; }
    .vhb-opt-text { font-size: 12px; color: var(--text-primary); line-height: 1.4; }
    .vhb-opt-text small { display: block; font-size: 11px; color: var(--text-muted); font-weight: 400; margin-top: 2px; }
    .vhb-opt-text b { font-weight: 700; }

    /* Custom orange radio + checkbox */
    .vhb-opt input[type=radio], .vhb-opt input[type=checkbox] {
        appearance: none; -webkit-appearance: none;
        width: 17px; height: 17px; flex-shrink: 0; margin: 1px 0 0;
        border: 1.5px solid var(--border-color); background: #fff;
        cursor: pointer; position: relative; transition: border-color 0.15s, background 0.15s;
    }
    .vhb-opt input[type=radio] { border-radius: 50%; }
    .vhb-opt input[type=checkbox] { border-radius: 5px; }
    .vhb-opt input[type=radio]:checked { border-color: #f97316; background: #f97316; }
    .vhb-opt input[type=radio]:checked::after { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 5px; height: 5px; border-radius: 50%; background: #fff; }
    .vhb-opt input[type=checkbox]:checked { border-color: #f97316; background: #f97316; }
    .vhb-opt input[type=checkbox]:checked::after { content: ''; position: absolute; left: 5px; top: 1.5px; width: 4px; height: 8px; border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg); }
    .vhb-opt input:focus-visible { outline: 2px solid rgba(249,115,22,0.4); outline-offset: 1px; }
    [data-theme="dark"] .vhb-opt input[type=radio], [data-theme="dark"] .vhb-opt input[type=checkbox] { background: var(--bg-card-hover); }
    [data-theme="dark"] .vhb-opt input:checked { background: #f97316; }

    /* "Other — please specify" inline field */
    .vhb-other { margin: 2px 0 4px; }

    /* Tip / boost callout (peach) */
    .vhb-callout { display: flex; gap: 10px; background: rgba(249,115,22,0.07); border: 1px solid rgba(249,115,22,0.20); border-radius: 10px; padding: 12px 13px; }
    .vhb-callout svg { width: 16px; height: 16px; color: var(--brand-text); flex-shrink: 0; margin-top: 1px; }
    .vhb-callout-body { font-size: 11px; color: var(--text-secondary); line-height: 1.5; }
    .vhb-callout-body b { color: var(--text-primary); font-weight: 700; }
    .vhb-callout-title { display: block; font-weight: 800; color: var(--text-primary); margin-bottom: 2px; }

    /* Divider between sub-sections in a card */
    .vhb-divider { height: 1px; background: var(--border-color); margin: 16px 0; }

    /* Budget row */
    .vhb-budget-row { display: flex; align-items: center; gap: 8px; }
    .vhb-budget-row .vhb-select { width: 84px; flex-shrink: 0; padding-left: 10px; }
    .vhb-budget-row .vhb-input { text-align: left; }
    .vhb-budget-row .to { font-size: 12px; color: var(--text-muted); flex-shrink: 0; }

    /* Dual-thumb budget slider (visual mock) */
    .vhb-range { position: relative; height: 18px; margin: 14px 2px 0; }
    .vhb-range-track { position: absolute; top: 50%; left: 0; right: 0; height: 4px; transform: translateY(-50%); background: var(--border-color); border-radius: 999px; }
    .vhb-range-fill { position: absolute; top: 50%; height: 4px; transform: translateY(-50%); background: #f97316; border-radius: 999px; }
    .vhb-range-thumb { position: absolute; top: 50%; width: 16px; height: 16px; transform: translate(-50%,-50%); background: #f97316; border: 3px solid var(--bg-card); border-radius: 50%; box-shadow: 0 2px 6px rgba(0,0,0,0.20); }
    .vhb-range-marks { display: flex; justify-content: space-between; font-size: 10.5px; color: var(--text-muted); margin-top: 6px; padding: 0 2px; }

    /* Footer actions */
    .vhb-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 22px; }
    .vhb-btn { padding: 11px 22px; border-radius: 9px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border: 1px solid var(--border-color); }
    .vhb-btn svg { width: 15px; height: 15px; }
    .vhb-btn.ghost { background: var(--bg-card); color: var(--text-primary); }
    .vhb-btn.ghost:hover { background: var(--bg-card-hover); }
    .vhb-btn.primary { background: #f97316; color: #fff; border-color: #f97316; }
    .vhb-btn.primary:hover { background: #ea580c; }

    @media (max-width: 1280px) { .vhb-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 720px)  { .vhb-grid { grid-template-columns: 1fr; } .vhb-stepper { flex-wrap: wrap; gap: 10px 0; } .vhb-step-line { min-width: 16px; margin: 0 8px; } }
</style>
@endpush

@section('content')
{{-- Two steps, the way the client's workflow draws them: Plan, then Services,
     with a Continue between. They were one page, so submitting looked like a
     jump from step 2 straight to step 4 with step 3 never seen. --}}
@include('client.virtual-hub._stages', ['current' => $step === 'plan' ? 2 : 3, 'event' => null])

@if($errors->any())
    <div class="vhb-card" id="vhb-errors" style="border-color:#ef4444;max-width:820px;">
        <b style="display:block;margin-bottom:6px;">Please fix these first</b>
        <ul style="margin:0;padding-left:18px;font-size:13px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

@if($step === 'plan')
    {{-- ── Step 2 · Plan ───────────────────────────────── --}}
    <form method="POST" action="{{ route('client.virtual-hub.save', 'plan') }}" class="vhb">
        @csrf
        <div class="vhb-card">
            <div class="vhb-card-head"><span class="vhb-step">2</span> Tell us about your event</div>

            <label class="vhb-label" for="vhb-title">Event name *</label>
            <input type="text" id="vhb-title" name="title" class="vhb-input" required maxlength="200"
                   value="{{ old('title', $draft['title'] ?? '') }}" placeholder="e.g. Annual Leadership Conference">
            @error('title')<p class="vhb-err">{{ $message }}</p>@enderror

            <label class="vhb-label">Event format *</label>
            <div class="vhb-formats">
                @foreach(['virtual' => 'Fully virtual', 'hybrid' => 'Hybrid — in person and online'] as $val => $text)
                    <label class="vhb-opt">
                        <input type="radio" name="event_format" value="{{ $val }}" required
                               {{ old('event_format', $draft['event_format'] ?? '') === $val ? 'checked' : '' }}
                               onchange="document.getElementById('vhb-venue').style.display = this.value === 'hybrid' ? 'block' : 'none';">
                        <span class="vhb-opt-text">{{ $text }}</span>
                    </label>
                @endforeach
            </div>
            @error('event_format')<p class="vhb-err">{{ $message }}</p>@enderror

            <div class="vhb-grid-2">
                <div>
                    <label class="vhb-label" for="vhb-type">Event type</label>
                    <input type="text" id="vhb-type" name="event_type" class="vhb-input" maxlength="80"
                           value="{{ old('event_type', $draft['event_type'] ?? '') }}" placeholder="e.g. Conference">
                    @error('event_type')<p class="vhb-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="vhb-label" for="vhb-guests">Expected attendance</label>
                    <input type="number" id="vhb-guests" name="guest_count" class="vhb-input" min="1"
                           value="{{ old('guest_count', $draft['guest_count'] ?? '') }}" placeholder="e.g. 150">
                </div>
            </div>

            <div class="vhb-grid-2">
                <div>
                    <label class="vhb-label" for="vhb-starts">Starts *</label>
                    <input type="datetime-local" id="vhb-starts" name="starts_at" class="vhb-input" required
                           value="{{ old('starts_at', $draft['starts_at'] ?? '') }}">
                    @error('starts_at')<p class="vhb-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="vhb-label" for="vhb-ends">Ends</label>
                    <input type="datetime-local" id="vhb-ends" name="ends_at" class="vhb-input"
                           value="{{ old('ends_at', $draft['ends_at'] ?? '') }}">
                    @error('ends_at')<p class="vhb-err">{{ $message }}</p>@enderror
                </div>
            </div>

            <div id="vhb-venue" style="display:{{ old('event_format', $draft['event_format'] ?? '') === 'hybrid' ? 'block' : 'none' }};">
                <label class="vhb-label" for="vhb-loc">Venue for the in-person half *</label>
                <input type="text" id="vhb-loc" name="location" class="vhb-input" maxlength="200"
                       value="{{ old('location', $draft['location'] ?? '') }}" placeholder="e.g. Baltimore, MD">
                @error('location')<p class="vhb-err">{{ $message }}</p>@enderror
            </div>

            <div class="vhb-grid-2">
                <div>
                    <label class="vhb-label" for="vhb-platform">Platform, if you know it</label>
                    <select name="platform" id="vhb-platform" class="vhb-select">
                        <option value="">Not decided yet</option>
                        @foreach(['Zoom', 'Microsoft Teams', 'Google Meet', 'Webex', 'Hopin', 'YouTube Live', 'Other'] as $pf)
                            <option value="{{ $pf }}" @selected(old('platform', $draft['platform'] ?? '') === $pf)>{{ $pf }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="vhb-label" for="vhb-url">Joining link, if you have one</label>
                    <input type="url" id="vhb-url" name="meeting_url" class="vhb-input" maxlength="500"
                           value="{{ old('meeting_url', $draft['meeting_url'] ?? '') }}" placeholder="https://…">
                    @error('meeting_url')<p class="vhb-err">{{ $message }}</p>@enderror
                    <p class="vhb-hint">We only store what you paste. Nothing here creates a meeting for you.</p>
                </div>
            </div>
        </div>

        <div class="vhb-actions">
            <a href="{{ route('client.virtual-hub.index') }}" class="vhb-btn-ghost">Cancel</a>
            <button type="submit" class="vhb-btn">Continue to services →</button>
        </div>
    </form>

@else
    {{-- ── Step 3 · Services ───────────────────────────── --}}
    <form method="POST" action="{{ route('client.virtual-hub.save', 'services') }}" class="vhb">
        @csrf

        {{-- What they told us a moment ago, so they are not choosing blind. --}}
        <div class="vhb-recap">
            <b>{{ $draft['title'] }}</b>
            <span>
                {{ ucfirst($draft['event_format']) }}
                @if(! empty($draft['starts_at'])) · {{ \Illuminate\Support\Carbon::parse($draft['starts_at'])->format('M j, Y · g:i A') }} @endif
                @if(! empty($draft['guest_count'])) · {{ $draft['guest_count'] }} attending @endif
            </span>
            <a href="{{ route('client.virtual-hub.brief', 'plan') }}">Edit</a>
        </div>

        <div class="vhb-card">
            <div class="vhb-card-head"><span class="vhb-step">3</span> What services do you need?</div>
            <p class="vhb-hint" style="margin-top:-4px;">Pick one or more. Professionals bid on what you choose.</p>

            @error('services')<p class="vhb-err" style="margin-bottom:8px;">{{ $message }}</p>@enderror
            <x-service-picker :categories="$services" name="services" :selected="old('services', [])" />
        </div>

        <div class="vhb-card">
            <div class="vhb-card-head">Anything else they should know?</div>
            <textarea name="description" class="vhb-input" rows="4" maxlength="5000"
                      placeholder="Run of show, rehearsal needs, accessibility requirements…">{{ old('description') }}</textarea>

            <div class="vhb-grid-2">
                <div>
                    <label class="vhb-label" for="vhb-bmin">Budget from</label>
                    <input type="number" id="vhb-bmin" name="budget_min" class="vhb-input" min="0" step="1" value="{{ old('budget_min') }}">
                </div>
                <div>
                    <label class="vhb-label" for="vhb-bmax">Budget to</label>
                    <input type="number" id="vhb-bmax" name="budget_max" class="vhb-input" min="0" step="1" value="{{ old('budget_max') }}">
                    @error('budget_max')<p class="vhb-err">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="vhb-actions">
            <a href="{{ route('client.virtual-hub.brief', 'plan') }}" class="vhb-btn-ghost">← Back</a>
            <button type="submit" class="vhb-btn">Post event &amp; get proposals →</button>
        </div>
    </form>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var box = document.getElementById('vhb-errors');
        if (box) { box.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    });
</script>
<style>
    .vhb{max-width:820px;}
    .vhb-err{font-size:12px;color:#dc2626;margin:5px 0 0;font-weight:600;}
    .vhb-card{background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;padding:20px 22px;margin-bottom:16px;}
    .vhb-card-head{font-size:15.5px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:9px;}
    .vhb-step{display:inline-flex;width:23px;height:23px;border-radius:50%;background:var(--accent-orange,#f97316);
        color:#fff;align-items:center;justify-content:center;font-size:12px;font-weight:800;}
    .vhb-label{display:block;font-size:12.5px;font-weight:700;margin:14px 0 5px;}
    .vhb-input,.vhb-select{width:100%;padding:10px 12px;font:inherit;font-size:14px;color:var(--text-primary);
        background:var(--bg-body);border:1px solid var(--border-color);border-radius:9px;outline:none;}
    .vhb-input:focus,.vhb-select:focus{border-color:var(--accent-blue);}
    .vhb-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    @media(max-width:640px){.vhb-grid-2{grid-template-columns:1fr;}}
    .vhb-formats{display:flex;gap:10px;flex-wrap:wrap;}
    .vhb-opt{display:flex;align-items:center;gap:8px;border:1px solid var(--border-color);border-radius:10px;
        padding:10px 14px;cursor:pointer;font-size:13.5px;}
    .vhb-opt:has(input:checked){border-color:var(--accent-orange,#f97316);background:rgba(249,115,22,.07);font-weight:700;}
    .vhb-hint{font-size:11.5px;color:var(--text-muted);margin:5px 0 0;}
    .vhb-recap{display:flex;align-items:center;gap:12px;flex-wrap:wrap;max-width:820px;
        border:1px solid var(--border-color);border-radius:12px;padding:12px 16px;margin-bottom:16px;}
    .vhb-recap b{font-size:14px;}
    .vhb-recap span{font-size:12.5px;color:var(--text-muted);flex:1;min-width:180px;}
    .vhb-recap a{font-size:12.5px;font-weight:700;}
    .vhb-actions{display:flex;gap:10px;justify-content:flex-end;align-items:center;max-width:820px;}
    .vhb-btn{border:none;border-radius:10px;padding:11px 20px;background:var(--accent-orange,#f97316);
        color:#fff;font-weight:800;font-size:14px;cursor:pointer;font-family:inherit;}
    .vhb-btn-ghost{padding:11px 18px;border:1px solid var(--border-color);border-radius:10px;
        text-decoration:none;color:var(--text-primary);font-size:13.5px;font-weight:600;}
</style>
@endsection
