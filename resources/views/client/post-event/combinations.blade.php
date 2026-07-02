@extends('layouts.client')
@section('title', 'Post an Event — Package Combinations')
@include('client.post-event._styles')

@section('content')
<div class="pe-wrap">
    @include('client.post-event._wizard')

    <div class="pe-container pe-main">
        <h1 class="pe-h1">Package Combinations</h1>
        <p class="pe-sub">Fulfil all your requested services by combining these packages.</p>

        {{-- AI Tip --}}
        <div class="pe-aitip" style="margin-bottom:22px;">
            <span class="ic">✨</span>
            <div>
                <h4>AI Tip</h4>
                <p>When no single package covers everything, our AI bundles the best combinations to give you full coverage at the best value.</p>
            </div>
        </div>

        <div class="pe-grid">
            {{-- Main --}}
            <div>
                @foreach($combos as $combo)
                    <div class="pe-card">
                        <div style="display:flex; align-items:flex-start; gap:18px; flex-wrap:wrap;">
                            {{-- Left: combined packages --}}
                            <div style="flex:1; min-width:260px;">
                                <div style="margin-bottom:14px;">
                                    <span class="pe-badge orange">{{ $combo['label'] }}</span>
                                </div>

                                <div style="display:flex; align-items:stretch; flex-wrap:wrap; gap:10px;">
                                    @foreach($combo['packages'] as $pkg)
                                        @php $img = $pkg['img']; @endphp
                                        <div style="flex:1; min-width:170px; border:1px solid var(--pe-line); border-radius:12px; overflow:hidden; background:#fff;">
                                            <div style="height:96px; background:#eee url('https://images.unsplash.com/{{ $img }}?w=600&q=80&auto=format&fit=crop') center/cover no-repeat;"></div>
                                            <div style="padding:12px 12px 14px;">
                                                <div style="font-weight:800; font-size:13.5px; line-height:1.3;">{{ $pkg['name'] }}</div>
                                                <div class="pe-muted" style="margin:2px 0 8px;">By {{ $pkg['vendor'] }}</div>
                                                <div class="pe-price" style="font-size:16px;">${{ number_format($pkg['price']) }}</div>
                                            </div>
                                        </div>

                                        @unless($loop->last)
                                            <div style="display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:800; color:var(--pe-muted); flex:0 0 auto; padding:0 2px;">＋</div>
                                        @endunless
                                    @endforeach
                                </div>
                            </div>

                            {{-- Right: match + total + CTA --}}
                            <div style="flex:0 0 200px; min-width:180px; text-align:center; border-left:1px solid var(--pe-line-2); padding-left:18px;">
                                <div class="pe-ring" style="--v:{{ $combo['match'] }}; width:78px; height:78px; margin:0 auto 8px;">
                                    <b style="font-size:16px;">{{ $combo['match'] }}%</b>
                                </div>
                                <div style="font-weight:800; font-size:13px;">Complete Match</div>
                                <p class="pe-muted" style="margin:6px 0 14px;">{{ $combo['note'] }}</p>
                                <div class="pe-muted" style="font-size:12px;">Total Price</div>
                                <div class="pe-price" style="margin-bottom:14px;">${{ number_format($combo['total']) }}</div>
                                <a href="{{ route('client.post-event.checkout') }}" class="pe-btn" style="width:100%;">View Details
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Create MSR for missing services --}}
                <button type="button" class="pe-btn pe-btn-ghost" style="width:100%; border-style:dashed; border-width:1.5px; padding:16px; justify-content:center; margin-top:4px;">
                    <span style="font-weight:800; font-size:16px;">＋</span> Create MSR for Missing Services
                </button>

                {{-- Actions --}}
                <div class="pe-actions" style="margin-top:22px;">
                    <a href="{{ route('client.post-event.customize') }}" class="pe-btn pe-btn-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Back
                    </a>
                    <a href="{{ route('client.post-event.checkout') }}" class="pe-btn">Checkout &amp; Payment
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>

            {{-- Rail --}}
            <aside class="pe-rail">
                @include('client.post-event._rail')

                <div class="pe-rail-card">
                    <h4>Your Requested</h4>
                    @foreach($summary['services'] as $svc)
                        <div class="pe-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ $svc }}
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
