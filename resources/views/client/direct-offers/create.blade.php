@extends('layouts.client')

@section('title', 'Direct Request')
@section('page-title', 'Send a Direct Request')
{{-- Sir Peter, 2026-09-10: the subtitle has to cover both scopes. A Direct
     Request can ask one professional for a single service (SSR) or several
     (MSR); "services" read as the second only. Phrased like the Bidding
     Request card, "one service or several". --}}
@section('page-subtitle', 'Request Single or Multiple services from a single professional.')

{{-- Client → Professional Direct Request builder. The request type (SSR / MSR)
     reshapes the form: SSR = one service; MSR = multiple services, each handled
     as its own separate agreement (Team/Co-Op combined-force removed platform-
     wide). Type switching is pure-JS via data attributes. Representative submit
     (no backend yet). --}}

@php
    // ER is its own standalone "Post a Rush Request" workflow — NOT a Direct
    // Offer type (per Peter). Direct Request supports single / multi service only.
    $types = [
        ['SSR', 'Single Service Request', 'One specific service from this pro, simplest request.'],
        ['MSR', 'Multi-Service Request', 'Several services. Each handled as its own separate agreement.'],
    ];
@endphp

@push('styles')
<style>
    .do { --do: #f97316; --do-strong: #ea580c; --ai: #16a34a; max-width: 100%; margin: 0 auto; }
    /* Fill large screens: form + contextual rail side-by-side, stacks on narrow. */
    .do-layout { display: grid; grid-template-columns: minmax(0,1fr) 340px; gap: 20px; align-items: start; }
    .do-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 88px; }
    .do-rcard { background: var(--bg-card,#fff); border: 1px solid var(--border-color,#e5e7eb); border-radius: 16px; padding: 18px; }
    .do-rcard h4 { font-size: 13px; font-weight: 800; color: var(--text-primary,#111827); margin-bottom: 13px; display:flex; align-items:center; gap:8px; }
    .do-rcard h4 svg { width:16px; height:16px; color: var(--do); }
    .do-step { display: flex; gap: 11px; margin-bottom: 12px; }
    .do-step:last-child { margin-bottom: 0; }
    .do-step-n { flex-shrink:0; width:24px; height:24px; border-radius:50%; background: rgba(249,115,22,.12); color: var(--do-strong); font-size:12px; font-weight:800; display:flex; align-items:center; justify-content:center; }
    .do-step-b { font-size:12.5px; color:var(--text-secondary,#4b5563); line-height:1.45; }
    .do-step-b b { color:var(--text-primary,#111827); display:block; font-size:12.5px; margin-bottom:1px; }
    .do-rlist { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px; }
    .do-rlist li { display:flex; gap:8px; font-size:12.5px; color:var(--text-secondary,#4b5563); line-height:1.4; }
    .do-rlist svg { width:14px; height:14px; color: var(--do); flex-shrink:0; margin-top:2px; }
    .do-rlist b { color: var(--text-primary,#111827); }

    /* request type selector */
    .do-types { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 22px; }
    .do-type { border: 2px solid var(--border-color); border-radius: 14px; padding: 15px; cursor: pointer; transition: all .15s; background: var(--bg-card); }
    .do-type:hover { border-color: var(--do); }
    .do-type.sel { border-color: var(--do); background: rgba(249,115,22,.07); }
    .do-type-code { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--do-strong); background: rgba(249,115,22,.12); padding: 3px 10px; border-radius: 999px; }
    .do-type h5 { font-size: 14px; font-weight: 800; color: var(--text-primary); margin: 9px 0 5px; }
    .do-type p { font-size: 12px; color: var(--text-muted); line-height: 1.45; }

    .do-sec { border: 1px solid var(--border-color); border-radius: 14px; background: var(--bg-card); margin-bottom: 14px; overflow: hidden; }
    .do-sec-hd { display: flex; align-items: center; gap: 9px; padding: 13px 16px; border-bottom: 1px solid var(--border-color); }
    /* The section headings are the shared numbered ones now, so this card
       supplies the bar around them rather than its own heading markup. */
    .do-sec > .fsec-h { margin: 0; padding: 13px 16px; border-bottom: 1px solid var(--border-color); }
    .do-sec.req > .fsec-h { background: rgba(249,115,22,.06); }
    .do-sec.ai  > .fsec-h { background: rgba(22,163,74,.07); }
    .do-sec.ai  > .fsec-h .fsec-n, .do-sec.ai > .fsec-h .fsec-tag { background: var(--ai); }
    .do-sec.req .do-sec-hd { background: rgba(249,115,22,.06); }
    .do-sec.ai .do-sec-hd { background: rgba(22,163,74,.07); }
    .do-sec-hd h4 { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .do-tag { margin-left: auto; font-size: 9.5px; font-weight: 800; letter-spacing: .3px; padding: 3px 9px; border-radius: 999px; color: #fff; }
    .do-sec.req .do-tag { background: var(--do); }
    .do-sec.ai .do-tag { background: var(--ai); }
    .do-sec-bd { padding: 15px 16px; }
    /* Empty state for the professional picker. It replaces a select with no
       options in it — see the note at the control. */
    .do-empty { border: 1px solid rgba(245,158,11,.35); background: rgba(245,158,11,.06);
                border-radius: 11px; padding: 14px 16px; }
    .do-empty b { display: block; font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 5px; }
    .do-empty p { font-size: 12.5px; color: var(--text-muted); margin: 0; line-height: 1.6; }
    .do-empty-acts { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 10px; }
    .do-empty-acts a { font-size: 12.5px; font-weight: 700; color: var(--brand-text); text-decoration: none; }

    .do-field { margin-bottom: 13px; }
    .do-field:last-child { margin-bottom: 0; }
    .do-field label { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .do-input { width: 100%; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 10px 12px; font-size: 13.5px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; }
    .do-input:focus { outline: none; border-color: var(--do); }
    textarea.do-input { resize: vertical; min-height: 70px; }
    .do-row { display: flex; gap: 12px; flex-wrap: wrap; }
    .do-row > div { flex: 1; min-width: 150px; }

    .do-chips { display: flex; flex-wrap: wrap; gap: 9px; }
    .do-chip { display: inline-flex; align-items: center; gap: 7px; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 9px 13px; font-size: 13px; font-weight: 700; color: var(--text-secondary); background: var(--bg-card); cursor: pointer; user-select: none; }
    .do-chip.sel { border-color: var(--do); background: rgba(249,115,22,.1); color: var(--do-strong); }
    .do-chip .tick { display: none; } .do-chip.sel .tick { display: inline; }

    /* pro card */
    .do-pro { display: flex; align-items: center; gap: 12px; }
    .do-pro-av { width: 46px; height: 46px; border-radius: 12px; object-fit: cover; }
    .do-pro-main h5 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .do-pro-main p { font-size: 12px; color: var(--text-muted); }

    .do-ai-row { display: flex; align-items: flex-start; gap: 9px; font-size: 12.5px; color: var(--text-secondary); padding: 5px 0; }
    .do-ai-row .ck { color: var(--ai); font-weight: 800; flex-shrink: 0; }

    .do-hint { font-size: 11.5px; color: var(--do-strong); background: rgba(249,115,22,.08); border-radius: 8px; padding: 7px 11px; margin-top: 10px; }

    .do-foot { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 18px; flex-wrap: wrap; }
    .do-foot p { font-size: 12.5px; color: var(--text-muted); }
    .do-btn { border: none; border-radius: 12px; padding: 13px 26px; font-size: 14.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--do), var(--do-strong)); cursor: pointer; display: inline-flex; align-items: center; gap: 9px; }
    .do-btn svg { width: 17px; height: 17px; }

    /* hide sections not valid for the current type */
    .do[data-type="SSR"] [data-types]:not([data-types~="SSR"]),
    .do[data-type="MSR"] [data-types]:not([data-types~="MSR"]),
    .do[data-type="SSR"] .do-svc-multi { display: none; }
    .do:not([data-type="SSR"]) .do-svc-single { display: none; }
    .do[data-type="SSR"] .do-hide-ssr { display: none; }

    @media (max-width: 1024px) { .do-layout { grid-template-columns: 1fr; } .do-rail { position: static; flex-direction: row; flex-wrap: wrap; } .do-rcard { flex:1; min-width: 240px; } }
    @media (max-width: 760px) { .do-types { grid-template-columns: 1fr; } }
    @media (max-width: 640px) { .do-rail { flex-direction: column; } }

    /* OA-144 — the service already chosen, shown rather than asked again. */
    .do-chosen { display:flex; align-items:baseline; gap:8px; padding:10px 12px;
                 border:1px solid var(--border-color,#e5e7eb); border-radius:9px;
                 background:var(--bg-soft,rgba(0,0,0,.02)); }
    .do-chosen b { font-size:13.5px; }
    .do-chosen span { font-size:11.5px; color:var(--text-muted,#6b7280); }
    .do-ai-note { margin: 10px 0 0; font-size: 12px; line-height: 1.55; color: var(--text-muted, #6b7280); }
    /* The chosen service's Level 4 terms, on step 1 beside the service. */
    .do-l4 { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 6px 14px; }
    .do-l4 label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--text-secondary); margin: 0; }
</style>
@endpush

@section('content')
<div class="do" data-type="{{ $type }}" id="doRoot">

    {{-- Request type --}}
    <div class="do-types">
        @foreach($types as [$code, $name, $desc])
            <div class="do-type {{ $type === $code ? 'sel' : '' }}" data-type="{{ $code }}">
                <span class="do-type-code">{{ $code }}</span>
                <h5>{{ $name }}</h5>
                <p>{{ $desc }}</p>
            </div>
        @endforeach
    </div>

    <div class="do-layout">
    <form method="POST" action="{{ route('client.direct-offers.store') }}">
        @csrf
        {{-- Validation errors are rendered once, by layouts.client, for every
             page. This screen used to print its own copy as well, so a failed
             submit showed the same sentence twice. --}}
        <input type="hidden" name="request_type" id="doType" value="{{ $type }}">

        {{-- Checklist row 193 — service first, then the professional.

             This section used to open with every professional in the state,
             so a photography brief could go to a florist and only come back
             when they declined it. Choosing the service narrows the list to
             people who actually do that work. Arriving from a profile page
             skips this: the professional is already chosen, and the services
             offered are only theirs. --}}
        {{-- Shown unless the client arrived from a professional's own profile,
             where the person is already chosen and the services are theirs.
             It used to disappear the moment ANY professional was set — and
             one was set automatically — so picking a service was a one-way
             door with no way back to change it. --}}
        @unless($selectedPro)
            <div class="do-sec req">
                <x-form-section :n="1" title="What do you need?" tag="START HERE" />
                <div class="do-sec-bd">
                    <div class="do-field">
                        <label><span data-types="SSR">Service</span><span data-types="MSR">Main service</span></label>
                        {{-- Choosing a service reloads the page to narrow the
                             professionals to the people who offer it. That
                             reload carried the service and nothing else, so it
                             came back on the default type: pick Multi-Service,
                             choose a service, and the page snapped back to
                             Single Service every time. The whole address is
                             rebuilt here, so what has already been chosen
                             survives the reload. --}}
                        <select class="do-input" data-do-service
                                data-url="{{ route('client.direct-offers.create') }}" aria-label="Choose a service…">
                            <option value="">Choose a service…</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(($serviceId ?? 0) === $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <p style="font-size:11.5px;color:var(--text-muted);margin-top:5px;">
                            <span data-types="SSR">We only show professionals who offer this.</span>
                            {{-- On a Multi-Service Request the services
                                 themselves are picked further down, so this
                                 one has a job of its own and says what it is. --}}
                            <span data-types="MSR">We only show professionals who offer this. It counts as one of the services you are asking for; add the rest under <b>Service Needs</b> below.</span>
                        </p>
                    </div>

                    {{-- Sir Peter, 22 Sep: the service's own Level 4 terms
                         belong here, with the service, not on a later step. --}}
                    @php
                        $__l4 = ($serviceId ?? 0)
                            ? \App\Models\Category::where('kind', \App\Models\Category::SERVICE_SPECIALTY)
                                ->where('parent_id', $serviceId)->where('is_active', true)
                                ->orderBy('sort_order')->get(['id', 'name'])
                            : collect();
                        $__l4Picked = array_map('intval', (array) (old('service_details')[$serviceId ?? 0] ?? []));
                    @endphp
                    @if($__l4->isNotEmpty())
                        <div class="do-field">
                            <label>What exactly do you need? <span style="font-weight:500;color:var(--text-muted);">(optional)</span></label>
                            <div class="do-l4">
                                @foreach($__l4 as $__term)
                                    <label>
                                        <input type="checkbox" name="service_details[{{ $serviceId }}][]" value="{{ $__term->id }}"
                                               @checked(in_array((int) $__term->id, $__l4Picked, true))>
                                        <span>{{ $__term->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p style="font-size:11.5px;color:var(--text-muted);margin-top:5px;">Pick all that apply, or leave them blank.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endunless

        {{-- Choose Professional --}}
        <div class="do-sec req">
            <x-form-section :n="2" title="Choose Professional" tag="YOUR INPUT" required />
            <div class="do-sec-bd">
                @if($selectedPro)
                    <div class="do-pro" style="margin-bottom:12px;">
                        <img class="do-pro-av" src="{{ $selectedPro->avatar_url }}" alt="">
                        <div class="do-pro-main">
                            <h5>{{ $selectedPro->name }}</h5>
                            <p>{{ $selectedPro->profile->headline ?? 'Event Professional' }}@if($selectedPro->reviews_avg) · ★ {{ number_format($selectedPro->reviews_avg,1) }}@endif</p>
                        </div>
                    </div>
                @endif

                {{-- The dropdown appears only when there is somebody in it.
                     It used to render regardless: with no service chosen, or
                     with a service nobody in the state offers, the page showed
                     a focusable "Send to" control containing nothing at all,
                     directly under a sentence explaining that there was
                     nobody. An empty select is not an empty state. --}}
                @php
                    // Nothing to choose from until the client says what they
                    // need. Without this the list fell back to every
                    // professional in the state, under a heading that promises
                    // "We only show professionals who offer this."
                    $mustPickService = ! $selectedPro && ($serviceId ?? 0) === 0;
                @endphp

                @if($mustPickService || $pros->isEmpty())
                    <div class="do-empty">
                        @if($mustPickService)
                            <b>Choose a service first</b>
                            <p>Pick what you need above and this will list the professionals who offer it in your state.</p>
                        @else
                            <b>No professional in your state offers this yet</b>
                            <p>
                                GigResource matches within your own state, and nobody here has listed this service.
                                You can pick a different service, or post it to the board. It stays open, and any
                                professional who can do it may reply.
                            </p>
                            <div class="do-empty-acts">
                                <a href="{{ route('client.direct-offers.create') }}">Choose another service</a>
                                <a href="{{ route('client.bsr.step', 'service') }}">Post it to the board instead</a>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="do-field">
                        <label for="doPro">Send to <span style="color:#dc2626;">*</span></label>
                        <select name="professional_id" id="doPro" class="do-input" required>
                            {{-- No pre-selection. The form used to address
                                 itself to whoever came back first, so a client
                                 who never chose anyone could still send. --}}
                            <option value="">Choose who this goes to…</option>
                            @foreach($pros as $p)
                                <option value="{{ $p->id }}" @selected(old('professional_id') == $p->id || ($selectedPro && $selectedPro->id === $p->id))>{{ $p->name }}: {{ $p->profile->headline ?? 'Professional' }}</option>
                            @endforeach
                        </select>
                        @error('professional_id')
                            <p style="font-size:12px;color:#dc2626;font-weight:600;margin-top:5px;">{{ $message }}</p>
                        @enderror
                        <p style="font-size:11.5px;color:var(--text-muted);margin-top:5px;">
                            {{ $pros->count() }} {{ \Illuminate\Support\Str::plural('professional', $pros->count()) }} in your state offer{{ $pros->count() === 1 ? 's' : '' }} this.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Event Details --}}
        <div class="do-sec req">
            <x-form-section :n="3" title="Event Details" tag="YOUR INPUT" required />
            <div class="do-sec-bd">
                <div class="do-row">
                    <div class="do-field">
                        <label for="doEventType">Event Type</label>
                        <select class="do-input" name="event_type" id="doEventType">
                            <option value="">Choose your event…</option>
                            @foreach($eventTypes as $__t)
                                <option value="{{ $__t->name }}" @selected(old('event_type') === $__t->name)>{{ $__t->name }}</option>
                            @endforeach
                            <option value="Other Event" @selected(old('event_type') === 'Other Event')>Other / not on this list</option>
                        </select>
                    </div>
                    <div class="do-field"><label>Event Name <span style="color:#dc2626;">*</span></label><input class="do-input" name="event_name" value="{{ old('event_name') }}" placeholder="e.g. Luxury Wedding Reception" required>@error('event_name')<p style="color:#dc2626;font-size:12px;margin-top:5px;">{{ $message }}</p>@enderror</div>
                </div>
                <div class="do-field">
                    <label for="doOrgType">This request is for <span style="color:#dc2626;">*</span></label>
                    <select class="do-input" name="organization_type" id="doOrgType" required>
                        @foreach($orgTypes as $key => $label)
                            <option value="{{ $key }}" @selected(old('organization_type', 'individual') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="do-row">
                    <div class="do-field">
                        <label>Event Date <span style="color:#dc2626;">*</span></label>
                        <input type="date" class="do-input" name="event_date" value="{{ old('event_date') }}" required>
                        @error('event_date')<p style="color:#dc2626;font-size:12px;margin-top:5px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="do-field"><label>Guest Count</label><input type="number" class="do-input" name="guests" placeholder="150"></div>
                </div>
                <div class="do-field"><label>Venue / Location</label><input class="do-input" name="venue" {{-- Placeholder text is still copy: this one named Chicago on a
                     seven-jurisdiction marketplace (R9). --}}
                    placeholder="The Grand Ballroom, Baltimore, MD"></div>

                {{-- The form had nowhere to say what was actually wanted. The
                     professional was sent a name, a date and a service list,
                     and had to guess the rest -- while the bidding form has
                     always required this. --}}
                <div class="do-field">
                    <label>What should the professional know? <span style="color:#dc2626;">*</span></label>
                    <textarea class="do-input" name="description" maxlength="4000" required
                              placeholder="What the event is, what you need delivered, anything that would change the price…">{{ old('description') }}</textarea>
                    @error('description')<p style="color:#dc2626;font-size:12px;margin-top:5px;">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        @php
            $__chosenTop = ($serviceId ?? 0) ? $categories->firstWhere('id', $serviceId) : null;
        @endphp
        {{-- Service Needs (adapts by type).
             On a single-service request whose service was chosen in step 1,
             this section has nothing left to ask, so it is not drawn at all
             (Sir Peter, 22 Sep). A multi-service request still picks its
             services here. --}}
        <div class="do-sec req {{ ($__chosenTop ?? null) ? 'do-hide-ssr' : '' }}">
            <x-form-section :n="4" title="Service Needs" tag="YOUR INPUT" required />
            <div class="do-sec-bd">
                {{-- SSR: single service.

                     OA-144: this asked the same question twice. The client
                     chooses a service at the top of the page to find
                     professionals who offer it, and was then asked again down
                     here which service they wanted — with no indication the
                     two were related, and free to disagree.

                     When the choice was already made above, it is shown as
                     settled and carried in a hidden field. The dropdown is
                     still there for the other way in: arriving from a
                     professional's own profile, where no service was picked. --}}
                @php
                    $__chosen = $__chosenTop;
                    // Nothing chosen and nobody chosen: the question is already
                    // being asked at the top of the page, where it also does
                    // something — it finds the professionals. Asking again here
                    // is the duplicate, and it cannot be acted on yet anyway.
                    $__askAtTop = ! $selectedPro && ! $__chosen;
                @endphp

                {{-- Sir Peter, Sep 11: with the dropdown hidden the section was
                     empty under "YOUR INPUT", so nothing said what to do. --}}
                @if($__askAtTop)
                    <div class="do-svc-single">
                        <div class="do-field">
                            <label>Service requested</label>
                            <p class="do-hint" style="margin:0;">
                                Choose the service you need at the top of this page. It will appear here,
                                along with the professionals who offer it.
                                <a href="#" onclick="document.querySelector('[aria-label=&quot;Choose a service…&quot;]')?.focus(); window.scrollTo({top:0,behavior:'smooth'}); return false;"
                                   style="color:#ea580c;font-weight:700;">Choose a service</a>
                            </p>
                        </div>
                    </div>
                @endif
                <div class="do-svc-single" @if($__askAtTop || $__chosen) hidden @endif>
                    <div class="do-field">
                        <label>Service requested</label>

                        @if($__chosen)
                            {{-- Sir Peter, 22 Sep: asked in step 1, so it is not
                                 shown again here. It still travels with the form. --}}
                            <input type="hidden" name="service_single" value="{{ $__chosen->name }}">
                        @elseif(! $__askAtTop)
                            <select class="do-input" name="service_single" aria-label="Service single">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="do-hint">SSR: a single, specific service from this professional.</div>
                </div>
                {{-- MSR: the other services.

                     OA-144 again, on the multi-service side. The service
                     chosen at the top of the page was offered here a second
                     time, as a checkbox among all the others, with nothing to
                     say it was the same one. So it is shown here as settled —
                     it travels with the form in a hidden field — and the
                     picker asks only for what has not been asked for yet. --}}
                <div class="do-svc-multi">
                    <div class="do-field">
                        @if($__chosenTop)
                            <label>Other services you also need</label>
                            <p class="do-hint" style="margin:0 0 10px;">
                                <b>{{ $__chosenTop->name }}</b> is already included, from step 1.
                            </p>
                            {{-- Disabled on an SSR, where this whole block is
                                 hidden: a hidden field still submits, and a
                                 single-service request must not carry a list. --}}
                            <input type="hidden" name="services[]" value="{{ $__chosenTop->id }}"
                                   data-msr-only @disabled($type === 'SSR')>
                            <x-service-picker :categories="$categories->reject(fn ($c) => $c->name === $__chosenTop->name)"
                                              name="services" :selected="old('services', [])"
                                              :details="true" :detail-selected="old('service_details', [])"
                                              :missing="true" :missing-value="old('service_missing')" />
                        @else
                            <label>Services requested (pick all that apply)</label>
                            <x-service-picker :categories="$categories" name="services" :selected="old('services', [])"
                                              :details="true" :detail-selected="old('service_details', [])"
                                              :missing="true" :missing-value="old('service_missing')" />
                        @endif

                        {{-- Appears the moment a catering or bar service is ticked. --}}
                        @include('partials._food_delivery', [
                            'mode'  => old('delivery_mode'),
                            'live'  => true,
                            'shown' => \App\Domain\Requests\FoodDelivery::appliesTo(
                                array_map('intval', (array) old('services', []))
                            ),
                        ])
                    </div>
                </div>
            </div>
        </div>

        {{-- Budget & Payment --}}
        <div class="do-sec req">
            <x-form-section :n="5" title="Budget" tag="YOUR INPUT" />
            <div class="do-sec-bd">
                <div class="do-row">
                    <div class="do-field"><label>Budget <span style="color:#dc2626;">*</span></label><input type="number" class="do-input" name="budget_min" min="1" required value="{{ old('budget_min') }}" placeholder="7000"><p style="font-size:11.5px;color:var(--text-muted);margin:4px 0 0;">A rough estimate is fine.</p>@error('budget_min')<p style="color:#dc2626;font-size:12px;margin:4px 0 0;">{{ $message }}</p>@enderror</div>
                    <div class="do-field"><label>Budget up to <span style="font-weight:500;color:var(--text-muted);">(optional)</span></label><input type="number" class="do-input" name="budget_max" min="1" value="{{ old('budget_max') }}" placeholder="8500"></div>
                </div>
                    @include('client.partials._service_budget_split', [
                        'pickerName' => 'services',
                        'split'      => old('service_budgets', []),
                        'suggestUrl' => route('client.bsr.suggest-split'),
                    ])

                {{-- Sir Peter, 19 Sep: the client does not pick a payment plan here.
                     The professional proposes one in the agreement, and only then
                     can the client counter it. (The select was never saved.) --}}
                <p style="font-size:12.5px;color:var(--text-muted);margin:8px 0 0;">How payment works is proposed by the professional in the agreement. You can counter it there.</p>
            </div>
        </div>

        {{-- AI Summary (green) --}}
        <div class="do-sec ai">
            <x-form-section :n="6" title="Request Summary" tag="AUTO-DRAFTED" />
            <div class="do-sec-bd">
                <div class="do-ai-row"><span class="ck">✓</span><span>We draft a clear, structured request from your inputs so the pro understands scope instantly.</span></div>
                <div class="do-ai-row"><span class="ck">✓</span><span>Suggests a fair budget band based on your services, location and guest count.</span></div>
                {{-- The sentence is ONE span.
                     .do-ai-row is a flex container, so an inline <b> in loose
                     text becomes its own flex item — which put a 9px gap
                     either side of "MSR" and let the line break there. Sir
                     Peter read it as the acronym being oddly placed; it was
                     the layout, not the wording. --}}
                <div class="do-ai-row" data-types="MSR"><span class="ck">✓</span><span>For an <b id="doTypeLbl">{{ $type }}</b>, each requested service is sent as its own separate agreement.</span></div>
                {{-- Issue #24: the section is labelled AUTO-DRAFTED, which did
                     not say who wrote it. This does, and says the words stay
                     the client's to change. --}}
                <p class="do-ai-note">This summary is drafted for you by GigResource's AI from the answers above. Nothing is sent until you press Send, and you can edit any answer first.</p>
            </div>
        </div>

        <div class="do-foot">
            <p><span>The professional will receive this as a <b id="doTypeLbl2">{{ $type }}</b> and can accept, counter, or ask questions.</span></p>

            @include('client.partials._request_fee_terms', ['action' => 'sending this request'])
            <button type="submit" class="do-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Send Direct Request
            </button>
        </div>
    </form>

    {{-- Contextual rail — fills large screens, stacks under the form on tablet/mobile --}}
    {{-- Shared with the bidding and emergency pages -- see config/request-help.
         It had no "what it'll cost" panel at all, while both of the others did. --}}
    <aside class="hr-rail">
        @include('partials._help_rail', ['flow' => 'dr'])
    </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.getElementById('doRoot');
    var hidden = document.getElementById('doType');

    /*
     * Narrowing by service reloads the page, and the reload has to carry
     * what has already been chosen — above all the request type, which the
     * server reads from ?type= and otherwise defaults back to SSR.
     */
    var svc = document.querySelector('[data-do-service]');
    if (svc) {
        svc.addEventListener('change', function () {
            var url = new URL(svc.dataset.url, window.location.origin);
            var here = new URLSearchParams(window.location.search);
            if (here.get('pro')) url.searchParams.set('pro', here.get('pro'));
            url.searchParams.set('type', hidden.value || 'SSR');
            if (svc.value) url.searchParams.set('service', svc.value);
            window.location = url.toString();
        });
    }
    document.querySelectorAll('.do-type').forEach(function (card) {
        card.addEventListener('click', function () {
            var t = card.getAttribute('data-type');
            document.querySelectorAll('.do-type').forEach(function (c) { c.classList.toggle('sel', c === card); });
            root.setAttribute('data-type', t);
            hidden.value = t;
            // The multi-service block is only hidden, and a hidden field still
            // submits, so what belongs to a multi-service request is switched
            // off rather than merely put out of sight.
            document.querySelectorAll('[data-msr-only]').forEach(function (el) { el.disabled = (t === 'SSR'); });
            document.querySelectorAll('#doTypeLbl, #doTypeLbl2').forEach(function (el) { el.textContent = t; });
        });
    });
})();
</script>
@endpush
