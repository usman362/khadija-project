@extends('layouts.client')
@section('title', 'Post an Event — Customize Package')
@include('client.post-event._styles')

@push('styles')
<style>
    .pe-cz-head { display:flex; gap:16px; align-items:center; padding-bottom:18px; margin-bottom:18px; border-bottom:1px solid var(--pe-line-2); }
    .pe-cz-cover { width:110px; height:78px; border-radius:12px; background-size:cover; background-position:center; flex-shrink:0; }
    .pe-cz-name { font-size:17px; font-weight:800; margin:0 0 3px; }
    .pe-cz-vendor { font-size:12.5px; color:var(--pe-muted); margin:0 0 8px; }
    .pe-cz-stars { font-size:12.5px; font-weight:700; color:var(--pe-ink-2); }
    .pe-cz-stars .st { color:#f59e0b; }

    .pe-cz-table { width:100%; }
    .pe-cz-line { display:grid; grid-template-columns:minmax(0,1.6fr) 110px minmax(0,1.3fr) 120px; gap:12px; align-items:center;
        padding:14px 0; border-bottom:1px solid var(--pe-line-2); }
    .pe-cz-line:last-child { border-bottom:none; }
    @media (max-width:720px){ .pe-cz-line { grid-template-columns:1fr 1fr; } }
    .pe-cz-svc { font-size:13.5px; font-weight:800; color:var(--pe-ink); }
    .pe-cz-cov { font-size:12px; color:var(--pe-muted); margin-top:2px; }
    .pe-cz-inc { display:inline-flex; align-items:center; gap:6px; font-size:12.5px; font-weight:700; color:#15803d; }
    .pe-cz-inc svg { width:15px; height:15px; }
    .pe-cz-sel { font-size:12.5px; color:var(--pe-ink-2); }
    .pe-cz-sel b { color:#15803d; }
    .pe-cz-pricec { text-align:right; }
    .pe-cz-pricec .p { font-size:15px; font-weight:800; }
    .pe-cz-pricec a { display:block; font-size:11.5px; font-weight:700; color:var(--pe-orange-d); text-decoration:none; margin-top:2px; }

    .pe-addon-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
    @media (max-width:900px){ .pe-addon-grid { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:520px){ .pe-addon-grid { grid-template-columns:1fr; } }
    .pe-addon { border:1px solid var(--pe-line); border-radius:14px; overflow:hidden; background:#fff; }
    .pe-addon-img { height:96px; background-size:cover; background-position:center; }
    .pe-addon-body { padding:12px; }
    .pe-addon-nm { font-size:13.5px; font-weight:800; margin:0; }
    .pe-addon-meta { font-size:11.5px; color:var(--pe-muted); margin:2px 0 10px; }
    .pe-addon-btn { width:100%; border:1px solid var(--pe-orange); background:#fff7ed; color:var(--pe-orange-d);
        font-size:12.5px; font-weight:800; font-family:inherit; padding:9px 10px; border-radius:10px; cursor:pointer; }
    .pe-addon-btn:hover { background:#ffedd5; }

    .pe-scroll-x { overflow-x:auto; }
</style>
@endpush

@section('content')
<div class="pe-wrap">
    @include('client.post-event._wizard')

    <div class="pe-container pe-main">
        <h1 class="pe-h1">Customize Your Package</h1>
        <p class="pe-sub">Fine-tune your package — add extras or adjust what is included.</p>

        <div class="pe-grid">
            {{-- Main --}}
            <div>
                {{-- Package header --}}
                <div class="pe-card">
                    <div class="pe-cz-head">
                        <div class="pe-cz-cover" style="background-image:url('https://images.unsplash.com/{{ $package['img'] }}?w=400&q=80&auto=format&fit=crop');"></div>
                        <div style="flex:1;">
                            <h2 class="pe-cz-name">{{ $package['name'] }}</h2>
                            <p class="pe-cz-vendor">By {{ $package['vendor'] }}</p>
                            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                <span class="pe-cz-stars"><span class="st">★</span> {{ $package['rating'] }} ({{ $package['reviews'] }})</span>
                                <span class="pe-badge green">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:12px;height:12px;"><polyline points="20 6 9 17 4 12"/></svg>
                                    Excellent Match
                                </span>
                            </div>
                        </div>
                    </div>

                    <h3 style="font-size:15px; font-weight:800; margin:0 0 6px;">Included Services</h3>
                    <div class="pe-cz-table">
                        @foreach($lines as $line)
                            <div class="pe-cz-line">
                                <div>
                                    <div class="pe-cz-svc">{{ $line['service'] }}</div>
                                    <div class="pe-cz-cov">{{ $line['coverage'] }} · {{ $line['package'] }}</div>
                                </div>
                                <div class="pe-cz-inc">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    Included
                                </div>
                                <div class="pe-cz-sel">Your Selection: <b>Included</b></div>
                                <div class="pe-cz-pricec">
                                    @php $low = (int) preg_replace('/[^0-9]/', '', explode('–', $line['budget'])[0]); @endphp
                                    <span class="p">${{ number_format($low) }}</span>
                                    <a href="#" onclick="return false;">Edit</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Add-ons --}}
                <div class="pe-card">
                    <h2 style="font-size:17px; font-weight:800; margin:0 0 4px;">Add-Ons</h2>
                    <p class="pe-muted" style="margin:0 0 14px;">Enhance your event with popular extras.</p>
                    <div class="pe-scroll-x">
                        <div class="pe-addon-grid">
                            @foreach($addons as $addon)
                                <div class="pe-addon">
                                    <div class="pe-addon-img" style="background-image:url('https://images.unsplash.com/{{ $addon['img'] }}?w=400&q=80&auto=format&fit=crop');"></div>
                                    <div class="pe-addon-body">
                                        <p class="pe-addon-nm">{{ $addon['name'] }}</p>
                                        <p class="pe-addon-meta">{{ $addon['meta'] }}</p>
                                        <button type="button" class="pe-addon-btn" onclick="this.textContent = 'Added ✓';">＋ Add ${{ number_format($addon['price']) }}</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right rail --}}
            <aside class="pe-rail">
                <div class="pe-rail-card">
                    <h4>Your Custom Package</h4>
                    <div class="pe-rail-row"><span class="k">Event Type</span><span class="v">{{ $summary['event_type'] }}</span></div>
                    <div class="pe-rail-row"><span class="k">Date</span><span class="v">{{ $summary['date'] }}</span></div>
                    <div class="pe-rail-row"><span class="k">Guests</span><span class="v">{{ $summary['guests'] }}</span></div>

                    <h4 style="margin:16px 0 8px; font-size:13px;">Services ({{ count($lines) }})</h4>
                    @foreach($lines as $line)
                        <div class="pe-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ $line['service'] }}
                        </div>
                    @endforeach

                    <div class="pe-rail-row" style="margin-top:10px;"><span class="k">Add-Ons</span><span class="v">None selected</span></div>
                    <div class="pe-rail-row"><span class="k">Package Subtotal</span><span class="v">${{ number_format($package['price']) }}</span></div>
                    <div class="pe-rail-row" style="border-top:1px solid var(--pe-line); padding-top:12px; margin-top:4px;">
                        <span class="k" style="font-weight:800; color:var(--pe-ink);">Estimated Total</span>
                        <span class="v" style="font-size:17px; color:var(--pe-orange-d);">${{ number_format($package['price']) }}</span>
                    </div>
                </div>
                @include('client.post-event._rail')
            </aside>
        </div>

        <div class="pe-actions">
            <a href="{{ route('client.post-event.compare') }}" class="pe-btn pe-btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back
            </a>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="button" class="pe-btn pe-btn-ghost" onclick="return false;">Save for Later</button>
                <button type="button" class="pe-btn pe-btn-ghost" onclick="return false;">Reset Changes</button>
                <a href="{{ route('client.post-event.combinations') }}" class="pe-btn">Next: Review
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
