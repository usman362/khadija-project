@extends('layouts.client')

@section('title', 'Create a Bidding Request')
@section('page-title', 'Create a Bidding Request')
@section('page-subtitle', 'Tell professionals what you need, when and where — they review it and send you sealed proposals to compare.')

{{-- Screen 1 of the client BR set — the 7-step create wizard.

     One wizard, not two: a BR is the same request type whether it needs one
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
    .bw-step.done .bw-dot { background: #15803d; border-color: #16a34a; color: #fff; }
    .bw-step.on .bw-dot { background: #f97316; border-color: #f97316; color: #fff; }
    .bw-step small { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-muted); }
    .bw-step.on small { color: var(--brand-text); }
    .bw-step.done small { color: var(--text-secondary); }

    .bw-grid { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 20px; align-items: start; }
    @media (max-width: 1000px) { .bw-grid { grid-template-columns: minmax(0,1fr); } }

    .bw-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 22px 24px; }
    .bw-card h3 { font-size: 18px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
    .bw-card .lede { font-size: 13.5px; color: var(--text-secondary); line-height: 1.65; margin-bottom: 18px; }
    .bw-field { margin-bottom: 16px; }
    .bw-field label { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
    .bw-field .req { color: var(--bad-text); }
    .bw-optional { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .3px; margin-left: 4px; }
    .bw-field .bw-hint { font-size: 11.5px; color: var(--text-muted); margin-top: 5px; line-height: 1.4; }
    .bw-field input[type=text], .bw-field input[type=number], .bw-field input[type=datetime-local],
    .bw-field input[type=date], .bw-field input[type=time],
    .bw-field select, .bw-field textarea {
        width: 100%; background: var(--bg-page, transparent); border: 1px solid var(--border-color);
        border-radius: 10px; padding: 10px 12px; font-size: 13.5px; color: var(--text-primary); font-family: inherit;
    }
    .bw-field textarea { min-height: 130px; resize: vertical; line-height: 1.6; }
    .bw-field textarea[rows='3'] { min-height: 84px; }
    .bw-help { font-size: 12px; color: var(--text-muted); margin-top: 5px; line-height: 1.5; }
    /* The two ways of answering "where is it". */
    .bw-locpick { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 8px; margin-bottom: 10px; }
    .bw-locopt { display: flex; align-items: flex-start; gap: 9px; padding: 10px 12px; border: 1.5px solid var(--border-color); border-radius: 10px; cursor: pointer; background: var(--bg-card); }
    .bw-locopt:has(input:checked) { border-color: var(--brand, #f97316); background: color-mix(in srgb, var(--brand, #f97316) 6%, transparent); }
    .bw-locopt input { margin-top: 3px; }
    .bw-locopt b { display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); }
    .bw-locopt small { display: block; font-size: 11.5px; color: var(--text-muted); line-height: 1.35; margin-top: 1px; }
    .bw-locmine { border: 0; background: none; padding: 0; font: inherit; font-weight: 700; color: var(--brand, #f97316); cursor: pointer; text-decoration: underline; }
    /* The per-service budget breakdown. */
    .bw-split { border: 1.5px solid var(--border-color); border-radius: 12px; padding: 14px 16px; margin-top: 16px; background: var(--bg-card); }
    .bw-split h4 { margin: 0 0 2px; font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .bw-split-row { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--border-color); }
    .bw-split-row:last-of-type { border-bottom: 0; }
    .bw-split-row label { flex: 1; font-size: 13.5px; color: var(--text-primary); }
    .bw-split-row input { width: 130px; }
    .bw-split-total { margin-top: 10px; padding-top: 10px; border-top: 1.5px solid var(--border-color); font-size: 13px; color: var(--text-muted); }
    .bw-split-total b { color: var(--text-primary); }
    .bw-suggest { margin-left: 10px; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 8px; padding: 4px 11px; font: inherit; font-size: 12.5px; font-weight: 700; color: var(--brand, #f97316); cursor: pointer; }
    .bw-suggest:disabled { opacity: .6; cursor: default; }
    .bw-suggest-note { display: block; margin-top: 6px; font-size: 12px; color: var(--text-muted); }
    .bw-two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

    /* Step 7 — date, time and availability.
       The two controls on this step used to carry a class name, `.bw-input`,
       that this stylesheet never defined: styling comes from
       `.bw-field input`, a descendant selector, and neither control was in a
       .bw-field. They rendered as raw browser widgets — a tiny monospace
       textarea beside a native date box — on a page where everything else is
       styled. Both are inside .bw-field now and the class is gone. */
    .bw-three { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 14px; }
    @media (max-width: 700px) { .bw-three { grid-template-columns: 1fr; } }

    .bw-sec { border: 1px solid var(--border-color); border-radius: 13px; padding: 16px 18px; margin-bottom: 16px; }
    .bw-sec-h { margin-bottom: 14px; }
    .bw-sec-h b { display: block; font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .bw-sec-h span { display: block; font-size: 12px; color: var(--text-muted); margin-top: 3px; }
    .bw-sec-sub { font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin: 16px 0 9px; }

    .bw-avail { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 12px; }
    @media (max-width: 700px) { .bw-avail { grid-template-columns: 1fr; } }
    .bw-avail-card { border: 1px solid var(--border-color); border-radius: 12px; padding: 15px 16px; }
    .bw-avail-card .n { font-size: 30px; font-weight: 800; line-height: 1; color: var(--text-primary); }
    .bw-avail-card.ok .n { color: var(--ok-text, #059669); }
    .bw-avail-card .l { font-size: 13px; font-weight: 700; margin-top: 7px; color: var(--text-primary); }
    .bw-avail-card .s { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    .bw-days { display: flex; gap: 10px; flex-wrap: wrap; }
    .bw-day { min-width: 100px; border: 1px solid var(--border-color); border-radius: 11px; padding: 10px 12px;
              background: var(--bg-card); cursor: pointer; text-align: left; font-family: inherit; }
    .bw-day:hover { border-color: var(--brand-text, #f97316); }
    .bw-day.on { border-color: var(--brand-text, #f97316); background: rgba(249,115,22,.06); }
    .bw-day span { display: block; }
    .bw-day .dow { font-size: 11.5px; color: var(--text-muted); }
    .bw-day .dom { font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
    .bw-day .cnt { font-size: 19px; font-weight: 800; margin-top: 4px; color: var(--text-primary); }
    .bw-day .cap { font-size: 11px; color: var(--text-muted); }

    .bw-caveat { margin-top: 16px; border: 1px solid var(--border-color); border-left: 3px solid var(--accent-blue, #6366f1);
                 border-radius: 10px; padding: 12px 14px; font-size: 12.5px; color: var(--text-muted); line-height: 1.6; }

    .bw-note { border: 1px dashed var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 16px; }
    .bw-note.warn { border-style: solid; border-color: rgba(245,158,11,.4); background: rgba(245,158,11,.06); }
    .bw-note b { display: block; font-size: 14px; color: var(--text-primary); margin-bottom: 6px; }
    .bw-note p { font-size: 13px; color: var(--text-muted); margin: 0; line-height: 1.6; }
    .bw-note-acts { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 11px; }
    .bw-note-acts a { font-size: 12.5px; font-weight: 700; color: var(--brand-text); text-decoration: none; }

    .bw-callout { margin-top: 14px; border: 1px solid rgba(249,115,22,.28); background: rgba(249,115,22,.05);
                  border-radius: 10px; padding: 12px 14px; }
    .bw-callout b { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 3px; }
    .bw-callout p { font-size: 12.5px; color: var(--text-muted); margin: 0; line-height: 1.6; }

    /* The label is for screen readers and the accessibility baseline; the
       section heading above already says it on screen. */
    .sr-only-label { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

    @media (max-width: 620px) { .bw-two { grid-template-columns: 1fr; } }

    .bw-opts { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
    .bw-opt { position: relative; border: 1px solid var(--border-color); border-radius: 12px; padding: 13px 14px; cursor: pointer; }
    .bw-opt input { position: absolute; opacity: 0; }
    .bw-opt b { display: block; font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 3px; }
    .bw-opt span { font-size: 11.5px; color: var(--text-muted); line-height: 1.45; }
    .bw-opt:has(input:checked) { border-color: #f97316; box-shadow: 0 0 0 1px #f97316 inset; background: rgba(249,115,22,.05); }

    .bw-focus { background:#ecfdf5; border:1px solid #a7f3d0; border-radius:11px; padding:11px 14px; margin-bottom:14px; font-size:13px; line-height:1.55; }
    .bw-focus b { display:block; color:#065f46; font-weight:800; }
    .bw-focus span { color:#047857; font-size:12.5px; }
    .bw-focus a { color:#065f46; font-weight:800; text-decoration:underline; }
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

{{-- Row 226 — where the pre-filled answers came from, said out loud and kept
     visible for the whole wizard. A form that fills itself in without saying
     why is a form the client stops trusting the moment one figure looks off. --}}
@if(!empty($data['from_tool_name']))
    <div class="cl-card" style="background:rgba(37,99,235,.06);border:1px solid rgba(37,99,235,.28);padding:12px 16px;margin-bottom:16px;font-size:13px;color:var(--text-secondary);">
        <b style="color:var(--text-primary);">Started from {{ $data['from_tool_name'] }}.</b>
        What you entered there has been filled in below — every field is still yours to change, and
        nothing goes out to professionals until you publish.
    </div>
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

{{-- The title and subtitle live in the banner at the top of the page, like
     every other client screen. They used to be repeated here as well, at a
     third font size — which is what Sir Peter picked up on 29 Aug: some pages
     had a heading here, some had none, and no two were the same size. The
     banner is the page header; nothing restates it. --}}
<div class="bw-top">
    <div></div>
    <div class="bw-acts">
        @if($draftId)<span style="font-size:12px;color:var(--ok-text);font-weight:700;">✓ Draft saved</span>@endif
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

        {{-- Event type first, and required.
             It used to sit below the services and validate as nullable, so a
             client who started from Post Event rather than from an event-type
             page could skip it and the request was saved with no event type at
             all — two entry paths producing disconnected requests (Khadijah,
             2026-08-20: "it should be the first thing not below"). --}}
        <div class="bw-field">
            <label for="bwEventType">Event type <span class="req">*</span></label>
            <select name="event_type" id="bwEventType" required aria-label="Event type">
                <option value="">— Choose your event —</option>
                @foreach($eventTypes as $t)
                    <option value="{{ $t->name }}" @selected(($data['event_type'] ?? '') === $t->name)>{{ $t->name }}</option>
                @endforeach
                <option value="{{ $otherEventType }}" @selected(($data['event_type'] ?? '') === $otherEventType)>Other / not on this list</option>
            </select>
            <p class="bw-hint">The services below are ordered by what this kind of event usually needs.</p>
        </div>

        {{-- H — the client's own words, kept beside the list entry rather than
             replacing it. Free text as the event type would break the
             archetype relevance matrix, which is what orders the services on
             every request form. --}}
        <div class="bw-field" id="bwOwnTitle" @if(($data['event_type'] ?? '') !== $otherEventType) hidden @endif>
            <label for="bwEventTitle">What do you call your event? <span class="req">*</span></label>
            <input type="text" name="event_title" id="bwEventTitle" maxlength="120"
                   value="{{ $data['event_title'] ?? '' }}"
                   placeholder="e.g. Maryland's Horse Show Event">
            <p class="bw-hint">We keep your wording. Our team reviews it before it goes out to professionals.</p>
        </div>

        @if(! empty($focusNames) && ! $showingAll)
            {{-- They already said which area on the event-type page. This is
                 the level below it, not the same question again — so the page
                 says what they chose and shows only what is inside it. --}}
            <div class="bw-focus">
                <b>You chose {{ implode(' and ', $focusNames) }}.</b>
                <span>Pick the specific services you need. Or
                    <a href="{{ route('client.bsr.step', ['step' => 'service', 'all' => 1]) }}">see every service</a>.</span>
            </div>
        @elseif(! empty($focusNames))
            <div class="bw-focus">
                <b>Showing every service.</b>
                <span><a href="{{ route('client.bsr.step', 'service') }}">Back to {{ implode(' and ', $focusNames) }}</a>.</span>
            </div>
        @endif

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

        <div class="bw-field">
            <label for="bwOrgType">This request is for <span class="req">*</span></label>
            <select name="organization_type" id="bwOrgType" aria-label="Organization type">
                @foreach($orgTypes as $k => $l)
                    <option value="{{ $k }}" @selected(($data['organization_type'] ?? 'individual') === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </div>

        <div class="bw-field">
            {{-- No longer required. Sir Peter, 2026-08-31: nothing reads this
                 after it is saved — it reaches no professional and changes no
                 matching, deadline or fee — so it cannot be allowed to block a
                 client from posting. It stays while its purpose is decided. --}}
            <label>Request characteristic <span class="bw-optional">optional</span></label>
            <div class="bw-opts">
                @foreach($characteristics as $k => [$label, $desc])
                    <label class="bw-opt">
                        <input type="radio" name="characteristic" value="{{ $k }}"
                               @checked(($data['characteristic'] ?? 'standard') === $k)>
                        <b>{{ $label }}</b><span>{{ $desc }}</span>
                    </label>
                @endforeach
            </div>
            {{-- No "Emergency" option: emergency is its own request type (ER),
                 which broadcasts on a rush timeline, not a flavour of a BR. --}}
            <p class="bw-help">Need it urgently because something fell through? <a href="{{ route('client.esr.create') }}" style="color:var(--brand-text);font-weight:700;">Post an emergency request</a> instead.</p>
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
            {{-- Two ways to answer, because both are real.

                 The field was a single free-text box with "Baltimore, MD" in it,
                 so every request stored a city and nothing else — and
                 location_precision, which the database has carried all along,
                 stayed 'unresolved'. Distance from a professional cannot be
                 worked out from a city name, so it could never be worked out at
                 all.

                 A venue hunt genuinely has no address yet; everything else
                 usually does. Asking which one they have beats guessing. --}}
            @php
                $__loc  = $data['location'] ?? '';
                $__prof = auth()->user()?->profile;
                $__home = trim(implode(', ', array_filter([
                    $__prof?->address, $__prof?->city, $__prof?->state, $__prof?->zip_code,
                ])));
                $__kind = $data['location_kind'] ?? ($__loc === '' ? 'exact' : 'area');
            @endphp

            <div class="bw-field bw-locfield">
                <label>Where is the event?</label>

                <div class="bw-locpick">
                    <label class="bw-locopt">
                        <input type="radio" name="location_kind" value="exact" @checked($__kind === 'exact')>
                        <span><b>I know the address</b><small>Lets us judge how far professionals are from it</small></span>
                    </label>
                    <label class="bw-locopt">
                        <input type="radio" name="location_kind" value="area" @checked($__kind === 'area')>
                        <span><b>Only the area so far</b><small>Fine if you are still looking for a venue</small></span>
                    </label>
                </div>

                <input type="text" name="location" value="{{ $__loc }}"
                       placeholder="{{ $__kind === 'exact' ? '1234 Garden Way, Baltimore, MD 21201' : 'Baltimore, MD' }}"
                       data-bw-location>

                @if($__home !== '')
                    <p class="bw-help">
                        {{-- Peter: ask whether it differs from their own address,
                             rather than making them type it out again. --}}
                        Is it at your own address?
                        <button type="button" class="bw-locmine" data-bw-usemine="{{ $__home }}">Use {{ $__home }}</button>
                    </p>
                @endif
            </div>
            {{-- The state selector that stood here is gone.
                 Sir Peter's State Boundary Rule (2026-08-25) matches every
                 request by the client's own home state, whatever state the
                 event is in — so picking one changed nothing, and the hint
                 under it ("Professionals in this state are the ones who can
                 bid") was untrue for any state but their own. Stated instead
                 of asked, and it comes back when cross-state opens up. --}}
            @php $__homeState = config('geo.allowed_states')[auth()->user()?->profile?->state] ?? null; @endphp
            @if($__homeState)
                <div class="bw-field">
                    <label>Who can bid</label>
                    <p class="bw-hint" style="margin-top:0;">
                        Professionals in <b>{{ $__homeState }}</b> — GigResource works within one state for now,
                        so that is who sees this request even if the event itself is elsewhere.
                    </p>
                </div>
            @endif
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
        {{-- The per-service split.

             Bids have always been per service — a professional bids on ONE of
             them — while the budget was a single figure for the whole request.
             So on five services with a $10,000 budget, five different
             professionals were each shown $10,000 and priced against a number
             that was never meant for them. Khadijah, 2026-08-30.

             Only shown when there IS something to split. A single-service
             request has one budget and no breakdown to make. --}}
        @php
            $__svcIds = array_values(array_filter(array_map('intval', (array) ($data['services'] ?? []))));
            $__svcs   = count($__svcIds) > 1
                ? \App\Models\Category::whereIn('id', $__svcIds)->orderBy('name')->get(['id', 'name'])
                : collect();
            $__split  = (array) ($data['service_budgets'] ?? []);
        @endphp

        @if($__svcs->isNotEmpty())
            <div class="bw-split">
                <h4>What is each service worth to you?</h4>
                <p class="bw-help" style="margin-top:0;">
                    Professionals bid on one service each, so this is the figure the
                    right one sees. Leave any of them blank if you would rather not say.
                </p>

                @foreach($__svcs as $svc)
                    <div class="bw-split-row">
                        <label for="sb-{{ $svc->id }}">{{ $svc->name }}</label>
                        <input type="number" id="sb-{{ $svc->id }}" min="0" step="1"
                               name="service_budgets[{{ $svc->id }}]"
                               value="{{ $__split[$svc->id] ?? '' }}"
                               data-bw-split placeholder="—">
                    </div>
                @endforeach

                <div class="bw-split-total">
                    Breakdown adds up to <b data-bw-splittotal>—</b>
                    {{-- Offered, never applied on its own. It divides the
                         client's own total using the Masterlist's Essential /
                         Common / Occasional ranking for this occasion — it does
                         not estimate what anything costs, and every box stays
                         editable afterwards. --}}
                    <button type="button" class="bw-suggest" data-bw-suggest>Suggest a split</button>
                    <span class="bw-suggest-note" data-bw-suggestnote></span>
                </div>
            </div>
        @endif

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
            <label>Proposal deadline @unless($defaultWindowHours)<span class="req">*</span>@endunless</label>
            <input type="datetime-local" name="proposal_deadline" value="{{ $data['proposal_deadline'] ?? '' }}">
            <p class="bw-help">
                @if($defaultWindowHours)
                    Leave blank to use the standard {{ $defaultWindowHours }}-hour window.
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

    {{-- ── 6 · Files ─────────────────────────────────────────
         This step used to say "Attachments aren't available yet" — true then,
         because there was nowhere to put a file, but a step in an eight-step
         wizard that does nothing is still a step that does nothing.
         Files upload as they are picked, so nothing is lost if the client
         leaves the wizard and comes back. --}}
    @elseif($step === 'files')
        <h3>Files</h3>
        <p class="lede">Briefs, floor plans and reference documents. Optional — you can publish without them.</p>

        <x-request-files :files="$files" :draft-key="$filesKey" />

    {{-- ── 7 · Availability Match ───────────────────────────
         The mockup's four buckets (Available / Limited / Not Confirmed /
         Unavailable) are three more than our data supports. A clear
         GigResource calendar means no commitment ON GIGRESOURCE -- the
         professional may be booked elsewhere -- so this states the two
         countable facts and the caveat, rather than a confidence gauge. --}}
    @elseif($step === 'availability')
        <h3>Date, time and availability</h3>
        <p class="lede">Set exactly when your event runs, then see how many matching professionals have that day clear before you send it out.</p>

        {{-- ── Event date & time ───────────────────────────────
             The mockup puts these on this step, and it is right to: the
             client is looking at who is free on a date, which is the moment
             they would change it. Step 2 asked for a date; this is where the
             times get pinned down. --}}
        <div class="bw-sec">
            <div class="bw-sec-h">
                <b>Event date &amp; time</b>
                <span>Professionals see this on your request.</span>
            </div>

            <div class="bw-three">
                <div class="bw-field">
                    <label for="av_date">Event date <span class="req">*</span></label>
                    <input type="date" id="av_date" name="event_date" required
                           min="{{ now()->toDateString() }}"
                           value="{{ old('event_date', $availabilityDate?->toDateString() ?? '') }}">
                </div>
                <div class="bw-field">
                    <label for="av_start">Start time <span class="req">*</span></label>
                    <input type="time" id="av_start" name="event_start_time" required
                           value="{{ old('event_start_time', $availabilityDate?->format('H:i') ?? '') }}">
                </div>
                <div class="bw-field">
                    <label for="av_end">End time <span style="font-weight:500;color:var(--text-muted)">(optional)</span></label>
                    <input type="time" id="av_end" name="event_end_time"
                           value="{{ old('event_end_time', ! empty($data['ends_at']) ? \Illuminate\Support\Carbon::parse($data['ends_at'])->format('H:i') : '') }}">
                    <div class="bw-hint">Helps professionals quote staffing and overtime.</div>
                </div>
            </div>

            {{-- No time-zone picker.
                 The mockup has one, and for a marketplace that spanned zones it
                 would be right. R38 makes the client and the professional
                 the same state by design, so both read the same clock — and a
                 picker whose value nothing in the app converts by would be a
                 control that looks like it does something. --}}
        </div>

        {{-- ── Availability ────────────────────────────────────
             The mockup's four buckets (Available / Limited / Not Confirmed /
             Unavailable) and its EXCELLENT strength gauge are three buckets
             and a rating more than our data supports. A clear GigResource
             calendar means no commitment ON GIGRESOURCE — the professional
             may be booked elsewhere entirely. So this states the two
             countable facts and the caveat, and rates nothing. --}}
        @if(! $availability)
            <div class="bw-note">
                <b>Nothing to check yet</b>
                <p>
                    @if(empty($data['services'] ?? []))
                        Pick your services on step 1 and set a date on step 2, and this will show who is free.
                    @else
                        Set your event date above and this will show who is free.
                    @endif
                </p>
            </div>

        {{-- Nobody matches at all. That is a real answer and a different one
             from "everyone is busy", so it does not get three zeros and a
             shrug — it says which door is closed and which are open. --}}
        @elseif($availability['matched'] === 0)
            <div class="bw-note warn">
                @php
                    // The services THEY picked, not the event type's focus —
                    // naming the wrong thing here is worse than naming nothing.
                    $picked = $categories->whereIn('id', (array) ($data['services'] ?? []))->pluck('name');
                @endphp
                <b>No professional on GigResource offers {{ $picked->count() ? $picked->take(2)->implode(' or ') : 'these services' }} in your state yet</b>
                <p>
                    You can still publish — the request stays open and any professional who joins and offers
                    this will see it. If you would rather not wait, go back to step 1 and add another service,
                    or post a Direct Request to someone you already know.
                </p>
                <div class="bw-note-acts">
                    <a href="{{ route('client.bsr.step', 'service') }}">Change services</a>
                    <a href="{{ route('client.direct-offers.create') }}">Send a Direct Request instead</a>
                </div>
            </div>
        @else
            <div class="bw-sec">
                <div class="bw-sec-h">
                    <b>Availability on {{ $availabilityDate->format('D, M j, Y') }}</b>
                    <span>Counted now — it can change until a professional accepts.</span>
                </div>

                <div class="bw-avail">
                    <div class="bw-avail-card ok">
                        <div class="n">{{ $availability['nothing_booked'] }}</div>
                        <div class="l">have nothing booked</div>
                        <div class="s">on GigResource that day</div>
                    </div>
                    <div class="bw-avail-card">
                        <div class="n">{{ $availability['already_booked'] }}</div>
                        <div class="l">already booked</div>
                        <div class="s">that day on GigResource</div>
                    </div>
                    <div class="bw-avail-card">
                        <div class="n">{{ $availability['matched'] }}</div>
                        <div class="l">match your request</div>
                        <div class="s">your services, in your state</div>
                    </div>
                </div>

                @if(count($availabilityDays) > 1)
                    <div class="bw-sec-sub">Around your date</div>
                    <div class="bw-days">
                        @foreach($availabilityDays as $d)
                            {{-- Clicking a day moves the date. The old screen
                                 showed the same four days and made the client
                                 retype the date in a separate field below to
                                 act on them. --}}
                            <button type="button" class="bw-day {{ $d['chosen'] ? 'on' : '' }}"
                                    data-date="{{ $d['date']->toDateString() }}">
                                <span class="dow">{{ $d['date']->format('D') }}</span>
                                <span class="dom">{{ $d['date']->format('M j') }}</span>
                                <span class="cnt">{{ $d['nothing_booked'] }}</span>
                                <span class="cap">{{ $d['chosen'] ? 'your date' : 'free' }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- The caveat is not small print: it is the reason these
                     numbers are counts and not a promise. --}}
                <div class="bw-caveat">
                    A clear calendar here means nothing is booked <b>on GigResource</b> — a professional may still be
                    committed elsewhere. Nothing is held until one of them accepts and the booking is confirmed.
                </div>
            </div>
        @endif

        {{-- ── Note to the professional ────────────────────── --}}
        <div class="bw-sec">
            <div class="bw-sec-h">
                <b>Anything they should know about timing?</b>
                <span>Optional. Professionals see this on your request.</span>
            </div>
            <div class="bw-field" style="margin-bottom:0;">
                <label for="av_note" class="sr-only-label">Timing notes for the professional</label>
                <textarea id="av_note" name="availability_note" rows="3" maxlength="500"
                          data-counter="avCount"
                          placeholder="e.g. setup can start from 3pm, or we can move the date by a week">{{ old('availability_note', $data['availability_note'] ?? '') }}</textarea>
                <div class="bw-hint" style="text-align:right;"><span id="avCount">0</span> / 500</div>
            </div>

            <div class="bw-callout">
                <b>Ask them to confirm the date</b>
                <p>Professionals reply with a proposal. Availability above is a count, not a booking — ask them to
                   confirm the date and time when they respond.</p>
            </div>
        </div>

@push('scripts')
<script>
// The breakdown adds up as it is typed. The client is comparing it against
// their own range, so the sum has to be in front of them while they type —
// not discovered on the next screen.
// "Suggest a split" — fills the boxes from the client's own total, weighted by
// how central each service is to this kind of event. They then change whatever
// they disagree with; nothing is saved by pressing it.
(function () {
    const btn  = document.querySelector('[data-bw-suggest]');
    const note = document.querySelector('[data-bw-suggestnote]');
    if (!btn) return;

    btn.addEventListener('click', async function () {
        btn.disabled = true;
        const was = btn.textContent;
        btn.textContent = 'Working…';
        if (note) note.textContent = '';

        const totalField = document.querySelector('input[name="budget_max"]')
            || document.querySelector('input[name="budget_min"]');

        try {
            const res = await fetch('{{ route('client.bsr.suggest-split') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ total: totalField ? totalField.value : null }),
            });
            const data = await res.json();

            if (!data.ok) {
                if (note) note.textContent = data.message || 'Could not suggest a split.';
                return;
            }

            Object.entries(data.split).forEach(function ([id, amount]) {
                const field = document.querySelector('input[name="service_budgets[' + id + ']"]');
                if (field) field.value = amount;
            });

            document.querySelectorAll('[data-bw-split]').forEach(function (f) {
                f.dispatchEvent(new Event('input'));
            });

            if (note) note.textContent = 'A starting point — change anything you disagree with.';
        } catch (err) {
            if (note) note.textContent = 'Could not suggest a split just now.';
        } finally {
            btn.disabled = false;
            btn.textContent = was;
        }
    });
})();

(function () {
    const fields = document.querySelectorAll('[data-bw-split]');
    const out = document.querySelector('[data-bw-splittotal]');
    if (!fields.length || !out) return;

    function retotal() {
        let sum = 0;
        let any = false;
        fields.forEach(function (f) {
            const n = parseFloat(f.value);
            if (!isNaN(n) && n >= 0) { sum += n; any = true; }
        });
        out.textContent = any ? '$' + sum.toLocaleString('en-US') : '—';
    }

    fields.forEach(function (f) { f.addEventListener('input', retotal); });
    retotal();
})();

// The placeholder should show the shape of answer being asked for, and "use my
// address" should fill it rather than making them type it again.
(function () {
    const field = document.querySelector('[data-bw-location]');
    if (!field) return;

    const hints = {
        exact: '1234 Garden Way, Baltimore, MD 21201',
        area:  'Baltimore, MD',
    };

    document.querySelectorAll('input[name="location_kind"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            field.placeholder = hints[radio.value] || hints.area;
        });
    });

    const mine = document.querySelector('[data-bw-usemine]');
    if (mine) {
        mine.addEventListener('click', function () {
            field.value = mine.dataset.bwUsemine;
            const exact = document.querySelector('input[name="location_kind"][value="exact"]');
            if (exact) { exact.checked = true; field.placeholder = hints.exact; }
            field.focus();
        });
    }
})();
</script>
<script>
(function () {
    // Clicking a nearby day sets the date field rather than making the client
    // read the number here and retype the date somewhere else.
    var date = document.getElementById('av_date');
    document.querySelectorAll('.bw-day').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!date) return;
            date.value = b.dataset.date;
            document.querySelectorAll('.bw-day').forEach(function (x) { x.classList.remove('on'); });
            b.classList.add('on');
            date.form && date.form.requestSubmit
                ? null   // not submitted for them: they may still want to edit the time
                : null;
        });
    });

    var ta = document.getElementById('av_note'), out = document.getElementById('avCount');
    if (ta && out) {
        var tick = function () { out.textContent = ta.value.length; };
        ta.addEventListener('input', tick);
        tick();
    }
})();
</script>
@endpush

    {{-- ── 8 · Review ──────────────────────────────────────── --}}
    @elseif($step === 'review')
        @php
            $svcNames = $categories->whereIn('id', (array) ($data['services'] ?? []))->pluck('name');
            $isMulti  = count((array) ($data['services'] ?? [])) >= 2;
        @endphp
        <h3>Review &amp; publish</h3>
        <p class="lede">This is what professionals will see. You can edit any of it after publishing, right up until you choose someone.</p>

        <div class="bw-rev"><span>Request type</span><b>BR — open to bidding</b></div>
        <div class="bw-rev"><span>Scope</span><b>{{ $isMulti ? 'MSR — multi-service' : 'SSR — single service' }}</b></div>
        <div class="bw-rev"><span>Services</span><b>{{ $svcNames->implode(', ') ?: '—' }}</b></div>
        <div class="bw-rev"><span>Name</span><b>{{ $data['title'] ?? '—' }}</b></div>
        <div class="bw-rev"><span>Event date</span><b>{{ ! empty($data['starts_at']) ? \Illuminate\Support\Carbon::parse($data['starts_at'])->format('M j, Y · g:i A') : 'Flexible' }}@if(! empty($data['ends_at'])) – {{ \Illuminate\Support\Carbon::parse($data['ends_at'])->format('g:i A') }}@endif</b></div>
        <div class="bw-rev"><span>Location</span><b>{{ $data['location'] ?? '—' }}{{ ! empty($data['venue']) ? ' · ' . $data['venue'] : '' }}</b></div>
        <div class="bw-rev"><span>Guests</span><b>{{ ! empty($data['guest_count']) ? number_format($data['guest_count']) : '—' }}</b></div>
        <div class="bw-rev"><span>Budget</span><b>
            @if(! empty($data['budget_min']) || ! empty($data['budget_max']))
                ${{ number_format((float) ($data['budget_min'] ?? 0)) }} – ${{ number_format((float) ($data['budget_max'] ?? 0)) }}
            @else Not stated @endif
        </b></div>
        <div class="bw-rev"><span>Proposal deadline</span><b>{{ ! empty($data['proposal_deadline']) ? \Illuminate\Support\Carbon::parse($data['proposal_deadline'])->format('M j, Y · g:i A') : ($defaultWindowHours ? 'Standard ' . $defaultWindowHours . '-hour window' : 'Not set') }}</b></div>
        <div class="bw-rev"><span>Proposals</span><b>{{ ($data['sealed_proposals'] ?? true) ? 'Sealed' : 'Open' }} · questions {{ ($data['questions_enabled'] ?? true) ? 'allowed' : 'off' }}</b></div>
        {{-- Named on the review too. A file the client attached three steps
             back is part of what they are about to publish, and a summary that
             omits it is a summary they cannot check. --}}
        <div class="bw-rev"><span>Files</span><b>
            @if($files->count())
                {{ $files->count() }} attached · {{ $files->pluck('file_name')->implode(', ') }}
            @else
                None
            @endif
        </b></div>

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

<script>
(function () {
    var type = document.getElementById('bwEventType');
    var own  = document.getElementById('bwOwnTitle');
    var box  = document.getElementById('bwEventTitle');

    if (!type || !own) return;

    var OTHER = @json($otherEventType ?? 'Other Event');

    function sync() {
        var isOther = type.value === OTHER;
        own.hidden = !isOther;
        // Required only while it is on screen, or the form refuses to submit
        // over a field nobody can see.
        if (box) box.required = isOther;
    }

    type.addEventListener('change', sync);
    sync();
})();
</script>

@endsection
