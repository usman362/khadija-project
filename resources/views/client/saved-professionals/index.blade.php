@extends('layouts.client')

@section('title', 'My Professionals')
@section('page-title', 'My Professionals')
@section('page-subtitle', 'Professionals you hired or saved.')

@push('styles')
<style>
    .mp-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
    .mp-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px 18px; display: flex; gap: 14px; align-items: center; }
    .mp-stat-ico { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex: none; }
    .mp-stat-ico svg { width: 20px; height: 20px; }
    .mp-stat b { display: block; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .mp-stat span { font-size: 12.5px; color: var(--text-muted); }

    .mp-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .mp-tabs { display: inline-flex; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 4px; gap: 4px; }
    .mp-tab { border: 0; background: none; cursor: pointer; font: inherit; font-size: 13px; font-weight: 700; color: var(--text-secondary); padding: 7px 14px; border-radius: 9px; }
    .mp-tab.is-on { background: rgba(249,115,22,.12); color: var(--brand-text, #c2410c); }
    .mp-tab i { font-style: normal; font-size: 11.5px; color: var(--text-muted); margin-left: 4px; }
    .mp-search { display: flex; align-items: center; gap: 8px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 0 12px; height: 42px; min-width: 260px; }
    .mp-search svg { width: 17px; height: 17px; color: var(--text-muted); }
    .mp-search input { border: 0; outline: 0; background: transparent; font: inherit; font-size: 13.5px; flex: 1; color: var(--text-primary); }

    .mp-sec { margin-bottom: 28px; }
    .mp-sec-h { display: flex; align-items: baseline; gap: 10px; margin: 0 0 4px; }
    .mp-sec-h h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .mp-sec-h span { font-size: 12.5px; color: var(--text-muted); }
    .mp-sec-sub { font-size: 13px; color: var(--text-muted); margin: 0 0 14px; }
    .mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }

    .mp-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 18px; overflow: hidden; display: flex; flex-direction: column; transition: box-shadow .15s, transform .15s; }
    .mp-card:hover { box-shadow: 0 14px 34px -18px rgba(15,27,53,.35); transform: translateY(-1px); }
    .mp-cover { height: 58px; background: linear-gradient(120deg, #fff1e6, #ffe4d1 45%, #fde7f3); }
    .mp-body { padding: 0 18px 16px; display: flex; flex-direction: column; gap: 12px; flex: 1; }
    .mp-top { display: flex; gap: 14px; align-items: flex-end; margin-top: -30px; }
    .mp-avw { position: relative; flex: none; }
    .mp-av { width: 64px; height: 64px; border-radius: 18px; object-fit: cover; border: 3px solid var(--bg-card); background: #f97316; display: block; box-shadow: 0 6px 16px -8px rgba(15,27,53,.4); }
    .mp-on { position: absolute; right: -2px; bottom: -2px; width: 14px; height: 14px; border-radius: 50%; background: #22c55e; border: 2.5px solid var(--bg-card); }
    .mp-who { min-width: 0; padding-bottom: 2px; }
    .mp-name { font-size: 15.5px; font-weight: 800; color: var(--text-primary); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .mp-name:hover { color: var(--brand-text, #c2410c); }
    .mp-loc { font-size: 12px; color: var(--text-muted); margin-top: 2px; display: flex; align-items: center; gap: 4px; }
    .mp-loc svg { width: 13px; height: 13px; }
    .mp-rate { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-secondary); }
    .mp-rate .st { color: #f59e0b; letter-spacing: 1px; }
    .mp-tags { display: flex; flex-wrap: wrap; gap: 6px; }
    .mp-tag { font-size: 11.5px; font-weight: 700; color: var(--text-secondary); background: var(--bg-card-hover, #f1f5f9); border-radius: 999px; padding: 4px 10px; }
    .mp-facts { display: grid; grid-template-columns: repeat(3, 1fr); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
    .mp-fact { padding: 9px 10px; text-align: center; }
    .mp-fact + .mp-fact { border-left: 1px solid var(--border-color); }
    .mp-fact b { display: block; font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .mp-fact span { font-size: 11px; color: var(--text-muted); }
    .mp-last { font-size: 12.5px; color: var(--text-muted); line-height: 1.5; }
    .mp-last b { color: var(--text-primary); font-weight: 700; }
    .mp-note { font-size: 12.5px; color: var(--text-secondary); background: var(--bg-card-hover, #f8fafc); border-radius: 10px; padding: 8px 11px; font-style: italic; }
    .mp-actions { display: flex; gap: 8px; margin-top: auto; }
    .mp-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font: inherit; font-size: 13px; font-weight: 700; height: 40px; border-radius: 11px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); text-decoration: none; cursor: pointer; }
    .mp-btn svg { width: 15px; height: 15px; }
    .mp-btn:hover { border-color: #f97316; color: var(--brand-text, #c2410c); }
    .mp-btn.primary { background: #f97316; color: #fff; border-color: #f97316; }
    .mp-btn.primary:hover { background: #ea580c; color: #fff; }
    .mp-btn.icon { flex: 0 0 40px; font-size: 16px; }
    .mp-btn.icon.is-on { color: #f59e0b; border-color: #fcd34d; background: #fffbeb; }
    .mp-actions form { display: contents; }

    .mp-empty { background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 18px; padding: 34px 20px; text-align: center; }
    .mp-empty-ico { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 16px; background: rgba(249,115,22,.1); color: #ea580c; display: flex; align-items: center; justify-content: center; }
    .mp-empty-ico svg { width: 26px; height: 26px; }
    .mp-empty h4 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; }
    .mp-empty p { font-size: 13px; color: var(--text-muted); margin: 0 0 14px; }
    .mp-empty a { display: inline-flex; height: 40px; align-items: center; padding: 0 18px; border-radius: 11px; background: #f97316; color: #fff; font-weight: 700; font-size: 13px; text-decoration: none; }
    .mp-none { display: none; }

    @media (max-width: 1100px) { .mp-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 560px) { .mp-stats { grid-template-columns: 1fr; } .mp-search { min-width: 0; width: 100%; } }
</style>
@endpush

@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
    $initialsFallback = fn ($u) => $u->avatar_url;
@endphp

@section('content')
<div>
    @if(session('status'))
        <div style="background:rgba(16,163,74,.12);border:1px solid rgba(16,163,74,.35);color:var(--ok-text);padding:11px 16px;border-radius:12px;margin-bottom:16px;font-size:13.5px;">{{ session('status') }}</div>
    @endif

    {{-- The numbers, counted from the same rows as the cards below. --}}
    <div class="mp-stats">
        <div class="mp-stat">
            <div class="mp-stat-ico" style="background:rgba(249,115,22,.12);color:#ea580c;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div><b>{{ $stats['worked'] }}</b><span>Worked with</span></div>
        </div>
        <div class="mp-stat">
            <div class="mp-stat-ico" style="background:rgba(245,158,11,.14);color:#d97706;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><polygon points="12 2 15.1 8.3 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 8.9 8.3 12 2"/></svg>
            </div>
            <div><b>{{ $stats['saved'] }}</b><span>Saved</span></div>
        </div>
        <div class="mp-stat">
            <div class="mp-stat-ico" style="background:rgba(37,99,235,.1);color:#2563eb;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <div><b>{{ $stats['bookings'] }}</b><span>Bookings together</span></div>
        </div>
        <div class="mp-stat">
            <div class="mp-stat-ico" style="background:rgba(22,163,74,.1);color:#16a34a;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div><b>{{ $money($stats['spent']) }}</b><span>Agreed with them</span></div>
        </div>
    </div>

    <div class="mp-bar">
        <div class="mp-tabs" role="tablist">
            <button type="button" class="mp-tab is-on" data-mp-tab="all">All</button>
            <button type="button" class="mp-tab" data-mp-tab="worked">Worked With <i>{{ $stats['worked'] }}</i></button>
            <button type="button" class="mp-tab" data-mp-tab="saved">Saved <i>{{ $stats['saved'] }}</i></button>
        </div>
        <label class="mp-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.5" y2="16.5"/></svg>
            <input type="search" data-mp-search placeholder="Search by name or service…" aria-label="Search your professionals">
        </label>
    </div>

    {{-- Worked with: from real bookings --}}
    <div class="mp-sec" data-mp-sec="worked">
        <div class="mp-sec-h"><h3>Worked With</h3><span>{{ $stats['worked'] }}</span></div>
        <p class="mp-sec-sub">Professionals you've hired before. Re-book without starting a new search.</p>

        @if($workedWith->isEmpty())
            <div class="mp-empty">
                <div class="mp-empty-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                <h4>No one hired yet</h4>
                <p>Professionals you book will appear here, ready to re-book in one click.</p>
                <a href="{{ route('client.search.index') }}">Find professionals</a>
            </div>
        @else
            <div class="mp-grid">
                @foreach($workedWith as $row)
                    @php
                        $pro = $row['pro'];
                        $isSaved = $savedIds->contains($pro->id);
                        $services = $pro->serviceCategories->take(3);
                        $place = trim(collect([$pro->profile?->city, $pro->profile?->state])->filter()->implode(', '));
                        $online = $pro->last_active_at && \Illuminate\Support\Carbon::parse($pro->last_active_at)->gt(now()->subMinutes(5));
                    @endphp
                    <div class="mp-card" data-mp-card data-mp-text="{{ strtolower($pro->name . ' ' . $services->pluck('name')->implode(' ')) }}">
                        <div class="mp-cover"></div>
                        <div class="mp-body">
                            <div class="mp-top">
                                <div class="mp-avw">
                                    <img class="mp-av" src="{{ $pro->avatar_url }}" alt="{{ $pro->name }}">
                                    @if($online)<span class="mp-on" title="Online now"></span>@endif
                                </div>
                                <div class="mp-who">
                                    <a href="{{ route('public.professional.show', $pro) }}" class="mp-name">
                                        {{ $pro->name }}
                                        @if($pro->isVerified())
                                            <x-hex-badge inline size="18" icon="✓" :colour="config('badges.verified_colour', '#2563eb')" title="Licence, insurance and workers' comp approved" />
                                        @endif
                                        @if($pro->isTopRated())
                                            <x-hex-badge inline size="18" icon="★" :colour="config('badges.top_rated_colour', '#f59e0b')" title="Rated highly by the clients who booked them" />
                                        @endif
                                    </a>
                                    @if($place)
                                        <div class="mp-loc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $place }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="mp-rate">
                                @if($pro->reviews_avg)
                                    <span class="st">{{ str_repeat('★', (int) round($pro->reviews_avg)) }}</span>
                                    <b>{{ number_format($pro->reviews_avg, 1) }}</b>
                                    <span>({{ $pro->reviews_count }} {{ $pro->reviews_count === 1 ? 'review' : 'reviews' }})</span>
                                @else
                                    <span>No reviews yet</span>
                                @endif
                            </div>

                            @if($services->isNotEmpty())
                                <div class="mp-tags">
                                    @foreach($services as $svc)<span class="mp-tag">{{ $svc->name }}</span>@endforeach
                                </div>
                            @endif

                            <div class="mp-facts">
                                <div class="mp-fact"><b>{{ $row['times'] }}</b><span>{{ $row['times'] === 1 ? 'Booking' : 'Bookings' }}</span></div>
                                <div class="mp-fact"><b>{{ $row['completed'] }}</b><span>Completed</span></div>
                                <div class="mp-fact"><b>{{ $money($row['spent']) }}</b><span>Agreed</span></div>
                            </div>

                            <div class="mp-last">
                                Last worked together <b>{{ $row['last']?->humanAgo() }}</b>@if($row['last_event']) on <b>{{ $row['last_event'] }}</b>@endif.
                            </div>

                            <div class="mp-actions">
                                <a href="{{ route('client.direct-offers.create', ['pro' => $pro->id]) }}" class="mp-btn primary">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><polyline points="23 4 23 10 17 10"/><path d="M20.5 15a9 9 0 1 1-2.1-9.4L23 10"/></svg>
                                    Re-book
                                </a>
                                <a href="{{ route('client.chat.index') }}" class="mp-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    Message
                                </a>
                                @if($isSaved)
                                    <form method="POST" action="{{ route('client.saved-professionals.destroy', $pro) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="mp-btn icon is-on" title="Saved. Click to remove" aria-label="Remove from saved">★</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('client.saved-professionals.store') }}">
                                        @csrf
                                        <input type="hidden" name="professional_id" value="{{ $pro->id }}">
                                        <button type="submit" class="mp-btn icon" title="Save" aria-label="Save">☆</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mp-empty mp-none" data-mp-nomatch><h4>No match</h4><p>No one you've worked with matches that search.</p></div>
        @endif
    </div>

    {{-- Saved: pinned to come back to --}}
    <div class="mp-sec" data-mp-sec="saved">
        <div class="mp-sec-h"><h3>Saved</h3><span>{{ $stats['saved'] }}</span></div>
        <p class="mp-sec-sub">Professionals you've pinned to come back to.</p>

        @if($saved->isEmpty())
            <div class="mp-empty">
                <div class="mp-empty-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><polygon points="12 2 15.1 8.3 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 8.9 8.3 12 2"/></svg></div>
                <h4>Nothing saved yet</h4>
                <p>Tap the star on any professional, here or on their profile, to keep them close.</p>
                <a href="{{ route('client.search.index') }}">Browse professionals</a>
            </div>
        @else
            <div class="mp-grid">
                @foreach($saved as $pro)
                    @php
                        $services = $pro->serviceCategories->take(3);
                        $place = trim(collect([$pro->profile?->city, $pro->profile?->state])->filter()->implode(', '));
                        $online = $pro->last_active_at && \Illuminate\Support\Carbon::parse($pro->last_active_at)->gt(now()->subMinutes(5));
                    @endphp
                    <div class="mp-card" data-mp-card data-mp-text="{{ strtolower($pro->name . ' ' . $services->pluck('name')->implode(' ')) }}">
                        <div class="mp-cover" style="background:linear-gradient(120deg,#fffbeb,#fef3c7 45%,#fde7f3);"></div>
                        <div class="mp-body">
                            <div class="mp-top">
                                <div class="mp-avw">
                                    <img class="mp-av" src="{{ $pro->avatar_url }}" alt="{{ $pro->name }}">
                                    @if($online)<span class="mp-on" title="Online now"></span>@endif
                                </div>
                                <div class="mp-who">
                                    <a href="{{ route('public.professional.show', $pro) }}" class="mp-name">
                                        {{ $pro->name }}
                                        @if($pro->isVerified())
                                            <x-hex-badge inline size="18" icon="✓" :colour="config('badges.verified_colour', '#2563eb')" title="Licence, insurance and workers' comp approved" />
                                        @endif
                                        @if($pro->isTopRated())
                                            <x-hex-badge inline size="18" icon="★" :colour="config('badges.top_rated_colour', '#f59e0b')" title="Rated highly by the clients who booked them" />
                                        @endif
                                    </a>
                                    @if($place)
                                        <div class="mp-loc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $place }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="mp-rate">
                                @if($pro->reviews_avg)
                                    <span class="st">{{ str_repeat('★', (int) round($pro->reviews_avg)) }}</span>
                                    <b>{{ number_format($pro->reviews_avg, 1) }}</b>
                                    <span>({{ $pro->reviews_count }} {{ $pro->reviews_count === 1 ? 'review' : 'reviews' }})</span>
                                @else
                                    <span>No reviews yet</span>
                                @endif
                            </div>

                            @if($services->isNotEmpty())
                                <div class="mp-tags">
                                    @foreach($services as $svc)<span class="mp-tag">{{ $svc->name }}</span>@endforeach
                                </div>
                            @endif

                            @if($pro->pivot->note)<div class="mp-note">“{{ $pro->pivot->note }}”</div>@endif

                            <div class="mp-actions">
                                <a href="{{ route('client.direct-offers.create', ['pro' => $pro->id]) }}" class="mp-btn primary">Send a request</a>
                                <a href="{{ route('public.professional.show', $pro) }}" class="mp-btn">View profile</a>
                                <form method="POST" action="{{ route('client.saved-professionals.destroy', $pro) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="mp-btn icon is-on" title="Remove from saved" aria-label="Remove from saved">★</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mp-empty mp-none" data-mp-nomatch><h4>No match</h4><p>No saved professional matches that search.</p></div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    var tabs = document.querySelectorAll('[data-mp-tab]');
    var secs = document.querySelectorAll('[data-mp-sec]');
    var search = document.querySelector('[data-mp-search]');

    function apply() {
        var on = document.querySelector('[data-mp-tab].is-on').dataset.mpTab;
        var q = (search.value || '').trim().toLowerCase();

        secs.forEach(function (sec) {
            sec.hidden = on !== 'all' && sec.dataset.mpSec !== on;
            var cards = sec.querySelectorAll('[data-mp-card]');
            var shown = 0;
            cards.forEach(function (c) {
                var hit = ! q || c.dataset.mpText.indexOf(q) !== -1;
                c.style.display = hit ? '' : 'none';
                if (hit) shown++;
            });
            var none = sec.querySelector('[data-mp-nomatch]');
            if (none) none.classList.toggle('mp-none', ! (cards.length && ! shown));
        });
    }

    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            tabs.forEach(function (x) { x.classList.toggle('is-on', x === t); });
            apply();
        });
    });
    search.addEventListener('input', apply);
})();
</script>
@endpush
@endsection
