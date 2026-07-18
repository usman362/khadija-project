@extends('layouts.client')
@section('title', 'Post an Event — Choose How to Request')
@include('client.post-event._styles')

{{-- Step 0 — Choose Route. Packages are ONE route of five, not the whole
     product (Fix Spec 07.14). Package Search is the synchronous "buy a bundle"
     wizard; SSR/MSR/ESR are postings that end at Publish then take bids on
     Proposals; Direct Offer is a targeted, non-bidding invite. --}}

@push('styles')
<style>
    .rc-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }
    .rc-card { display:flex; flex-direction:column; gap:10px; background:var(--pe-card); border:1px solid var(--pe-line);
        border-radius:16px; padding:22px; text-decoration:none; color:inherit; position:relative;
        transition:border-color .15s, transform .15s, box-shadow .15s; }
    .rc-card:hover { border-color:var(--pe-orange); transform:translateY(-3px); box-shadow:0 12px 30px rgba(15,27,53,.08); }
    .rc-card.soon { opacity:.72; cursor:not-allowed; }
    .rc-card.soon:hover { transform:none; border-color:var(--pe-line); box-shadow:none; }
    .rc-ic { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center;
        background:var(--pe-purple-l); color:var(--pe-purple); flex-shrink:0; }
    .rc-ic svg { width:24px; height:24px; }
    .rc-card h3 { font-size:16px; font-weight:800; margin:0; display:flex; align-items:center; gap:8px; }
    .rc-card p { font-size:13px; color:var(--pe-muted); margin:0; line-height:1.5; }
    .rc-tag { font-size:10px; font-weight:800; letter-spacing:.4px; text-transform:uppercase; padding:3px 8px;
        border-radius:999px; background:var(--pe-line-2); color:var(--pe-muted); }
    .rc-tag.hot { background:#fff7ed; color:var(--pe-orange-d); }
    .rc-tag.soon { background:#eef2ff; color:#4f46e5; }
    .rc-foot { margin-top:auto; font-size:12.5px; font-weight:700; color:var(--pe-orange); display:flex; align-items:center; gap:6px; }
    .rc-note { margin:18px 0 0; font-size:12.5px; color:var(--pe-muted); }
</style>
@endpush

@section('content')
<div class="pe-wrap">
    <div class="pe-container pe-main" style="padding-top:26px; padding-bottom:40px;">
        <h1 class="pe-h1">How do you want to request?</h1>
        <p class="pe-sub">Pick the path that fits your event. You can always start another request later.</p>

        @php
            $routes = [
                [
                    'href'  => route('client.post-event.event-info'),
                    'tag'   => ['Ready-made', 'hot'],
                    'title' => 'Shop Packages',
                    'desc'  => 'Browse fixed service bundles from professionals and book instantly — one contract, one payment.',
                    'cta'   => 'Browse packages',
                    'icon'  => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
                ],
                [
                    'href'  => route('client.multi-service.index'),
                    'tag'   => ['Get bids', 'hot'],
                    'title' => 'Multi-Service Request (MSR)',
                    'desc'  => 'Post several services at once. Each service is its own gig — professionals bid on the ones they provide.',
                    'cta'   => 'Start an MSR',
                    'icon'  => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
                ],
                [
                    'href'  => route('client.direct-offers.create'),
                    'tag'   => ['Targeted', ''],
                    'title' => 'Direct Offer',
                    'desc'  => 'Invite one or more specific professionals you already like. They accept, decline, or reply — no open bidding.',
                    'cta'   => 'Send a Direct Offer',
                    'icon'  => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
                ],
                [
                    'href'  => route('client.multi-service.index'),
                    'tag'   => ['Single service', ''],
                    'title' => 'Single-Service Request (SSR)',
                    'desc'  => 'Post one specific service for professionals to bid on — same quick brief, just pick a single service.',
                    'cta'   => 'Start a request',
                    'icon'  => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
                ],
                [
                    'href'  => null,
                    'tag'   => ['Urgent', 'soon'],
                    'title' => 'Emergency Service Request (ESR)',
                    'desc'  => 'Time-sensitive need within 72 hours — priority notifications to available professionals. Coming soon.',
                    'cta'   => 'Coming soon',
                    'icon'  => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
                ],
            ];
        @endphp

        <div class="rc-grid">
            @foreach($routes as $r)
                @php $soon = $r['href'] === null; @endphp
                <a href="{{ $r['href'] ?? '#' }}" class="rc-card {{ $soon ? 'soon' : '' }}" @if($soon) onclick="return false;" aria-disabled="true" @endif>
                    <div class="rc-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $r['icon'] !!}</svg></div>
                    <h3>{{ $r['title'] }}
                        @if($r['tag'][0])<span class="rc-tag {{ $r['tag'][1] }}">{{ $r['tag'][0] }}</span>@endif
                    </h3>
                    <p>{{ $r['desc'] }}</p>
                    <span class="rc-foot">
                        {{ $r['cta'] }}
                        @unless($soon)<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>@endunless
                    </span>
                </a>
            @endforeach
        </div>

        <p class="rc-note">Not sure which to pick? <b>Shop Packages</b> is the fastest if a pro already offers what you need as a bundle. Choose <b>MSR</b> when you want professionals to compete on price for several services.</p>
    </div>
</div>
@endsection
