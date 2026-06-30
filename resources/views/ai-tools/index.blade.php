@extends($aiLayout ?? 'layouts.client')

@section('title', 'GigResource IQ™')
@section('page-title', 'GigResource IQ™')
@section('page-subtitle', 'The intelligence behind every event — your AI suites')

{{-- AI Toolkit hub, organised into the 5 GigResource IQ™ suites (Peter /
     ChatGPT). Catalog-driven: each suite shows the tools for this user's role.
     Live = "Use tool", planned = "Coming soon". --}}

@push('styles')
<style>
    .akt { --akt: #6366f1; --akt-strong: #4f46e5; }
    .akt-brand { display: flex; align-items: center; gap: 14px; background: linear-gradient(120deg, rgba(99,102,241,.12), rgba(79,70,229,.06)); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px 20px; margin-bottom: 22px; flex-wrap: wrap; }
    .akt-brand h2 { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .akt-brand h2 span { background: linear-gradient(135deg, var(--akt), var(--akt-strong)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
    .akt-brand p { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }
    .akt-brand .stat { margin-left: auto; display: flex; gap: 18px; }
    .akt-brand .stat b { display: block; font-size: 20px; font-weight: 800; color: var(--akt); text-align: center; } .akt-brand .stat span { font-size: 10.5px; color: var(--text-muted); }

    .akt-suite { margin-bottom: 26px; }
    .akt-suite-hd { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .akt-suite-em { width: 42px; height: 42px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .akt-suite-hd h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .akt-suite-hd h3 small { font-weight: 700; color: var(--akt); }
    .akt-suite-hd p { font-size: 12.5px; color: var(--text-muted); margin-top: 1px; }
    .akt-suite-n { margin-left: auto; font-size: 11.5px; font-weight: 800; color: var(--text-muted); background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 999px; padding: 5px 12px; white-space: nowrap; }

    .akt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 13px; }
    .akt-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; display: flex; flex-direction: column; }
    .akt-card.planned { opacity: .82; }
    .akt-top { display: flex; align-items: center; gap: 11px; margin-bottom: 10px; }
    .akt-ic { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, var(--akt), var(--akt-strong)); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .akt-ic svg { width: 19px; height: 19px; color: #fff; fill: none; stroke: currentColor; stroke-width: 2; }
    .akt-card.planned .akt-ic { background: var(--border-color); } .akt-card.planned .akt-ic svg { color: var(--text-muted); }
    .akt-name { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .akt-badge { font-size: 9.5px; font-weight: 800; letter-spacing: .3px; padding: 2px 8px; border-radius: 999px; margin-top: 3px; display: inline-block; }
    .akt-badge.client { background: rgba(249,115,22,.12); color: #c2590a; }
    .akt-badge.professional { background: rgba(37,99,235,.12); color: #1d4ed8; }
    .akt-badge.both { background: rgba(22,163,74,.12); color: #15803d; }
    .akt-purpose { font-size: 12px; color: var(--text-muted); line-height: 1.5; flex: 1; }
    .akt-foot { margin-top: 13px; }
    .akt-use { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--akt), var(--akt-strong)); border-radius: 9px; padding: 9px 16px; text-decoration: none; }
    .akt-use svg { width: 14px; height: 14px; }
    .akt-soon { display: inline-flex; font-size: 11.5px; font-weight: 800; color: var(--text-muted); background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 9px; padding: 8px 14px; }

    .akt-future { border: 1px dashed var(--border-color); border-radius: 14px; padding: 16px 18px; display: flex; align-items: center; gap: 12px; }
    .akt-future .em { font-size: 22px; } .akt-future h4 { font-size: 14px; font-weight: 800; color: var(--text-primary); } .akt-future p { font-size: 12px; color: var(--text-muted); margin-top: 1px; }
    .akt-future .tag { margin-left: auto; font-size: 10.5px; font-weight: 800; color: var(--text-muted); background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 999px; padding: 4px 11px; }
</style>
@endpush

@section('content')
@php
    $audLabel = ['client' => 'Client', 'professional' => 'Pro', 'both' => 'Both'];
    $icon = '<path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/>';
@endphp
<div class="akt">
    {{-- Master brand --}}
    <div class="akt-brand">
        <div>
            <h2><span>GigResource IQ™</span></h2>
            <p>The intelligence behind every event — {{ $isPro ? 'tools to grow your business' : 'plan smarter before you spend' }}.</p>
        </div>
        <div class="stat">
            <div><b>{{ $liveCount }}</b><span>AI tools</span></div>
            <div><b>{{ count($suites) }}</b><span>suites</span></div>
        </div>
    </div>

    {{-- Suites --}}
    @foreach($suites as $skey => $suite)
        <div class="akt-suite">
            <div class="akt-suite-hd">
                <span class="akt-suite-em">{{ $suite['emoji'] }}</span>
                <div>
                    <h3>GigResource IQ™ <small>{{ $suite['name'] }}</small></h3>
                    <p>{{ $suite['tagline'] }}</p>
                </div>
                <span class="akt-suite-n">{{ count($suite['tools']) }} {{ \Illuminate\Support\Str::plural('tool', count($suite['tools'])) }}</span>
            </div>
            <div class="akt-grid">
                @foreach($suite['tools'] as $t)
                    <div class="akt-card {{ $t['status'] === 'live' ? '' : 'planned' }}">
                        <div class="akt-top">
                            <span class="akt-ic"><svg viewBox="0 0 24 24">{!! $icon !!}</svg></span>
                            <div>
                                <div class="akt-name">{{ $t['name'] }}</div>
                                <span class="akt-badge {{ $t['audience'] }}">{{ $audLabel[$t['audience']] }}</span>
                            </div>
                        </div>
                        <p class="akt-purpose">{{ $t['purpose'] }}</p>
                        <div class="akt-foot">
                            @if($t['status'] === 'live')
                                <a href="{{ route($t['route']) }}" class="akt-use">Use tool <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                            @else
                                <span class="akt-soon">Coming soon</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- Automation Suite — future --}}
    <div class="akt-future">
        <span class="em">🚀</span>
        <div><h4>GigResource IQ™ Automation Suite</h4><p>Workflow automation, analytics, forecasting & AI insights.</p></div>
        <span class="tag">Coming soon</span>
    </div>
</div>
@endsection
