@extends('layouts.client')
@section('title', 'Post an Event — Service Details')
@include('client.post-event._styles')

@section('content')
<div class="pe-wrap">
    @include('client.post-event._wizard')

    <div class="pe-container pe-main">
        <h1 class="pe-h1">Service Details</h1>
        <p class="pe-sub">Tell us about each service so our AI can find the best packages.</p>

        <div class="pe-grid">
            {{-- Main --}}
            <div>
                <div class="pe-aitip" style="margin-bottom:18px;">
                    <span class="ic">✨</span>
                    <div>
                        <h4>AI Tip</h4>
                        <p>The more details you provide, the more accurate and personalised your package matches will be.</p>
                    </div>
                </div>

                <div class="pe-card">
                    @foreach($lines as $line)
                        <div class="pe-list-row" style="align-items:flex-start; flex-wrap:wrap;">
                            <div style="flex:1 1 160px; min-width:150px;">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:2px;">
                                    <span style="font-size:14px; font-weight:800; color:var(--pe-ink);">{{ $line['service'] }}</span>
                                    @if($line['required'])
                                        <span class="pe-badge req">Required</span>
                                    @endif
                                </div>
                            </div>
                            <div style="min-width:90px;">
                                <div class="pe-label" style="margin-bottom:2px;">Coverage</div>
                                <div style="font-size:13px; font-weight:700; color:var(--pe-ink);">{{ $line['coverage'] }}</div>
                            </div>
                            <div style="min-width:100px;">
                                <div class="pe-label" style="margin-bottom:2px;">Package</div>
                                <div style="font-size:13px; font-weight:700; color:var(--pe-ink);">{{ $line['package'] }}</div>
                            </div>
                            <div style="min-width:120px;">
                                <div class="pe-label" style="margin-bottom:2px;">Budget</div>
                                <div style="font-size:13px; font-weight:700; color:var(--pe-ink);">{{ $line['budget'] }}</div>
                            </div>
                            <div style="margin-left:auto;">
                                <button type="button" class="pe-btn-ghost" style="padding:9px 14px; font-size:13px; font-weight:700; border-radius:10px;">✎ Edit Details</button>
                            </div>
                        </div>
                    @endforeach

                    <button type="button" class="pe-btn-ghost" style="width:100%; margin-top:16px; border-style:dashed; padding:13px;">
                        ＋ Add Another Service
                    </button>
                </div>

                <div class="pe-actions">
                    <a href="{{ route('client.post-event.build') }}" class="pe-btn pe-btn-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Back
                    </a>
                    <a href="{{ route('client.post-event.review-search') }}" class="pe-btn">Next: Review &amp; Search
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>

            {{-- Right rail --}}
            <aside class="pe-rail">
                @include('client.post-event._rail')

                <div class="pe-rail-card">
                    <h4>Your Selected Services</h4>
                    @foreach($lines as $line)
                        <div class="pe-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ $line['service'] }}
                        </div>
                    @endforeach
                </div>

                <div class="pe-rail-card">
                    <h4>AI Service Check</h4>
                    <div style="display:flex; align-items:center; gap:14px;">
                        <div class="pe-ring" style="--v:{{ $match }}"><b>{{ $match }}%</b></div>
                        <div>
                            <div style="font-size:13.5px; font-weight:800; color:var(--pe-green);">High Match Potential</div>
                            <p class="pe-muted" style="margin:4px 0 0;">Great! You've added enough detail for our AI to find strong matches.</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
