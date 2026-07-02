@extends('layouts.client')
@section('title', 'Post an Event — Build Your Event')
@include('client.post-event._styles')

@section('content')
<div class="pe-wrap">
    @include('client.post-event._wizard')

    <div class="pe-container pe-main">
        <h1 class="pe-h1">What does your event need?</h1>
        <p class="pe-sub">Select the services you need and our AI will find the best packages.</p>

        <div class="pe-grid">
            {{-- Main --}}
            <div>
                <div class="pe-card">
                    <div class="pe-svc-grid">
                        @foreach($services as $service)
                            <div class="pe-svc {{ $service['selected'] ? 'on' : '' }}" data-svc="{{ $service['name'] }}" onclick="peToggleSvc(this)">
                                <div class="ic">
                                    @switch($service['icon'])
                                        @case('camera')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                            @break
                                        @case('video')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                                            @break
                                        @case('utensils')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-3 4.5V13c0 1 1 2 2 2h1z"/></svg>
                                            @break
                                        @case('glass')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><path d="M8 22h8"/><path d="M12 15v7"/><path d="M5 3h14l-2 9a5 5 0 0 1-10 0z"/></svg>
                                            @break
                                        @case('flower')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><circle cx="12" cy="12" r="3"/><path d="M12 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M12 21a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M15 12a3 3 0 1 0 6 0 3 3 0 0 0-6 0z"/><path d="M3 12a3 3 0 1 0 6 0 3 3 0 0 0-6 0z"/></svg>
                                            @break
                                        @case('cake')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8"/><path d="M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1"/><path d="M2 21h20"/><path d="M7 8v3"/><path d="M12 8v3"/><path d="M17 8v3"/><path d="M7 4h.01"/><path d="M12 4h.01"/><path d="M17 4h.01"/></svg>
                                            @break
                                        @case('booth')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="12" cy="10" r="3"/><path d="M7 21v-2a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/></svg>
                                            @break
                                        @case('music')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                                            @break
                                        @case('chair')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><path d="M6 3v11"/><path d="M18 3v11"/><path d="M5 14h14l-1 7"/><path d="M6 21l-1-7"/><path d="M6 8h12"/></svg>
                                            @break
                                        @case('users')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                            @break
                                        @case('shield')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                            @break
                                        @case('clipboard')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                            @break
                                        @default
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><polygon points="12 2 15 9 22 9 16 14 18 21 12 17 6 21 8 14 2 9 9 9 12 2"/></svg>
                                    @endswitch
                                </div>
                                <div class="nm">{{ $service['name'] }}</div>
                                <span class="chk">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" width="11" height="11"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="pe-btn-ghost" style="width:100%; margin-top:14px; border-style:dashed; padding:13px;">
                        ＋ Add a Custom Service
                    </button>
                </div>

                <div class="pe-aitip" style="margin-bottom:18px;">
                    <span class="ic">✨</span>
                    <div>
                        <h4>AI Tip</h4>
                        <p>The more services you add, the more accurate our AI matches will be.</p>
                    </div>
                </div>

                <div class="pe-actions">
                    <a href="{{ route('client.post-event.event-info') }}" class="pe-btn pe-btn-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Back
                    </a>
                    <a href="{{ route('client.post-event.service-details') }}" class="pe-btn">Next: Service Details
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>

            {{-- Right rail --}}
            <aside class="pe-rail">
                @include('client.post-event._rail')

                <div class="pe-rail-card">
                    <h4>Your Selected Services</h4>
                    @foreach($services as $service)
                        @if($service['selected'])
                            <div class="pe-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ $service['name'] }}
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="pe-rail-card pe-rail-why">
                    <h4 style="color:var(--pe-purple);">✨ AI Suggestion</h4>
                    <p class="pe-muted" style="margin:-6px 0 12px;">Based on similar events:</p>
                    @foreach($aiSuggested as $suggest)
                        <div class="pe-rail-row" style="border-bottom:none; padding:5px 0;">
                            <span>{{ $suggest }}</span>
                            <span class="pe-badge pro" style="margin-left:auto; cursor:pointer;">＋ Add</span>
                        </div>
                    @endforeach
                    <button type="button" class="pe-btn pe-btn-purple" style="width:100%; margin-top:10px; padding:10px;">Add All</button>
                </div>
            </aside>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function peToggleSvc(el) {
        el.classList.toggle("on");
    }
</script>
@endpush
@endsection
