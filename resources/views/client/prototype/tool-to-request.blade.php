@extends('layouts.client')

@section('title', 'Prototype — Tool to Request')
@section('page-title', 'Tool → Request')
@section('page-subtitle', 'Prototype — nothing here is saved.')

@push('styles')
<style>
    .pt { max-width: 980px; }
    /* A prototype that looks like the product is a prototype that gets mistaken
       for the product. This banner never leaves the screen. */
    .pt-flag { display: flex; gap: 12px; align-items: flex-start; background: rgba(245,158,11,0.10); border: 1px solid rgba(245,158,11,0.35); border-radius: 12px; padding: 13px 16px; margin-bottom: 20px; }
    .pt-flag svg { width: 19px; height: 19px; color: var(--warn-text); flex-shrink: 0; margin-top: 1px; }
    .pt-flag b { color: var(--text-primary); font-size: 13.5px; display: block; margin-bottom: 2px; }
    .pt-flag p { margin: 0; font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; }

    .pt-steps { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 22px; font-size: 12px; font-weight: 700; }
    .pt-steps span { padding: 5px 12px; border-radius: 999px; background: var(--bg-card-hover); color: var(--text-muted); }
    .pt-steps span.on { background: rgba(234,88,12,0.12); color: var(--brand-text); }
    .pt-steps i { color: var(--text-muted); font-style: normal; }

    .pt-h { font-size: 19px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; }
    .pt-sub { font-size: 13.5px; color: var(--text-muted); margin: 0 0 18px; }

    .pt-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 14px; }
    .pt-card { display: block; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 18px; text-decoration: none; }
    .pt-card:hover { border-color: #ea580c; }
    .pt-card .ic { width: 38px; height: 38px; border-radius: 10px; background: rgba(234,88,12,0.12); color: var(--brand-text); display: flex; align-items: center; justify-content: center; margin-bottom: 11px; }
    .pt-card .ic svg { width: 18px; height: 18px; }
    .pt-card b { display: block; font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .pt-card p { margin: 0; font-size: 12.5px; color: var(--text-muted); line-height: 1.5; }

    .pt-tag { display: inline-block; font-size: 10px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; padding: 3px 8px; border-radius: 999px; margin-bottom: 9px; }
    .pt-tag.built    { background: rgba(16,185,129,0.14); color: var(--ok-text); }
    .pt-tag.first    { background: rgba(234,88,12,0.14);  color: var(--brand-text); }
    .pt-tag.proposed { background: rgba(99,102,241,0.14); color: var(--accent-text); }

    .pt-panel { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 20px; margin-bottom: 16px; }
    .pt-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .pt-table td { padding: 9px 0; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); }
    .pt-table tr:last-child td { border-bottom: none; }
    .pt-table td:first-child { color: var(--text-primary); font-weight: 600; }
    .pt-table td:last-child { text-align: right; color: var(--text-muted); }

    .pt-carry { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border-color); }
    .pt-chip { font-size: 11.5px; font-weight: 700; padding: 4px 11px; border-radius: 999px; background: rgba(16,185,129,0.12); color: var(--ok-text); }

    .pt-fill { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border-color); font-size: 13px; }
    .pt-fill:last-child { border-bottom: none; }
    .pt-fill .tick { width: 18px; height: 18px; border-radius: 50%; background: #047857; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pt-fill .tick svg { width: 11px; height: 11px; }
    .pt-fill .empty { width: 18px; height: 18px; border-radius: 50%; border: 2px dashed var(--border-color); flex-shrink: 0; }
    .pt-fill b { color: var(--text-primary); }
    .pt-fill span { color: var(--text-muted); margin-left: auto; font-size: 12px; }

    .pt-btns { display: flex; gap: 9px; flex-wrap: wrap; margin-top: 20px; }
    .pt-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); }
    .pt-btn.primary { background: #ea580c; border-color: #ea580c; color: #fff; }
</style>
@endpush

@section('content')
<div class="pt">

    <div class="pt-flag">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>
            <b>This is a prototype, for the design review.</b>
            <p>
                Click through it to see the whole idea. Nothing is saved and nothing is posted —
                the tool results below are representative examples, not calculations.
                Each outcome says whether it exists today or is still a proposal.
            </p>
        </div>
    </div>

    <div class="pt-steps">
        <span class="{{ $step === 'pick' ? 'on' : '' }}">1 · Pick a tool</span><i>→</i>
        <span class="{{ $step === 'result' ? 'on' : '' }}">2 · See what it produced</span><i>→</i>
        <span class="{{ $step === 'outcome' ? 'on' : '' }}">3 · Choose where it goes</span>
    </div>

    @if($step === 'pick')
        <h2 class="pt-h">Start with a tool</h2>
        <p class="pt-sub">The three Khadijah named for the first pass. Names and links come from the real catalogue.</p>
        <div class="pt-grid">
            @foreach($tools as $t)
                <a class="pt-card" href="{{ route('client.prototype.tool-to-request', ['tool' => $t['key']]) }}">
                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></span>
                    <b>{{ $t['name'] }}</b>
                    <p>{{ $t['purpose'] ?? '' }}</p>
                </a>
            @endforeach
        </div>

    @elseif($step === 'result')
        <h2 class="pt-h">{{ $tool['name'] }} — result</h2>
        <p class="pt-sub">{{ $sample['headline'] ?? '' }} · <em>representative example</em></p>

        <div class="pt-panel">
            <table class="pt-table">
                @foreach($sample['rows'] ?? [] as $row)
                    <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                @endforeach
            </table>
            <div class="pt-carry">
                <span style="font-size:12px;color:var(--text-muted);align-self:center;">Carries forward:</span>
                @foreach($sample['carries'] ?? [] as $c)<span class="pt-chip">{{ $c }}</span>@endforeach
            </div>
        </div>

        <h2 class="pt-h" style="margin-top:26px;">Where does this go?</h2>
        <p class="pt-sub">Today a tool stops here. This is the step being proposed.</p>
        <div class="pt-grid">
            @foreach($outcomes as $key => $o)
                <a class="pt-card" href="{{ route('client.prototype.tool-to-request', ['tool' => $tool['key'], 'outcome' => $key]) }}">
                    <span class="pt-tag {{ $o['status'] }}">
                        {{ ['built' => 'Works today', 'first' => 'Building first', 'proposed' => 'Proposed'][$o['status']] }}
                    </span>
                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $o['icon'] !!}</svg></span>
                    <b>{{ $o['label'] }}</b>
                    <p>{{ $o['blurb'] }}</p>
                </a>
            @endforeach
        </div>

    @else
        <h2 class="pt-h">{{ $outcome['label'] }}</h2>
        <p class="pt-sub">From <b>{{ $tool['name'] }}</b> · {{ $outcome['blurb'] }}</p>

        @if($outcomeKey === 'bsr')
            <div class="pt-panel">
                <div style="font-size:12px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:var(--text-muted);margin-bottom:12px;">
                    The bidding request opens with
                </div>
                @php
                    // Which wizard steps the tool can pre-fill, and which the client
                    // still has to answer. Step names are the wizard's own.
                    $prefilled = ['service' => '5 services', 'event' => 'Date, guests, location', 'budget' => '$15,000 total'];
                @endphp
                @foreach($bsrSteps as $key => $label)
                    <div class="pt-fill">
                        @if(isset($prefilled[$key]))
                            <span class="tick"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg></span>
                            <b>{{ $label }}</b><span>{{ $prefilled[$key] }} — from the tool</span>
                        @else
                            <span class="empty"></span>
                            <b style="color:var(--text-muted);font-weight:600;">{{ $label }}</b><span>you fill this in</span>
                        @endif
                    </div>
                @endforeach
                <p style="margin:16px 0 0;font-size:12.5px;color:var(--text-muted);line-height:1.55;">
                    Three of the seven steps arrive already answered, so the client is not
                    typing the same numbers twice. The rest is the wizard as it is today.
                </p>
            </div>
        @elseif($outcomeKey === 'attach')
            <div class="pt-panel">
                <p style="margin:0 0 12px;font-size:13.5px;color:var(--text-secondary);line-height:1.6;">
                    <b style="color:var(--text-primary);">This one is already live.</b>
                    Nine tools carry an “Add to my event” button; the result is stored against
                    the event and appears on its page. Nothing to build — it only needs to sit
                    alongside the other four so the client sees one consistent set of choices.
                </p>
                <a class="pt-btn" href="{{ route('client.events.index') }}">See it on a real event →</a>
            </div>
        @else
            <div class="pt-panel">
                <p style="margin:0;font-size:13.5px;color:var(--text-secondary);line-height:1.6;">
                    Design only — not built, and not in the first pass. It would work the same
                    way as “Post as BR”: the tool's output opens
                    {{ $outcomeKey === 'esr' ? 'the rush request' : ($outcomeKey === 'dsr' ? 'the direct offer' : 'a saved draft') }}
                    with the fields it already knows filled in.
                    Worth deciding after the BR handoff has been used, so the pattern is proven once
                    before it is repeated four times.
                </p>
            </div>
        @endif

        <div class="pt-btns">
            <a class="pt-btn" href="{{ route('client.prototype.tool-to-request', ['tool' => $tool['key']]) }}">← Other outcomes</a>
            <a class="pt-btn" href="{{ route('client.prototype.tool-to-request') }}">Start again</a>
            @if($outcomeKey === 'bsr')
                <a class="pt-btn primary" href="{{ route('client.bsr.step', 'service') }}">Open the real wizard →</a>
            @endif
        </div>
    @endif
</div>
@endsection
