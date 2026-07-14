@extends('layouts.client')
@section('title', 'Post an Event — Compare Packages')
@include('client.post-event._styles')

@push('styles')
<style>
    .pe-cmp-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; align-items:start; }
    @media (max-width:820px){ .pe-cmp-grid { grid-template-columns:1fr; } }
    .pe-cmp-grid .pe-pkg { margin-bottom:0; }
    .pe-pkg-cover.tall { height:150px; }
    .pe-pkg-badge.best { background:var(--pe-green); }
    .pe-cmp-name { font-size:15.5px; font-weight:800; margin:0 0 3px; line-height:1.3; }
    .pe-cmp-vendor { font-size:12.5px; color:var(--pe-muted); margin:0 0 8px; }
    .pe-cmp-stars { font-size:12.5px; font-weight:700; color:var(--pe-ink-2); }
    .pe-cmp-stars .st { color:#f59e0b; }
    .pe-cmp-mid { display:flex; align-items:center; gap:12px; margin:12px 0; padding:12px 0; border-top:1px solid var(--pe-line-2); border-bottom:1px solid var(--pe-line-2); }
    .pe-cmp-mid .lbl { font-size:11px; font-weight:700; color:var(--pe-muted); text-transform:uppercase; letter-spacing:.4px; }
    .pe-cmp-mid .pe-price { font-size:20px; }
    .pe-cmp-feat { display:flex; align-items:flex-start; gap:8px; font-size:12.5px; color:var(--pe-ink-2); padding:5px 0; }
    .pe-cmp-feat svg { width:15px; height:15px; color:var(--pe-green); flex-shrink:0; margin-top:1px; }
    .pe-cmp-btn { margin-top:14px; width:100%; }

    .pe-toggle-row { display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin:-8px 0 22px; }
    .pe-toggle { display:inline-flex; align-items:center; gap:8px; font-size:12.5px; font-weight:700; color:var(--pe-ink-2); cursor:pointer; user-select:none; }
    .pe-toggle .sw { width:36px; height:20px; border-radius:999px; background:var(--pe-line); position:relative; transition:.15s; flex-shrink:0; }
    .pe-toggle .sw::after { content:''; position:absolute; top:2px; left:2px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.2); transition:.15s; }
    .pe-toggle.on .sw { background:var(--pe-orange); }
    .pe-toggle.on .sw::after { left:18px; }
</style>
@endpush

@section('content')
<div class="pe-wrap">
    @include('client.post-event._wizard')

    <div class="pe-container pe-main">
        <h1 class="pe-h1">Compare Packages Side by Side</h1>
        <p class="pe-sub">Compare the top matches and choose the best package for your event.</p>

        <div class="pe-toggle-row">
            <span class="pe-toggle" onclick="this.classList.toggle('on')"><span class="sw"></span> Show Differences Only</span>
            <span class="pe-toggle on" onclick="this.classList.toggle('on')"><span class="sw"></span> Expert Comparison</span>
        </div>

        <div class="pe-grid">
            {{-- Main comparison grid --}}
            <div>
                <div class="pe-cmp-grid">
                    @foreach($packages as $package)
                        <div class="pe-pkg">
                            <div class="pe-pkg-cover tall" style="background-image:url('https://images.unsplash.com/{{ $package['img'] }}?w=400&q=80&auto=format&fit=crop');">
                                <span class="pe-pkg-badge {{ $loop->first ? 'best' : '' }}">{{ $package['badge'] ?? 'Best Match' }}</span>
                            </div>
                            <div class="pe-pkg-body">
                                <h3 class="pe-cmp-name">{{ $package['name'] }}</h3>
                                <p class="pe-cmp-vendor">By {{ $package['vendor'] }}</p>
                                <div class="pe-cmp-stars"><span class="st">★</span> {{ $package['rating'] }} ({{ $package['reviews'] }})</div>

                                <div class="pe-cmp-mid">
                                    <div class="pe-ring sm" style="--v:{{ $package['match'] }}"><b>{{ $package['match'] }}%</b></div>
                                    <div>
                                        <div class="lbl">Total Price</div>
                                        <div class="pe-price">${{ number_format($package['price']) }}</div>
                                    </div>
                                </div>

                                <div>
                                    @foreach($package['services'] as $svc)
                                        <div class="pe-cmp-feat">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                            {{ $svc }}
                                        </div>
                                    @endforeach
                                    @foreach(['6 Hours Coverage', 'Insurance Included', 'Contract & Secure Payment'] as $rep)
                                        <div class="pe-cmp-feat">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                            {{ $rep }}
                                        </div>
                                    @endforeach
                                </div>

                                <a href="{{ route('client.post-event.customize') }}" class="pe-btn pe-cmp-btn">View Package Details</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Right rail --}}
            <aside class="pe-rail">
                <div class="pe-rail-card">
                    <h4>Comparison Tools</h4>
                    <div class="pe-rail-row" onclick="void(0)" style="cursor:pointer;">
                        <span class="k">Show differences</span><span class="v">Off</span>
                    </div>
                    <div class="pe-rail-row" onclick="window.print()" style="cursor:pointer;">
                        <span class="k">Print comparison</span><span class="v">→</span>
                    </div>
                    <p class="pe-muted" style="margin:12px 0 0;">Side-by-side view helps you weigh services, price and match before you choose.</p>
                </div>
                @include('client.post-event._rail')
            </aside>
        </div>

        <div class="pe-actions">
            <a href="{{ route('client.post-event.results') }}" class="pe-btn pe-btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back
            </a>
            <a href="{{ route('client.post-event.customize') }}" class="pe-btn">Customize Package
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>
</div>
@endsection
