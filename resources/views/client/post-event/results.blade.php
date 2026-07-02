@extends('layouts.client')
@section('title', 'Post an Event — Results')
@include('client.post-event._styles')

@section('content')
<div class="pe-wrap">
    @include('client.post-event._wizard')

    <div class="pe-container pe-main">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div>
                <h1 class="pe-h1">Packages for Your Event</h1>
                <p class="pe-sub">We found these package matches for your requested services.</p>
            </div>
            <div style="display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--pe-muted); font-weight:700;">
                View as:
                <span style="display:inline-flex; border:1px solid var(--pe-line); border-radius:9px; overflow:hidden;">
                    <span style="padding:7px 12px; background:var(--pe-orange); color:#fff;">List</span>
                    <span style="padding:7px 12px; background:#fff; color:var(--pe-ink-2); cursor:pointer;">Cards</span>
                </span>
            </div>
        </div>

        <div class="pe-grid">
            {{-- Main results --}}
            <div>
                @foreach($packages as $package)
                    <div class="pe-card" style="display:flex; gap:18px; align-items:stretch; padding:18px;">
                        {{-- Cover --}}
                        <img
                            src="https://images.unsplash.com/{{ $package['img'] }}?w=400&q=80&auto=format&fit=crop"
                            alt="{{ $package['name'] }}"
                            style="width:140px; height:140px; border-radius:12px; object-fit:cover; flex-shrink:0; background:#eee;">

                        {{-- Middle --}}
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <h3 style="font-size:16px; margin:0;">{{ $package['name'] }}</h3>
                                @if($package['badge'])
                                    <span class="pe-badge orange">{{ $package['badge'] }}</span>
                                @endif
                            </div>
                            <div class="pe-muted" style="margin:4px 0 6px;">By {{ $package['vendor'] }}</div>
                            <div style="font-size:13px; font-weight:700; color:var(--pe-ink-2); margin-bottom:10px;">
                                <span style="color:#f59e0b;">★</span> {{ $package['rating'] }}
                                <span class="pe-muted" style="font-weight:600;">({{ $package['reviews'] }} reviews)</span>
                            </div>
                            <div>
                                @foreach($package['services'] as $svc)
                                    <span class="pe-tag">{{ $svc }}</span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Right --}}
                        <div style="width:170px; flex-shrink:0; display:flex; flex-direction:column; align-items:center; text-align:center; border-left:1px solid var(--pe-line-2); padding-left:16px;">
                            <div class="pe-ring" style="--v:{{ $package['match'] }}; width:64px; height:64px;">
                                <b>{{ $package['match'] }}%</b>
                            </div>
                            <div style="font-size:11.5px; font-weight:800; color:var(--pe-green); margin:6px 0 12px;">{{ $package['tier'] }}</div>

                            <div class="pe-price" style="line-height:1.1;">
                                <small style="margin-bottom:2px;">Starting at</small>
                                ${{ number_format($package['price']) }}
                            </div>

                            <a href="{{ route('client.post-event.compare') }}" class="pe-btn" style="width:100%; margin-top:12px;">View Package</a>

                            <div style="display:flex; gap:14px; margin-top:10px; font-size:12px; font-weight:700; color:var(--pe-muted);">
                                <span style="cursor:pointer;">♡ Save</span>
                                <span style="cursor:pointer;">⇄ Compare</span>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Nav --}}
                <div class="pe-actions" style="margin-top:22px;">
                    <a href="{{ route('client.post-event.review-search') }}" class="pe-btn pe-btn-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Back
                    </a>
                    <a href="{{ route('client.post-event.compare') }}" class="pe-btn">Compare Packages
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>

            {{-- Rail --}}
            <aside class="pe-rail">
                @include('client.post-event._rail')

                <div class="pe-rail-card">
                    <h4>Services You Need</h4>
                    @foreach($summary['services'] as $svc)
                        <div class="pe-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ $svc }}
                        </div>
                    @endforeach
                </div>

                <div class="pe-rail-card">
                    <h4>Match Score Guide</h4>
                    @php $guide = [
                        ['90-100%', 'Excellent', '#16a34a', '#dcfce7'],
                        ['75-89%',  'Great',     '#0284c7', '#e0f2fe'],
                        ['60-74%',  'Good',      '#d97706', '#ffedd5'],
                        ['Below 60%', 'Fair',    '#6b7280', '#f1f5f9'],
                    ]; @endphp
                    @foreach($guide as $g)
                        <div style="display:flex; align-items:center; gap:10px; padding:6px 0;">
                            <span style="width:52px; text-align:center; font-size:11px; font-weight:800; color:{{ $g[2] }}; background:{{ $g[3] }}; border-radius:6px; padding:3px 4px;">{{ $g[0] }}</span>
                            <span style="font-size:13px; font-weight:700; color:var(--pe-ink-2);">{{ $g[1] }}</span>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
