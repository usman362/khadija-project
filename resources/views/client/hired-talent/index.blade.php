@extends('layouts.client')

@section('title', 'Hired Talent')
@section('page-title', 'Hired Talent')

@push('styles')
<style>
    /* ── The banner his mockup opens with ───────────────────── */
    .ht-banner { display: flex; align-items: center; gap: 16px; padding: 18px 22px; border-radius: 16px;
        background: linear-gradient(90deg, rgba(249,115,22,.16), rgba(249,115,22,.05)); margin-bottom: 16px; }
    [data-theme="dark"] .ht-banner { background: linear-gradient(90deg, rgba(249,115,22,.18), rgba(249,115,22,.04)); }
    .ht-banner > svg { width: 42px; height: 42px; color: var(--accent-orange, #ea580c); flex: none; }
    .ht-banner h1 { font-size: 27px; font-weight: 800; color: var(--text-primary); margin: 0; line-height: 1.15; }
    .ht-banner p { font-size: 13.5px; color: var(--text-secondary); margin: 3px 0 0; }

    .ht { display: grid; grid-template-columns: 340px minmax(0, 1fr); gap: 16px; align-items: start; }
    .ht-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; }

    /* ── Left ───────────────────────────────────────────────── */
    .ht-filters { padding: 16px 16px 12px; }
    .ht-ltitle { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 0 0 11px; }

    .ht-srow { display: flex; gap: 8px; margin-bottom: 11px; }
    .ht-search { position: relative; flex: 1; min-width: 0; }
    .ht-search svg { position: absolute; left: 11px; top: 11px; width: 15px; height: 15px; color: var(--text-muted); }
    .ht-search input { width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
        padding: 9px 11px 9px 33px; font: inherit; font-size: 13px; color: var(--text-primary); background: var(--bg-card); }
    .ht-search input:focus { outline: none; border-color: var(--accent-orange, #ea580c); }
    .ht-funnel { width: 38px; flex: none; display: flex; align-items: center; justify-content: center;
        border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-muted); text-decoration: none; }
    .ht-funnel.is-on { border-color: var(--accent-orange, #ea580c); color: var(--accent-orange, #ea580c); background: rgba(249,115,22,.08); }
    .ht-funnel svg { width: 15px; height: 15px; }

    .ht-pills { display: flex; gap: 7px; }
    .ht-pill { flex: 1; text-align: center; font-size: 12.5px; font-weight: 700; padding: 8px 6px; border-radius: 10px;
        border: 1px solid var(--border-color); color: var(--text-secondary); text-decoration: none; white-space: nowrap; }
    .ht-pill.is-on { border-color: var(--accent-orange, #ea580c); background: rgba(249,115,22,.1); color: var(--brand-text, #c2410c); }
    .ht-pill.is-empty { opacity: .5; }
    .ht-kinds { display: flex; gap: 7px; margin-bottom: 7px; }

    .ht-list { max-height: 660px; overflow-y: auto; border-top: 1px solid var(--border-color); }
    /* Saved people, moved here from My Professionals. */
    .ht-saved { display: flex; align-items: center; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--border-color); }
    .ht-saved-av { width: 40px; height: 40px; border-radius: 11px; object-fit: cover; flex: none; }
    .ht-saved-who { min-width: 0; flex: 1; }
    .ht-saved-who b { display: block; font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .ht-saved-who span { display: block; font-size: 11.5px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ht-saved-acts { display: flex; align-items: center; gap: 7px; flex: none; }
    .ht-saved-btn { font-size: 11.5px; font-weight: 700; padding: 7px 11px; border-radius: 9px; cursor: pointer;
        border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); text-decoration: none; }
    .ht-saved-btn.is-go { border-color: var(--accent-orange, #ea580c); background: var(--accent-orange, #ea580c); color: #fff; }
    @media (max-width: 560px) { .ht-saved { flex-wrap: wrap; } .ht-saved-acts { width: 100%; } }


    .ht-ev { display: flex; align-items: center; gap: 10px; width: 100%; text-align: left; border: 0;
        background: none; cursor: pointer; font: inherit; padding: 12px 16px 8px; }
    .ht-ev-mid { flex: 1; min-width: 0; }
    .ht-ev-d { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .ht-ev-d svg { width: 15px; height: 15px; color: var(--accent-orange, #ea580c); flex: none; }
    .ht-ev-t { display: block; font-size: 11.5px; color: var(--text-muted); margin: 1px 0 0 23px; }
    .ht-ev-n { font-size: 11.5px; font-weight: 700; color: var(--text-muted); white-space: nowrap; }
    .ht-ev-chev { width: 15px; height: 15px; color: var(--text-muted); flex: none; transition: transform .15s; }
    .ht-ev[aria-expanded="false"] .ht-ev-chev { transform: rotate(180deg); }
    .ht-ev[aria-expanded="false"] + .ht-ev-rows { display: none; }
    .ht-ev-rows { padding: 0 10px 8px; }

    .ht-row { display: flex; align-items: center; gap: 11px; width: 100%; text-align: left;
        padding: 9px 10px; border: 1px solid transparent; border-radius: 12px; background: none; cursor: pointer; font: inherit; }
    .ht-row:hover { background: var(--bg-card-hover, #f1f5f9); }
    .ht-row.is-on { background: rgba(249,115,22,.07); border-color: rgba(249,115,22,.3); }
    .ht-av { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; flex: none; }
    .ht-row-mid { min-width: 0; flex: 1; }
    .ht-row-n { display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ht-row-s { display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--text-muted); white-space: nowrap; }
    .ht-dot { width: 7px; height: 7px; border-radius: 50%; flex: none; }
    .ht-dot.ok { background: #10b981; }
    .ht-dot.warn { background: #f59e0b; }
    .ht-row-time { display: block; font-size: 11.5px; color: var(--text-muted); white-space: nowrap; }
    .ht-row-chev { width: 14px; height: 14px; color: var(--text-muted); flex: none; }

    .ht-chip { font-size: 10.5px; font-weight: 800; padding: 4px 10px; border-radius: 999px; white-space: nowrap; }
    .ht-chip.ok   { background: rgba(16,185,129,.12); color: var(--ok-text, #047857); }
    .ht-chip.wait { background: rgba(249,115,22,.12); color: var(--brand-text, #c2410c); }
    .ht-chip.done { background: rgba(37,99,235,.10); color: var(--info-text, #1d4ed8); }
    .ht-chip.off  { background: var(--bg-card-hover, #f1f5f9); color: var(--text-muted); }

    /* ── Right ──────────────────────────────────────────────── */
    .ht-detail-h { display: flex; align-items: flex-start; gap: 18px; padding: 22px 22px 18px; }
    .ht-avwrap { position: relative; flex: none; }
    .ht-detail-av { width: 84px; height: 84px; border-radius: 50%; object-fit: cover; display: block; }
    .ht-avtick { position: absolute; right: 0; bottom: 0; width: 26px; height: 26px; border-radius: 50%;
        background: #10b981; border: 3px solid var(--bg-card); display: flex; align-items: center; justify-content: center; }
    .ht-avtick svg { width: 13px; height: 13px; color: #fff; }
    .ht-detail-n { font-size: 23px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .ht-ver { display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 800;
        color: var(--info-text, #1d4ed8); background: rgba(37,99,235,.1); padding: 4px 10px; border-radius: 999px;
        vertical-align: middle; margin-left: 8px; }
    .ht-ver svg { width: 13px; height: 13px; }
    .ht-detail-sub { font-size: 14px; color: var(--text-secondary); margin: 3px 0 0; }
    .ht-detail-meta { display: flex; flex-wrap: wrap; gap: 16px; font-size: 13px; color: var(--text-muted); margin: 7px 0 0; }
    .ht-detail-meta > span { display: inline-flex; align-items: center; gap: 5px; }
    .ht-detail-meta svg { width: 14px; height: 14px; }
    .ht-detail-sp { flex: 1; }

    .ht-actions { position: relative; display: flex; align-items: center; gap: 8px; flex: none; }
    .ht-review { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 800;
        padding: 8px 13px; border-radius: 10px; text-decoration: none; white-space: nowrap;
        background: var(--accent-orange, #ea580c); color: #fff; }
    .ht-review svg { width: 13px; height: 13px; }

    .ht-more { width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--border-color);
        background: var(--bg-card); color: var(--text-secondary); cursor: pointer; font-size: 17px; line-height: 1;
        display: flex; align-items: center; justify-content: center; }
    .ht-more:hover { border-color: var(--accent-orange, #ea580c); }
    .ht-menu { position: absolute; right: 0; top: 46px; z-index: 30; min-width: 212px; padding: 6px;
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px;
        box-shadow: 0 18px 40px -18px rgba(15,27,53,.4); display: none; }
    .ht-menu.is-open { display: block; }
    .ht-menu a { display: block; padding: 9px 12px; border-radius: 8px; font-size: 13px;
        color: var(--text-primary); text-decoration: none; white-space: nowrap; }
    .ht-menu a:hover { background: var(--bg-card-hover, #f1f5f9); }

    .ht-facts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
        margin: 0 22px 18px; border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; }
    .ht-fact { display: flex; gap: 12px; padding: 16px 18px; border-right: 1px solid var(--border-color); }
    .ht-fact:last-child { border-right: 0; }
    .ht-fact-ico { width: 34px; height: 34px; border-radius: 10px; flex: none; display: flex;
        align-items: center; justify-content: center; background: rgba(249,115,22,.1); color: var(--accent-orange, #ea580c); }
    .ht-fact-ico svg { width: 17px; height: 17px; }
    .ht-fact-l { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; color: var(--text-muted); }
    .ht-fact-v { font-size: 13.5px; font-weight: 600; color: var(--text-primary); margin-top: 3px; line-height: 1.45; }
    .ht-fact-v .dim { color: var(--text-muted); font-weight: 500; }

    .ht-two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin: 0 22px 20px; }
    .ht-panel { border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 18px; }
    .ht-panel-h { display: flex; align-items: center; gap: 11px; margin-bottom: 12px; }
    .ht-panel-ico { width: 34px; height: 34px; border-radius: 10px; flex: none; display: flex;
        align-items: center; justify-content: center; }
    .ht-panel-ico svg { width: 17px; height: 17px; }
    .ht-panel-t { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .ht-panel-sp { flex: 1; }
    .ht-panel-n { font-size: 12.5px; color: var(--text-muted); white-space: nowrap; }

    .ht-bar { height: 9px; border-radius: 999px; background: var(--bg-card-hover, #e5e7eb); overflow: hidden; }
    .ht-bar i { display: block; height: 100%; background: #10b981; border-radius: 999px; }
    .ht-next { font-size: 12.5px; color: var(--text-secondary); margin: 9px 0 0; line-height: 1.5; }
    .ht-next b { color: var(--text-primary); }

    .ht-money-row { display: flex; gap: 22px; flex-wrap: wrap; }
    .ht-money-n { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .ht-money-n.due { color: var(--accent-orange, #ea580c); }
    .ht-money-k { font-size: 11.5px; color: var(--text-muted); }

    .ht-none { padding: 40px 24px; text-align: center; }
    .ht-none p { font-size: 13.5px; color: var(--text-muted); margin: 0 0 4px; line-height: 1.6; }
    .ht-none b { color: var(--text-primary); }

    .ht-detail { display: none; }
    .ht-detail.is-on { display: block; }

    @media (max-width: 1100px) {
        .ht { grid-template-columns: 1fr; }
        .ht-list { max-height: none; }
        .ht-facts, .ht-two { grid-template-columns: 1fr; }
        .ht-fact { border-right: 0; border-bottom: 1px solid var(--border-color); }
    }
    /* Sir Peter, 12 October: "Hired Talent will be the webpage with tabs for
       Professionals and Influencers, no longer as subpages." Everyone stays as
       the landing tab, because most clients hire both and his own page groups
       them by event, not by role. */
    .ht-tabs { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
    .ht-tab { display: inline-flex; align-items: center; gap: 9px; padding: 10px 15px; border-radius: 12px;
        border: 1px solid var(--border-color); background: var(--bg-card); text-decoration: none; }
    .ht-tab b { display: block; font-size: 13.5px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
    .ht-tab span { font-size: 11.5px; color: var(--text-muted); }
    .ht-tab.is-on { border-color: var(--accent-orange, #ea580c); background: rgba(249,115,22,.08); }
    .ht-tab-ico { width: 30px; height: 30px; border-radius: 9px; flex: none; display: flex; align-items: center; justify-content: center;
        background: rgba(249,115,22,.12); color: #ea580c; }
    .ht-tab-ico svg { width: 16px; height: 16px; }
    @media (max-width: 560px) { .ht-tab span { display: none; } }
</style>
@endpush

@section('content')
@php
    $money = fn ($n) => $n === null ? null : '$' . number_format((float) $n, 2);
    $link = fn (array $over) => route('client.hired-talent.index', array_filter(
        array_merge(['who' => $group, 'show' => $state, 'q' => $q], $over),
        fn ($v) => $v !== null && $v !== '' && $v !== 'all',
    ));
    $heading = match ($group) {
        'influencers'   => 'Hired Influencers',
        'professionals' => 'Hired Professionals',
        default         => 'Hired Talent',
    };
@endphp

<div class="ht-banner">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </svg>
    <div>
        <h1>{{ $heading }}</h1>
        <p>View and manage everyone you have booked, by event, with where the money stands.</p>
    </div>
</div>

{{-- Tabs rather than subpages, per 12 October. The pills that used to carry
     this still sit in the filter row with their counts; these are the same
     three answers raised to the top of the page where he drew them. --}}
<div class="ht-tabs">
    @php
        $htTabs = [
            'all'           => ['Everyone',      'Both roles, by event', '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/>'],
            'professionals' => ['Professionals', $kinds['professionals'] . ' hired', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/>'],
            'influencers'   => ['Influencers',   $kinds['influencers'] . ' hired', '<path d="M12 2 15 9l7 .6-5.3 4.6L18.3 21 12 17.3 5.7 21l1.6-6.8L2 9.6 9 9z"/>'],
            // Moved here when My Professionals was removed. These are people
            // you saved and have NOT hired, so they appear nowhere else.
            'saved'         => ['Saved',         $savedCount . ' saved', '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>'],
        ];
    @endphp
    @foreach($htTabs as $key => [$label, $blurb, $icon])
        <a class="ht-tab {{ $group === $key ? 'is-on' : '' }}" href="{{ route('client.hired-talent.index', $key === 'all' ? [] : ['who' => $key]) }}">
            <span class="ht-tab-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
            </span>
            <span><b>{{ $label }}</b><span>{{ $blurb }}</span></span>
        </a>
    @endforeach
</div>

<div class="ht">
    {{-- ── Who you hired ─────────────────────────────── --}}
    <div class="ht-card">
        <div class="ht-filters">
            <h2 class="ht-ltitle">{{ ['influencers' => 'Hired Influencers', 'professionals' => 'Hired Professionals', 'saved' => 'Saved for later'][$group] ?? 'Hired Talent' }}</h2>

            <form method="GET" class="ht-srow">
                <label class="ht-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Search a name, event or service…" autocomplete="off">
                </label>
                @if($group !== 'all')<input type="hidden" name="who" value="{{ $group }}">@endif
                @if($state !== 'all')<input type="hidden" name="show" value="{{ $state }}">@endif
                {{-- The funnel clears rather than opening a second panel: every
                     filter this page has is already on screen. --}}
                <a class="ht-funnel {{ $q !== '' || $state !== 'all' || $group !== 'all' ? 'is-on' : '' }}"
                   href="{{ route('client.hired-talent.index') }}" title="Clear filters" aria-label="Clear filters">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                </a>
            </form>

            {{-- The role pills that sat here were the same three answers as the
                 tabs above, with the same counts, one on top of the other.
                 Tabs are what Sir Peter asked for, so these went. --}}
            <div class="ht-pills">
                <a href="{{ $link(['show' => 'all']) }}" class="ht-pill {{ $state === 'all' ? 'is-on' : '' }}">All ({{ $counts['all'] }})</a>
                <a href="{{ $link(['show' => 'active']) }}" class="ht-pill {{ $state === 'active' ? 'is-on' : '' }}">Active ({{ $counts['active'] }})</a>
                <a href="{{ $link(['show' => 'past']) }}" class="ht-pill {{ $state === 'past' ? 'is-on' : '' }}">Past ({{ $counts['past'] }})</a>
            </div>
        </div>

        <div class="ht-list">
            @if($group === 'saved')
                {{-- Saved people have no event to group under: that is the
                     whole point of them. A flat list, with the way to actually
                     ask one of them for something. --}}
                @forelse($saved as $pro)
                    <div class="ht-saved">
                        <img class="ht-saved-av" src="{{ $pro->avatar_url }}" alt="">
                        <div class="ht-saved-who">
                            {{-- The dot and its words both come from
                                 App\Support\Presence, so "Online now" cannot
                                 disagree with the colour beside it. Moved here
                                 with the rest of My Professionals. --}}
                            <b><x-presence-dot :user="$pro" inline />{{ $pro->name }}</b>
                            <span>{{ $pro->profile?->headline ?? 'No headline yet' }}</span>
                            <span>
                                @if($pro->reviews_count)★ {{ number_format((float) $pro->reviews_avg, 1) }} ({{ $pro->reviews_count }})@endif
                                {{ $pro->serviceCategories->pluck('name')->take(2)->implode(' · ') }}
                                @if(\App\Support\Presence::label($pro)) · {{ \App\Support\Presence::label($pro) }}@endif
                            </span>
                        </div>
                        <div class="ht-saved-acts">
                            <a class="ht-saved-btn is-go" href="{{ route('client.direct-offers.create', ['professional' => $pro->id]) }}">Request a quote</a>
                            <form method="POST" action="{{ route('client.hired-talent.unsave', $pro) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="ht-saved-btn">Remove</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="ht-empty" style="padding:34px 18px;">
                        <b>Nothing saved yet</b>
                        <p>The heart on a professional's card saves them here, so you can come back without searching again.</p>
                        <a class="ht-saved-btn is-go" href="{{ route('public.browse') }}">Browse professionals</a>
                    </div>
                @endforelse
            @else
            @forelse($groups as $g)
                <button type="button" class="ht-ev" data-ht-group aria-expanded="true">
                    <span class="ht-ev-mid">
                        <span class="ht-ev-d">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            {{ $g['event']['at']?->format('F j, Y') ?? 'Date not set' }}
                        </span>
                        <span class="ht-ev-t">{{ $g['event']['title'] ?? 'Untitled event' }}</span>
                    </span>
                    <span class="ht-ev-n">{{ count($g['rows']) }} {{ \Illuminate\Support\Str::plural($group === 'influencers' ? 'influencer' : 'provider', count($g['rows'])) }}</span>
                    <svg class="ht-ev-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                </button>
                <div class="ht-ev-rows">
                    @foreach($g['rows'] as $r)
                        <button type="button" class="ht-row" data-ht-row="{{ $r['id'] }}">
                            <img class="ht-av" src="{{ $r['supplier']['avatar'] }}" alt="">
                            <span class="ht-row-mid">
                                <span class="ht-row-n">{{ $r['supplier']['name'] }}</span>
                                <span class="ht-row-s">
                                    {{-- "On track" is the agreement's own progress, not a mood. --}}
                                    @if($r['readiness'])
                                        <span class="ht-dot {{ $r['readiness']['ok'] ? 'ok' : 'warn' }}"></span>
                                        {{ $r['readiness']['ok'] ? 'On track' : 'Action needed' }}
                                    @else
                                        {{ $r['service'] ?? 'Service not recorded' }}
                                    @endif
                                </span>
                                @if($r['event']['at'])
                                    <span class="ht-row-time">{{ $r['event']['at']->format('g:i A') }}@if($r['event']['ends']) &ndash; {{ $r['event']['ends']->format('g:i A') }}@endif</span>
                                @endif
                            </span>
                            <span class="ht-chip {{ $r['tone'] }}">{{ $r['state'] }}</span>
                            <svg class="ht-row-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    @endforeach
                </div>
            @empty
                <div class="ht-none">
                    @if($counts['all'] === 0)
                        <p><b>You have not hired anyone yet.</b></p>
                        <p>Once you accept a proposal, whoever you booked appears here with their event and payments.</p>
                    @elseif($group === 'influencers')
                        <p><b>No influencers yet.</b></p>
                        <p>Influencers cannot be hired through the site yet. When that opens, the ones you book will show up here beside your professionals.</p>
                    @elseif($q !== '')
                        <p><b>Nothing matches that.</b></p>
                        <p>Try a different search, or <a href="{{ route('client.hired-talent.index') }}" style="font-weight:700;">show everyone</a>.</p>
                    @elseif($state === 'active')
                        <p><b>Nothing on right now.</b></p>
                        <p>All {{ $counts['past'] }} of your bookings are finished. <a href="{{ $link(['show' => 'past']) }}" style="font-weight:700;">See the past ones</a>.</p>
                    @elseif($state === 'past')
                        <p><b>Nothing finished yet.</b></p>
                        <p>Bookings move here once the work is done or cancelled.</p>
                    @else
                        <p><b>Nothing matches that.</b></p>
                        <p>Try <a href="{{ route('client.hired-talent.index') }}" style="font-weight:700;">showing everyone</a>.</p>
                    @endif
                </div>
            @endforelse
            @endif
        </div>
    </div>

    {{-- ── The one you picked ────────────────────────── --}}
    <div class="ht-card" style="min-height:260px;">
        @if($shown === 0)
            <div class="ht-none"><p>Nothing to show on this side yet.</p></div>
        @else
            <div class="ht-none" data-ht-empty>
                <p>Pick somebody on the left to see their event, their booking and their payments.</p>
            </div>
        @endif

        @foreach($groups as $g)
            @foreach($g['rows'] as $r)
                <div class="ht-detail" data-ht-detail="{{ $r['id'] }}">
                    <div class="ht-detail-h">
                        <span class="ht-avwrap">
                            <img class="ht-detail-av" src="{{ $r['supplier']['avatar'] }}" alt="">
                            @if(in_array($r['raw_state'], ['confirmed', 'completed'], true))
                                {{-- The tick means the booking is settled, which we
                                     know. It is not a verification mark. --}}
                                <span class="ht-avtick" title="{{ $r['state'] }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                            @endif
                        </span>
                        <div>
                            <h2 class="ht-detail-n">
                                {{ $r['supplier']['name'] }}
                                @if($r['supplier']['verified'])
                                    <span class="ht-ver">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        Verified {{ ucfirst($r['supplier']['noun']) }}
                                    </span>
                                @endif
                            </h2>
                            <p class="ht-detail-sub">{{ $r['service'] ?? $r['supplier']['headline'] ?? 'Service not recorded' }}</p>
                            <p class="ht-detail-meta">
                                @if($r['supplier']['rating'])
                                    <span style="color:#f59e0b;">&#9733;
                                        <span style="color:var(--text-primary);font-weight:700;">{{ $r['supplier']['rating'] }}</span>
                                        <span style="color:var(--text-muted);">({{ $r['supplier']['reviews'] }} {{ \Illuminate\Support\Str::plural('review', $r['supplier']['reviews']) }})</span>
                                    </span>
                                @endif
                                @if($r['supplier']['where'])
                                    <span>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        {{ $r['supplier']['where'] }}
                                    </span>
                                @endif
                            </p>
                        </div>
                        <span class="ht-detail-sp"></span>
                        <div class="ht-actions">
                            {{-- Khadijah, 30 Sep: "there should be button to add
                                 review, then that review will appear on Reviews
                                 page." It lived on My Professionals, which came
                                 out on 21 October on her own instruction, so it
                                 moves here with everything else that page held.

                                 Only on a finished booking this client has not
                                 reviewed: a review belongs to the job, so the
                                 same professional can be owed one on one event
                                 and not on another. --}}
                            @if($r['to_review'] ?? false)
                                <a class="ht-review" href="{{ route('client.reviews.index', ['booking' => $r['id']]) }}">
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2 15 9l7 .6-5.3 4.6L18.3 21 12 17.3 5.7 21l1.6-6.8L2 9.6 9 9z"/></svg>
                                    Add review
                                </a>
                            @endif
                            <span class="ht-chip {{ $r['tone'] }}" style="font-size:13px;padding:9px 14px;">{{ $r['state'] }}</span>
                            <button type="button" class="ht-more" data-ht-more aria-haspopup="true" aria-expanded="false" aria-label="More actions">&#8943;</button>
                            {{-- Release Milestone Payment is on his menu and is not
                                 here: nothing in the system releases one. --}}
                            <div class="ht-menu" data-ht-menu>
                                @if($r['urls']['event'])<a href="{{ $r['urls']['event'] }}">View event details</a>@endif
                                @if($r['urls']['profile'])<a href="{{ $r['urls']['profile'] }}">View profile</a>@endif
                                @if($r['urls']['again'])<a href="{{ $r['urls']['again'] }}">Book again</a>@endif
                                <a href="{{ $r['urls']['payments'] }}">Payments</a>
                                <a href="{{ route('client.chat.index') }}">Message {{ $r['supplier']['noun'] }}</a>
                            </div>
                        </div>
                    </div>

                    <div class="ht-facts">
                        <div class="ht-fact">
                            <span class="ht-fact-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                            <div>
                                <div class="ht-fact-l">Event</div>
                                <div class="ht-fact-v">
                                    {{ $r['event']['title'] ?? 'Untitled event' }}
                                    @if($r['event']['at'])<br><span class="dim">{{ $r['event']['at']->format('l, F j, Y') }}</span>@endif
                                </div>
                            </div>
                        </div>
                        <div class="ht-fact">
                            <span class="ht-fact-ico" style="background:rgba(37,99,235,.1);color:#1d4ed8;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                            <div>
                                <div class="ht-fact-l">Service time</div>
                                <div class="ht-fact-v">
                                    @if($r['event']['at'])
                                        {{ $r['event']['at']->format('g:i A') }}@if($r['event']['ends']) &ndash; {{ $r['event']['ends']->format('g:i A') }}@endif
                                        @if($r['event']['ends'])
                                            @php $hrs = max(1, $r['event']['at']->diffInHours($r['event']['ends'])); @endphp
                                            <br><span class="dim">({{ $hrs }} {{ \Illuminate\Support\Str::plural('hour', $hrs) }})</span>
                                        @endif
                                    @else
                                        <span class="dim">Not set</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="ht-fact">
                            <span class="ht-fact-ico" style="background:rgba(109,40,217,.1);color:#6d28d9;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                            <div>
                                <div class="ht-fact-l">Event location</div>
                                <div class="ht-fact-v">{!! $r['event']['where'] ?: '<span class="dim">Not recorded</span>' !!}</div>
                            </div>
                        </div>
                    </div>

                    <div class="ht-two">
                        <div class="ht-panel">
                            <div class="ht-panel-h">
                                <span class="ht-panel-ico" style="background:rgba(16,185,129,.12);color:#047857;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
                                <span class="ht-panel-t">Readiness</span>
                                <span class="ht-panel-sp"></span>
                                @if($r['readiness'])
                                    <span class="ht-panel-n">{{ $r['readiness']['done'] }} of {{ $r['readiness']['total'] }} complete</span>
                                @endif
                            </div>
                            @if($r['readiness'])
                                {{-- The agreement's own seven steps, each with the
                                     date it was done. Not a bar drawn from nothing. --}}
                                <div class="ht-bar"><i style="width:{{ round($r['readiness']['done'] / max($r['readiness']['total'], 1) * 100) }}%;"></i></div>
                                <p class="ht-next">
                                    @if($r['readiness']['next'])
                                        Next: <b>{{ $r['readiness']['next'] }}</b>
                                    @else
                                        <b>Every step is done.</b>
                                    @endif
                                </p>
                            @else
                                <p class="ht-next">This booking was made outside the agreement steps, so there is nothing to track here.</p>
                            @endif
                        </div>

                        <div class="ht-panel">
                            <div class="ht-panel-h">
                                <span class="ht-panel-ico" style="background:rgba(249,115,22,.12);color:#ea580c;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.66 3.58 3 8 3s8-1.34 8-3V6"/><path d="M4 12v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/></svg></span>
                                <span class="ht-panel-t">Payment</span>
                            </div>
                            @if($r['money'])
                                <div class="ht-money-row">
                                    <div>
                                        <div class="ht-money-n">{{ $money($r['money']['total']) ?? '—' }}</div>
                                        <div class="ht-money-k">Agreed</div>
                                    </div>
                                    <div>
                                        <div class="ht-money-n">{{ $money($r['money']['paid']) }}</div>
                                        <div class="ht-money-k">Paid</div>
                                    </div>
                                    <div>
                                        <div class="ht-money-n {{ $r['money']['balance'] > 0 ? 'due' : '' }}">{{ $money($r['money']['balance']) }}</div>
                                        <div class="ht-money-k">{{ $r['money']['balance'] > 0 ? 'Due' : 'Nothing owed' }}@if($r['money']['due'] && $r['money']['balance'] > 0) by {{ $r['money']['due']->format('M j') }}@endif</div>
                                    </div>
                                </div>
                            @else
                                <p class="ht-next">No payment has gone through the site for this booking.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var rows = document.querySelectorAll('[data-ht-row]');
    if (!rows.length) return;

    var empty = document.querySelector('[data-ht-empty]');

    function closeMenus(except) {
        document.querySelectorAll('[data-ht-menu]').forEach(function (m) {
            if (m !== except) {
                m.classList.remove('is-open');
                var b = m.parentElement.querySelector('[data-ht-more]');
                if (b) b.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function show(id) {
        document.querySelectorAll('[data-ht-detail]').forEach(function (d) {
            d.classList.toggle('is-on', d.getAttribute('data-ht-detail') === id);
        });
        rows.forEach(function (r) {
            r.classList.toggle('is-on', r.getAttribute('data-ht-row') === id);
        });
        closeMenus();
        if (empty) empty.style.display = 'none';
    }

    rows.forEach(function (r) {
        r.addEventListener('click', function () { show(r.getAttribute('data-ht-row')); });
    });

    // An event folds away, for a client with a lot of them.
    document.querySelectorAll('[data-ht-group]').forEach(function (g) {
        g.addEventListener('click', function () {
            g.setAttribute('aria-expanded', g.getAttribute('aria-expanded') === 'false' ? 'true' : 'false');
        });
    });

    document.querySelectorAll('[data-ht-more]').forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.stopPropagation();
            var menu = b.parentElement.querySelector('[data-ht-menu]');
            var open = menu.classList.contains('is-open');
            closeMenus(menu);
            menu.classList.toggle('is-open', !open);
            b.setAttribute('aria-expanded', !open ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function () { closeMenus(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenus(); });

    // The first one, so the right side is never a blank panel on arrival.
    show(rows[0].getAttribute('data-ht-row'));
})();
</script>
@endpush
