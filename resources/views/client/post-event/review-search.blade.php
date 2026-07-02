@extends('layouts.client')
@section('title', 'Post an Event — Review & Search')
@include('client.post-event._styles')

@section('content')
<div class="pe-wrap">
    @include('client.post-event._wizard')

    <div class="pe-container pe-main">
        <h1 class="pe-h1">Review &amp; Search Preferences</h1>
        <p class="pe-sub">Review your details and set your search preferences before we find the best packages.</p>

        <div class="pe-grid">
            {{-- Main --}}
            <div>
                {{-- Your Event recap --}}
                <div class="pe-card">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                        <h2>Your Event</h2>
                        <a href="{{ route('client.post-event.event-info') }}" class="pe-muted" style="font-weight:700; text-decoration:none; color:var(--pe-orange);">Edit</a>
                    </div>

                    <div class="pe-row" style="gap:10px 22px; margin-bottom:16px;">
                        <div class="pe-rail-row"><span class="k">Venue</span><span class="v">{{ $summary['venue'] }}</span></div>
                        <div class="pe-rail-row"><span class="k">Guests</span><span class="v">{{ $summary['guests'] }}</span></div>
                        <div class="pe-rail-row"><span class="k">Budget</span><span class="v">{{ $summary['budget'] }}</span></div>
                        <div class="pe-rail-row"><span class="k">Style</span><span class="v">{{ $summary['style'] }}</span></div>
                    </div>

                    @foreach($lines as $line)
                        <div class="pe-list-row">
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:700; font-size:14px;">{{ $line['service'] }}</div>
                                <div class="pe-muted">{{ $line['coverage'] }} — {{ $line['budget'] }}</div>
                            </div>
                            <a href="{{ route('client.post-event.service-details') }}" class="pe-muted" style="font-weight:700; text-decoration:none; color:var(--pe-orange);">Edit</a>
                        </div>
                    @endforeach
                </div>

                {{-- Search Preferences --}}
                <div class="pe-card">
                    <h2 style="margin-bottom:14px;">Search Preferences</h2>

                    @foreach($prefs as $pref)
                        <label style="display:flex; align-items:flex-start; gap:10px; padding:9px 0; border-bottom:1px dashed var(--pe-line-2); cursor:pointer;">
                            <input type="checkbox" checked style="width:17px; height:17px; margin-top:1px; accent-color:var(--pe-orange); flex-shrink:0;">
                            <span style="font-size:13.5px; color:var(--pe-ink-2);">{{ $pref }}</span>
                        </label>
                    @endforeach

                    {{-- AI Suggested Additions --}}
                    <div style="margin-top:16px; background:var(--pe-purple-l); border:1px solid #ddd6fe; border-radius:12px; padding:14px;">
                        <h4 style="margin:0 0 4px; font-size:13px; font-weight:800; color:var(--pe-purple);">✨ AI Suggested Additions</h4>
                        <p style="margin:0 0 10px; font-size:12px; color:#5b21b6;">Based on similar events, clients also add these services:</p>
                        <div style="display:flex; flex-wrap:wrap; gap:8px;">
                            @foreach($aiAdditions as $add)
                                <span style="display:inline-flex; align-items:center; gap:6px; background:#fff; border:1px solid #ddd6fe; border-radius:999px; padding:6px 12px; font-size:12.5px; font-weight:700; color:var(--pe-purple); cursor:pointer;">
                                    <span style="font-weight:800;">＋</span> {{ $add }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- AI Tip --}}
                <div class="pe-aitip">
                    <span class="ic">✨</span>
                    <div>
                        <h4>AI Tip</h4>
                        <p>The more accurate your details, the better our AI can match the perfect packages.</p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="pe-actions" style="margin-top:22px;">
                    <a href="{{ route('client.post-event.service-details') }}" class="pe-btn pe-btn-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Back
                    </a>
                    <div style="display:flex; gap:12px;">
                        <button type="button" class="pe-btn pe-btn-ghost">Save for Later</button>
                        <a href="{{ route('client.post-event.results') }}" class="pe-btn">Search for My Packages
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Rail --}}
            <aside class="pe-rail">
                @include('client.post-event._rail')

                <div class="pe-rail-card">
                    <h4>Your Search Will…</h4>
                    @foreach(['Match whole packages that fit your needs','Prioritise verified professionals','Stay within your budget range','Suggest smart add-ons where they help'] as $point)
                        <div class="pe-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ $point }}
                        </div>
                    @endforeach
                </div>

                <div class="pe-rail-card" style="text-align:center;">
                    <h4 style="margin-bottom:14px;">Estimated Match</h4>
                    <div class="pe-ring" style="--v:{{ $match }}; width:96px; height:96px; margin:0 auto;">
                        <b style="font-size:20px;">{{ $match }}%</b>
                    </div>
                    <p class="pe-muted" style="margin:12px 0 0;">Based on your requested services and preferences.</p>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
