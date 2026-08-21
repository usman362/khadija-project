@extends($layout)

@section('title', 'Plan with Toolkit')
@section('page-title', 'Plan with Toolkit')
@section('page-subtitle', 'Put what a tool worked out into a request or an agreement.')

@push('styles')
<style>
    .pw { max-width: 1180px; }
    .pw-head { margin-bottom: 18px; }
    .pw-head h1 { font-size: 23px; font-weight: 800; margin: 0 0 4px; color: var(--text-primary); }
    .pw-head p  { font-size: 13px; color: var(--text-muted); margin: 0; }

    .pw-note { display: flex; gap: 10px; align-items: flex-start; background: var(--bg-card);
               border: 1px solid var(--border-color); border-left: 3px solid var(--accent-blue);
               border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; font-size: 13px; }
    .pw-note b { display: block; color: var(--text-primary); }
    .pw-note .sub { color: var(--text-muted); font-size: 12.5px; }

    .pw-steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; }
    .pw-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; }
    .pw-card h2 { font-size: 12px; text-transform: uppercase; letter-spacing: .05em;
                  color: var(--text-muted); font-weight: 800; margin: 0 0 12px; }
    .pw-step-n { display: inline-flex; width: 20px; height: 20px; border-radius: 50%;
                 background: var(--accent-blue); color: #fff; align-items: center; justify-content: center;
                 font-size: 11px; font-weight: 800; margin-right: 7px; }

    .pw-tool { display: flex; align-items: center; justify-content: space-between; gap: 8px;
               padding: 9px 11px; border: 1px solid var(--border-color); border-radius: 9px;
               margin-bottom: 7px; font-size: 13px; text-decoration: none; color: var(--text-primary); }
    .pw-tool.on { border-color: var(--accent-orange, #f97316); background: rgba(249,115,22,.08); font-weight: 700; }
    .pw-tool.off { opacity: .62; cursor: default; }
    .pw-tool small { display: block; font-size: 11px; color: var(--text-muted); font-weight: 400; margin-top: 2px; }

    .pw-row { display: block; padding: 10px 12px; border: 1px solid var(--border-color);
              border-radius: 9px; margin-bottom: 8px; font-size: 13px; text-decoration: none; color: var(--text-primary); }
    .pw-row.on { border-color: var(--accent-blue); background: rgba(59,130,246,.07); }
    .pw-row .meta { display: block; font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .pw-row.blocked { opacity: .72; }

    .pw-empty { font-size: 12.5px; color: var(--text-muted); padding: 10px 2px; }

    .pw-mode { display: block; border: 1px solid var(--border-color); border-radius: 9px;
               padding: 11px 12px; margin-bottom: 9px; cursor: pointer; font-size: 13px; }
    .pw-mode input { margin-right: 7px; }
    .pw-mode small { display: block; color: var(--text-muted); font-size: 11.5px; margin: 3px 0 0 21px; }

    .pw-btn { display: inline-block; width: 100%; text-align: center; padding: 10px 16px; border: none;
              border-radius: 9px; background: var(--accent-orange, #f97316); color: #fff;
              font-weight: 800; font-size: 13px; cursor: pointer; font-family: inherit; }
    .pw-btn[disabled] { opacity: .45; cursor: not-allowed; }

    .pw-placed { margin-top: 18px; }
    .pw-chip { display: inline-block; font-size: 10.5px; font-weight: 800; padding: 2px 7px;
               border-radius: 20px; text-transform: uppercase; letter-spacing: .04em; }
    .pw-chip.copy   { background: rgba(100,116,139,.16); color: var(--text-muted); }
    .pw-chip.linked { background: rgba(59,130,246,.14); color: var(--accent-blue); }
    .pw-chip.review { background: rgba(245,158,11,.16); color: #b45309; }

    .pw-item { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;
               padding: 12px 0; border-bottom: 1px solid var(--border-color); }
    .pw-item:last-child { border-bottom: 0; }
    .pw-item .t { font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
    .pw-item .s { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; }
    .pw-mini { border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);
               border-radius: 7px; padding: 5px 10px; font-size: 12px; cursor: pointer; font-family: inherit; }

    .pw-alert { border-radius: 10px; padding: 12px 14px; font-size: 13px; margin-bottom: 16px; }
    .pw-alert.warn { background: rgba(245,158,11,.10); border: 1px solid rgba(245,158,11,.35); }
    .pw-alert.err  { background: rgba(239,68,68,.10);  border: 1px solid rgba(239,68,68,.35); }
</style>
@endpush

@section('content')
<div class="pw">
    <div class="pw-head">
        <h1>Plan with Toolkit</h1>
        <p>Take something a tool already worked out and add it to an open request, or to one professional's agreement.</p>
    </div>

    @if(session('error'))
        <div class="pw-alert err">{{ session('error') }}</div>
    @endif

    {{-- The mockup opened with Semi / Maximum buttons for the client to pick
         their own tier. Nothing sells the toolkit yet and no purchase is
         recorded, so a picker would let someone choose a tier they never
         bought. The screen states the position instead. --}}
    @if($launchOpen)
        <div class="pw-note">
            <span>✅</span>
            <span>
                <b>Every toolkit tool is open on your account.</b>
                <span class="sub">The toolkit is not on sale yet, so nothing here is limited by an add-on. When tiers go on sale, only the tools in your add-on will appear.</span>
            </span>
        </div>
    @endif

    {{-- Linked data whose source moved. Shown first because it concerns
         something already sitting in a request or an agreement. --}}
    @if($pending->isNotEmpty())
        <div class="pw-alert warn">
            <b>{{ $pending->count() }} linked {{ Str::plural('item', $pending->count()) }} changed in the toolkit.</b>
            Nothing was altered where you placed it. Review each one below and choose whether to take the new version.
            @foreach($pending as $p)
                <div class="pw-item">
                    <div>
                        <div class="t">{{ $p->title }} <span class="pw-chip review">Needs review</span></div>
                        <div class="s">From {{ $p->tool_name }} · placed {{ $p->created_at->format('M j, Y') }}</div>
                    </div>
                    <div style="display:flex; gap:7px; flex-shrink:0;">
                        <form method="POST" action="{{ route('client.toolkit.placed.apply', $p) }}">@csrf
                            <button class="pw-mini" type="submit">Take the new version</button>
                        </form>
                        <form method="POST" action="{{ route('client.toolkit.placed.keep', $p) }}">@csrf
                            <button class="pw-mini" type="submit">Keep as it is</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('client.toolkit.plan.store') }}">
        @csrf
        <input type="hidden" name="result" value="{{ $artifact?->id }}">
        <input type="hidden" name="destination" value="{{ $destination ? (isset($destination['model']) && $destination['model'] instanceof \App\Models\Event ? 'request:' : 'agreement:') . $destination['id'] : '' }}">

        <div class="pw-steps">
            {{-- 1 — the tool --}}
            <div class="pw-card">
                <h2><span class="pw-step-n">1</span>Choose a tool</h2>

                @foreach($tools as $tool)
                    @if($tool['usable'])
                        <a class="pw-tool {{ $selectedTool && $selectedTool['key'] === $tool['key'] ? 'on' : '' }}"
                           href="{{ route('client.toolkit.plan', ['tool' => $tool['key']] + ($destination ? ['to' => request('to')] : [])) }}">
                            <span>{{ $tool['name'] }}</span>
                        </a>
                    @else
                        <div class="pw-tool off">
                            <span>
                                {{ $tool['name'] }}
                                <small>{{ $tool['reason'] }}</small>
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- 2 — the saved result --}}
            <div class="pw-card">
                <h2><span class="pw-step-n">2</span>Choose saved data</h2>

                @if(! $selectedTool)
                    <div class="pw-empty">Pick a tool first.</div>
                @elseif($saved->isEmpty())
                    <div class="pw-empty">
                        You have not saved anything from {{ $selectedTool['name'] }} yet.
                        <a href="{{ route($selectedTool['route']) }}" class="pw-link">Open the tool</a> and save a result first.
                    </div>
                @else
                    @foreach($saved as $s)
                        <a class="pw-row {{ $artifact && $artifact->id === $s->id ? 'on' : '' }}"
                           href="{{ route('client.toolkit.plan', array_filter(['tool' => $selectedTool['key'], 'result' => $s->id, 'to' => request('to')])) }}">
                            {{ $s->title }}
                            <span class="meta">Saved {{ $s->created_at->format('M j, Y') }}</span>
                        </a>
                    @endforeach
                @endif
            </div>

            {{-- 3 — where it goes --}}
            <div class="pw-card">
                <h2><span class="pw-step-n">3</span>Choose where it goes</h2>

                @php $anyDest = count($destinations['requests']) + count($destinations['agreements']); @endphp

                @if($anyDest === 0)
                    <div class="pw-empty">You have no open requests or agreements to add to yet.</div>
                @endif

                @if(count($destinations['requests']))
                    <div class="pw-empty" style="font-weight:700;color:var(--text-primary);">Open requests</div>
                    @foreach($destinations['requests'] as $d)
                        <a class="pw-row {{ $destination && $destination['id'] === $d['id'] && $d['model'] instanceof \App\Models\Event ? 'on' : '' }}"
                           href="{{ route('client.toolkit.plan', array_filter(['tool' => $selectedTool['key'] ?? null, 'result' => $artifact?->id, 'to' => 'request:'.$d['id']])) }}">
                            {{ $d['label'] }}
                            <span class="meta">{{ $d['meta'] ?? 'No date set' }}</span>
                        </a>
                    @endforeach
                @endif

                @if(count($destinations['agreements']))
                    <div class="pw-empty" style="font-weight:700;color:var(--text-primary);margin-top:10px;">Professional agreements</div>
                    {{-- One row per professional, never one for "the event". On a
                         multi-service request the guest count that is true for the
                         caterer is not something the DJ should be bound to. --}}
                    @foreach($destinations['agreements'] as $d)
                        @if($d['eligible'])
                            <a class="pw-row {{ $destination && $destination['id'] === $d['id'] && $d['model'] instanceof \App\Models\Agreement ? 'on' : '' }}"
                               href="{{ route('client.toolkit.plan', array_filter(['tool' => $selectedTool['key'] ?? null, 'result' => $artifact?->id, 'to' => 'agreement:'.$d['id']])) }}">
                                {{ $d['label'] }}
                                <span class="meta">{{ $d['meta'] ?? 'Professional' }}</span>
                            </a>
                        @else
                            <div class="pw-row blocked">
                                {{ $d['label'] }}
                                <span class="meta">{{ $d['meta'] }} — {{ $d['reason'] }}</span>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>

            {{-- 4 — how --}}
            <div class="pw-card">
                <h2><span class="pw-step-n">4</span>Choose how it is added</h2>

                <label class="pw-mode">
                    <input type="radio" name="link_mode" value="copy" checked>
                    <b>Add a copy</b>
                    <small>A snapshot. If you change this result in the toolkit later, what you placed here stays as it is.</small>
                </label>

                <label class="pw-mode">
                    <input type="radio" name="link_mode" value="linked">
                    <b>Keep linked</b>
                    <small>Follows the original — but a change is never applied on its own. You will be asked here first.</small>
                </label>

                <button class="pw-btn" type="submit" @disabled(! $artifact || ! $destination || ! ($destination['eligible'] ?? false))>
                    @if(! $artifact)
                        Choose saved data first
                    @elseif(! $destination)
                        Choose where it goes
                    @else
                        Add to “{{ Str::limit($destination['label'], 28) }}”
                    @endif
                </button>
            </div>
        </div>
    </form>

    {{-- What is already on the chosen destination — the "preview placement"
         step. Somebody about to add a second budget should see the first. --}}
    @if($destination)
        <div class="pw-card pw-placed">
            <h2>Already on “{{ $destination['label'] }}”</h2>

            @if($placed->isEmpty())
                <div class="pw-empty">Nothing from the toolkit has been added here yet.</div>
            @else
                @foreach($placed as $p)
                    <div class="pw-item">
                        <div>
                            <div class="t">
                                {{ $p->title }}
                                <span class="pw-chip {{ $p->isLinked() ? 'linked' : 'copy' }}">{{ $p->isLinked() ? 'Linked' : 'Copy' }}</span>
                                @if($p->needs_review)<span class="pw-chip review">Needs review</span>@endif
                            </div>
                            {{-- Labelled with the tool and the time, so a figure
                                 inside an agreement can be traced to what made it. --}}
                            <div class="s">From {{ $p->tool_name }} · added {{ $p->created_at->format('M j, Y \a\t g:i A') }}</div>
                        </div>
                        <form method="POST" action="{{ route('client.toolkit.placed.destroy', $p) }}"
                              onsubmit="return confirm('Remove this from “{{ $destination['label'] }}”? Your saved tool result is kept.');">
                            @csrf @method('DELETE')
                            <button class="pw-mini" type="submit">Remove</button>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>
    @endif
</div>
@endsection
