@extends($aiLayout ?? 'layouts.client')

@section('title', 'GigResource IQ™ — AI Tools')
@section('page-title', 'GigResource IQ™')
@section('page-subtitle', 'The intelligence behind every event')

@php
    use App\Domain\AiFeatures\AiToolCatalog;
    use App\Domain\AiFeatures\AiAccess;

    // Role brand accent — orange for clients, blue for professionals (matches the site).
    $accent  = $isPro ? '#2563eb' : '#f97316';
    $accentD = $isPro ? '#1d4ed8' : '#ea580c';
    $accentSoft = $isPro ? 'rgba(37,99,235,.12)' : 'rgba(249,115,22,.12)';
    $suiteKeys = array_keys($suites);
    // The Automation Suite is a future ("Plan A") roadmap item — shown but not active.
    $showComingSoon = ! array_key_exists('automation', $suites);
@endphp

@push('styles')
<style>
    .akt { --akt: {{ $accent }}; --akt-d: {{ $accentD }}; --akt-soft: {{ $accentSoft }}; }

    /* ── Suite selector row ── */
    .akt-nav { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin-bottom: 22px; }
    .akt-scard { position: relative; text-align: left; background: var(--bg-card); border: 1.5px solid var(--border-color); border-radius: 16px; padding: 16px; display: flex; align-items: flex-start; gap: 12px; cursor: pointer; transition: border-color .15s, box-shadow .15s, transform .1s; font-family: inherit; }
    .akt-scard:hover { border-color: var(--akt); transform: translateY(-1px); }
    .akt-scard.on { border-color: var(--akt); box-shadow: 0 0 0 1px var(--akt), 0 10px 24px -12px var(--akt); }
    .akt-scard.soon { cursor: default; opacity: .72; }
    .akt-scard.soon:hover { border-color: var(--border-color); transform: none; }
    .akt-sic { width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--akt-soft); color: var(--akt); }
    .akt-sic svg { width: 22px; height: 22px; }
    .akt-scard.soon .akt-sic { background: var(--border-color); color: var(--text-muted); }
    .akt-sbody { min-width: 0; flex: 1; display: flex; flex-direction: column; }
    .akt-sname { display: block; font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .akt-stag { display: block; font-size: 11.5px; color: var(--text-muted); line-height: 1.4; margin-top: 3px; }
    .akt-scount { font-size: 22px; font-weight: 800; color: var(--akt); line-height: 1; }
    .akt-scount small { display: block; font-size: 10px; font-weight: 700; color: var(--text-muted); margin-top: 3px; text-transform: uppercase; letter-spacing: .3px; }
    .akt-scard.soon .akt-scount { color: var(--text-muted); }
    .akt-here { position: absolute; top: -11px; left: 16px; font-size: 10px; font-weight: 800; color: #fff; background: var(--akt); padding: 3px 12px; border-radius: 999px; letter-spacing: .3px; }
    .akt-soonpill { position: absolute; top: -11px; left: 16px; font-size: 10px; font-weight: 800; color: var(--text-muted); background: var(--bg-card); border: 1px solid var(--border-color); padding: 3px 12px; border-radius: 999px; }

    /* ── Suite banner ── */
    .akt-banner { display: flex; align-items: center; gap: 18px; background: linear-gradient(120deg, var(--akt-soft), transparent 70%); border: 1px solid var(--border-color); border-radius: 18px; padding: 20px 24px; margin-bottom: 20px; position: relative; overflow: hidden; }
    .akt-bic { width: 60px; height: 60px; border-radius: 15px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--bg-card); border: 1px solid var(--border-color); color: var(--akt); }
    .akt-bic svg { width: 30px; height: 30px; }
    .akt-banner h2 { font-size: 26px; font-weight: 800; color: var(--akt); line-height: 1.1; }
    .akt-banner p { font-size: 13.5px; color: var(--text-secondary); margin-top: 4px; }
    .akt-dots { margin-left: auto; display: grid; grid-template-columns: repeat(5, 6px); gap: 9px; opacity: .5; }
    .akt-dots i { width: 6px; height: 6px; border-radius: 50%; background: var(--akt); display: block; }
    @media (max-width: 640px) { .akt-dots { display: none; } }

    /* ── Tool cards ── */
    .akt-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 16px; }
    .akt-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; display: flex; flex-direction: column; transition: border-color .15s, box-shadow .15s, transform .1s; }
    .akt-card:hover { border-color: var(--akt); transform: translateY(-2px); box-shadow: 0 12px 28px -16px var(--akt); }
    .akt-chd { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
    .akt-ic { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--akt), var(--akt-d)); color: #fff; box-shadow: 0 8px 18px -8px var(--akt); }
    .akt-ic svg { width: 23px; height: 23px; }
    .akt-num { width: 30px; height: 30px; border-radius: 50%; background: var(--akt-soft); color: var(--akt); font-size: 12px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
    .akt-name { font-size: 15.5px; font-weight: 800; color: var(--text-primary); }
    .akt-badges { display: flex; gap: 6px; flex-wrap: wrap; margin: 6px 0 10px; }
    .akt-badge { font-size: 10px; font-weight: 800; padding: 2px 9px; border-radius: 999px; text-transform: uppercase; letter-spacing: .3px; }
    .akt-badge.client { background: rgba(249,115,22,.14); color: #ea580c; }
    .akt-badge.professional { background: rgba(37,99,235,.14); color: #2563eb; }
    .akt-badge.both { background: rgba(22,163,74,.14); color: #16a34a; }
    .akt-lvl { font-size: 10px; font-weight: 800; padding: 2px 9px; border-radius: 999px; letter-spacing: .2px; white-space: nowrap; }
    .lvl-manual  { background: rgba(100,116,139,.16); color: #64748b; }
    .lvl-semi    { background: rgba(37,99,235,.15);  color: #2563eb; }
    .lvl-maximum { background: rgba(22,163,74,.16);  color: #16a34a; }
    .lvl-none    { background: rgba(239,68,68,.14);  color: #ef4444; }
    .akt-purpose { font-size: 12.5px; color: var(--text-muted); line-height: 1.5; }
    .akt-feats { list-style: none; margin: 12px 0 14px; padding: 12px 0 0; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 7px; flex: 1; }
    .akt-feats li { font-size: 12px; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; }
    .akt-feats svg { width: 15px; height: 15px; color: var(--akt); flex-shrink: 0; }
    .akt-use { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 11px; border-radius: 11px; background: linear-gradient(135deg, var(--akt), var(--akt-d)); color: #fff; font-size: 13px; font-weight: 800; text-decoration: none; }
    .akt-use svg { width: 15px; height: 15px; }
    .akt-soon { display: flex; align-items: center; justify-content: center; padding: 11px; border-radius: 11px; border: 1px dashed var(--border-color); color: var(--text-muted); font-size: 12.5px; font-weight: 800; }

    .akt-panel { display: none; }
    .akt-panel.on { display: block; animation: aktFade .25s ease; }
    @keyframes aktFade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

    @media (max-width: 1100px) { .akt-grid { grid-template-columns: repeat(2, 1fr); } .akt-nav { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px) { .akt-grid, .akt-nav { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="akt">

    <div style="display:flex; justify-content:flex-end; margin-bottom:14px;">
        @include('partials._ai_credits_badge')
    </div>

    {{-- ── Suite selector ── --}}
    <div class="akt-nav">
        @foreach($suites as $sk => $s)
            <button type="button" class="akt-scard {{ $loop->first ? 'on' : '' }}" data-suite="{{ $sk }}">
                @if($loop->first)<span class="akt-here">You're here</span>@endif
                <span class="akt-sic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! AiToolCatalog::suiteIcon($sk) !!}</svg></span>
                <span class="akt-sbody">
                    <span class="akt-sname">{{ $s['name'] }}</span>
                    <span class="akt-stag">{{ $s['tagline'] }}</span>
                </span>
                <span class="akt-scount">{{ count($s['tools']) }}<small>AI Tools</small></span>
            </button>
        @endforeach

        @if($showComingSoon)
            @php($auto = AiToolCatalog::suites()['automation'])
            <div class="akt-scard soon">
                <span class="akt-soonpill">Coming soon</span>
                <span class="akt-sic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! AiToolCatalog::suiteIcon('automation') !!}</svg></span>
                <span class="akt-sbody">
                    <span class="akt-sname">{{ $auto['name'] }}</span>
                    <span class="akt-stag">Workflow automation, analytics &amp; forecasting.</span>
                </span>
                <span class="akt-scount">—<small>Plan A</small></span>
            </div>
        @endif
    </div>

    {{-- ── Suite panels (one shown at a time) ── --}}
    @foreach($suites as $sk => $s)
        <section class="akt-panel {{ $loop->first ? 'on' : '' }}" data-panel="{{ $sk }}">
            <div class="akt-banner">
                <span class="akt-bic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! AiToolCatalog::suiteIcon($sk) !!}</svg></span>
                <div>
                    <h2>{{ $s['name'] }}</h2>
                    <p>{{ $s['tagline'] }}</p>
                </div>
                <div class="akt-dots">@for($i=0;$i<15;$i++)<i></i>@endfor</div>
            </div>

            <div class="akt-grid">
                @foreach($s['tools'] as $t)
                    @php($lvl = AiAccess::level(auth()->user(), $t['key']))
                    <div class="akt-card">
                        <div class="akt-chd">
                            <span class="akt-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! AiToolCatalog::icon($t['key']) !!}</svg></span>
                            <span class="akt-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="akt-name">{{ $t['name'] }}</div>
                        {{-- Audience tag (Client/Professional/Both) intentionally not shown here —
                             the hub is already filtered to each user's own tools, so it's redundant
                             for them. It's admin-facing info only. --}}
                        <div class="akt-badges">
                            <span class="akt-lvl lvl-{{ $lvl }}" title="Your AI level">{{ AiAccess::label($lvl) }}</span>
                        </div>
                        <p class="akt-purpose">{{ $t['purpose'] }}</p>
                        <ul class="akt-feats">
                            @foreach(AiToolCatalog::features($t['key']) as $f)
                                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/></svg>{{ $f }}</li>
                            @endforeach
                        </ul>
                        @if($t['status'] === 'live')
                            <a href="{{ route($t['route']) }}" class="akt-use">Use Tool <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                        @else
                            <span class="akt-soon">Coming soon</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>

@push('scripts')
<script>
(function () {
    var cards = document.querySelectorAll('.akt-scard[data-suite]');
    var panels = document.querySelectorAll('.akt-panel');
    cards.forEach(function (c) {
        c.addEventListener('click', function () {
            var suite = c.getAttribute('data-suite');
            cards.forEach(function (x) { x.classList.toggle('on', x === c); });
            // move "You're here" pill to the active card
            document.querySelectorAll('.akt-here').forEach(function (p) { p.remove(); });
            var pill = document.createElement('span'); pill.className = 'akt-here'; pill.textContent = "You're here"; c.prepend(pill);
            panels.forEach(function (p) { p.classList.toggle('on', p.getAttribute('data-panel') === suite); });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
})();
</script>
@endpush
@endsection
