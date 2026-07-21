@extends('layouts.landing')

@section('title', 'Explore by Event Type — GigResource')
@section('meta_description', 'Search by occasion — find the perfect professionals, services and packages for weddings, corporate events, birthdays and every kind of event.')

@php
    // Deep-link helper: occasion → real packages filtered by that event type.
    $link = fn ($name) => route('public.packages', ['event_type' => $name]);
    $countOf = fn ($name) => (int) (($counts ?? [])[$name] ?? 0);
    $badgeColor = ['POPULAR' => '#ea580c', 'FEATURED' => '#2563eb', 'HOT' => '#dc2626', 'NEW' => '#16a34a'];
@endphp

@push('styles')
<style>
    .et { --et: #2563eb; --et-dark: #1d4ed8; --et-soft: #eff6ff; }
    .et-wrap { background: var(--bg-soft); }
    .et-shell { max-width: 1200px; margin: 0 auto; padding: 0 22px 60px; }

    /* Hero */
    .et-hero { text-align: center; padding: 46px 22px 34px; }
    .et-hero h1 { font-size: clamp(1.9rem, 4vw, 2.7rem); font-weight: 900; color: var(--ink); letter-spacing: -.02em; margin: 0 0 8px; }
    .et-hero h1 span { color: var(--et); }
    .et-hero p { color: var(--muted); font-size: 1.02rem; max-width: 560px; margin: 0 auto 22px; }
    .et-search { max-width: 560px; margin: 0 auto; display: flex; gap: 10px; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 8px; box-shadow: 0 12px 30px -20px rgba(15,27,53,.4); }
    .et-search input { flex: 1; border: none; padding: 11px 14px; font-size: 15px; font-family: inherit; background: transparent; }
    .et-search button { border: none; background: var(--et); color: #fff; border-radius: 10px; padding: 0 22px; font-size: 14px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; }

    /* Filter bar */
    .et-filters { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 26px; }
    .et-drop { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 8px 15px; font-size: 13px; font-weight: 700; color: var(--ink-2); }
    .et-chips { margin-left: auto; display: inline-flex; gap: 8px; flex-wrap: wrap; }
    .et-chip { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 8px 15px; font-size: 13px; font-weight: 700; color: var(--ink-2); cursor: pointer; }
    .et-chip.on { background: var(--et); border-color: var(--et); color: #fff; }

    .et-sec-h { margin: 8px 0 4px; font-size: 1.4rem; font-weight: 900; color: var(--ink); }
    .et-sec-h span { color: var(--et); }
    .et-sec-p { color: var(--muted); font-size: 14px; margin: 0 0 18px; }

    /* Browse by Event Type: rail + tabs + featured grid */
    .et-browse { display: grid; grid-template-columns: 220px minmax(0,1fr); gap: 20px; align-items: start; margin-bottom: 40px; }
    .et-rail { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 10px; }
    .et-rail a { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; font-size: 13.5px; font-weight: 700; color: var(--ink-2); text-decoration: none; }
    .et-rail a:hover { background: var(--et-soft); color: var(--et-dark); }
    .et-rail a.on { background: var(--et); color: #fff; }
    .et-rail a .e { font-size: 16px; }

    .et-tabs { display: flex; gap: 6px; flex-wrap: wrap; background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 6px; margin-bottom: 16px; }
    .et-tab { border: none; background: transparent; border-radius: 8px; padding: 8px 15px; font-size: 13px; font-weight: 700; color: var(--muted); cursor: pointer; }
    .et-tab.on { background: var(--et); color: #fff; }

    .et-fgrid { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; }
    .et-card { position: relative; border-radius: 16px; overflow: hidden; text-decoration: none; display: block; }
    .et-card img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .et-hero-card { grid-row: span 2; min-height: 330px; }
    .et-hero-card .ov { position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,15,30,.82), rgba(10,15,30,.15) 60%); }
    .et-hero-card .txt { position: absolute; left: 20px; bottom: 20px; right: 20px; color: #fff; }
    .et-badge-feat { display: inline-block; font-size: 10.5px; font-weight: 800; letter-spacing: .4px; background: var(--et); color: #fff; padding: 4px 10px; border-radius: 6px; margin-bottom: 10px; }
    .et-hero-card h3 { font-size: 1.7rem; font-weight: 900; color: #fff; margin: 0 0 4px; }
    .et-hero-card p { font-size: 13.5px; color: #e2e8f0; margin: 0 0 14px; }
    .et-hero-card .go { display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.5); color: #fff; border-radius: 10px; padding: 10px 16px; font-size: 13.5px; font-weight: 800; }

    .et-tile { min-height: 156px; }
    .et-tile .ov { position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,15,30,.72), transparent 62%); }
    .et-tile .txt { position: absolute; left: 14px; bottom: 12px; right: 40px; color: #fff; }
    .et-tile h4 { font-size: 15px; font-weight: 800; color: #fff; margin: 0 0 2px; }
    .et-tile p { font-size: 11.5px; color: #e2e8f0; margin: 0; }
    .et-tile .arw { position: absolute; right: 12px; bottom: 12px; width: 26px; height: 26px; border-radius: 50%; background: #fff; color: var(--et); display: flex; align-items: center; justify-content: center; font-weight: 800; }
    .et-subgrid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 16px; }

    /* Popular Event Types cards */
    .et-pop { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 40px; }
    .et-pcard { background: #fff; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; text-decoration: none; display: flex; flex-direction: column; transition: transform .15s, box-shadow .15s; }
    .et-pcard:hover { transform: translateY(-3px); box-shadow: 0 16px 34px -20px rgba(15,27,53,.4); }
    .et-pmedia { position: relative; aspect-ratio: 3/2; }
    .et-pmedia img { width: 100%; height: 100%; object-fit: cover; }
    .et-pbadge { position: absolute; top: 9px; left: 9px; font-size: 10px; font-weight: 800; letter-spacing: .3px; color: #fff; padding: 3px 9px; border-radius: 6px; }
    .et-pbody { padding: 12px 14px 14px; }
    .et-pbody b { display: block; font-size: 14px; font-weight: 800; color: var(--ink); }
    .et-pbody span { display: block; font-size: 12px; color: var(--muted); margin: 3px 0 8px; line-height: 1.4; }
    .et-pfrom { font-size: 13px; font-weight: 800; color: var(--et); }
    .et-rc { margin-left: auto; font-size: 11px; font-weight: 800; color: var(--et-dark); background: var(--et-soft); border-radius: 999px; padding: 1px 8px; }
    .et-rail a.on .et-rc { background: rgba(255,255,255,.25); color: #fff; }
    .et-pcount { font-size: 11.5px; font-weight: 700; color: var(--muted); margin-left: 8px; }

    /* More occasions */
    .et-more { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 34px; }
    .et-mcard { position: relative; border-radius: 12px; overflow: hidden; text-decoration: none; min-height: 118px; display: block; }
    .et-mcard img { width: 100%; height: 100%; object-fit: cover; }
    .et-mcard .ov { position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,15,30,.78), transparent 60%); }
    .et-mcard .txt { position: absolute; left: 12px; bottom: 10px; right: 30px; color: #fff; }
    .et-mcard h5 { font-size: 13px; font-weight: 800; margin: 0; color: #fff; }
    .et-mcard p { font-size: 10.5px; color: #e2e8f0; margin: 1px 0 0; }
    .et-mcard .arw { position: absolute; right: 10px; bottom: 10px; width: 22px; height: 22px; border-radius: 50%; background: #fff; color: var(--et); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; }

    /* CTA */
    .et-cta { display: flex; align-items: center; justify-content: space-between; gap: 20px; background: var(--et-soft); border: 1px solid #bfdbfe; border-radius: 18px; padding: 24px 28px; }
    .et-cta h3 { font-size: 1.2rem; font-weight: 900; color: var(--ink); margin: 0 0 4px; }
    .et-cta p { color: var(--muted); font-size: 13.5px; margin: 0; }
    .et-cta .btns { display: flex; gap: 10px; flex-shrink: 0; }
    .et-cta .b1 { background: var(--et); color: #fff; border-radius: 11px; padding: 11px 20px; font-weight: 800; text-decoration: none; font-size: 14px; }
    .et-cta .b2 { background: #fff; color: var(--et); border: 1px solid var(--et); border-radius: 11px; padding: 11px 20px; font-weight: 800; text-decoration: none; font-size: 14px; }

    @media (max-width: 1000px) { .et-browse { grid-template-columns: 1fr; } .et-rail { display: flex; flex-wrap: wrap; } .et-rail a { flex: 1 1 44%; } .et-pop { grid-template-columns: repeat(2,1fr); } .et-more { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 640px) { .et-fgrid, .et-subgrid { grid-template-columns: 1fr; } .et-hero-card { grid-row: auto; } .et-pop { grid-template-columns: 1fr; } .et-cta { flex-direction: column; text-align: center; } }
</style>
@endpush

@section('content')
<div class="et et-wrap">
    <div class="et-hero">
        <h1>Explore by <span>Event Type</span> ✨</h1>
        <p>Search by occasion — find the perfect professionals, services, and packages for every kind of event.</p>
        <form class="et-search" method="GET" action="{{ route('public.browse') }}">
            <input type="text" name="q" placeholder="Search event types or occasions…">
            <button type="submit"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Search</button>
        </form>
    </div>

    <div class="et-shell">
        <div class="et-filters">
            <span class="et-drop">All event types ▾</span>
            <span class="et-drop">All subtypes ▾</span>
            <div class="et-chips">
                <button type="button" class="et-chip on">🔥 Popular</button>
                <button type="button" class="et-chip">📈 Trending</button>
                <button type="button" class="et-chip">💲 Budget-Friendly</button>
                <button type="button" class="et-chip">🆕 Newly Added</button>
                <button type="button" class="et-chip">📍 Near Me</button>
            </div>
        </div>

        {{-- Browse by Event Type --}}
        <h2 class="et-sec-h">Browse by Event Type</h2>
        <p class="et-sec-p">Find professionals and packages for every kind of occasion.</p>
        <div class="et-browse">
            <nav class="et-rail">
                <a href="{{ route('public.event-types') }}" class="on"><span class="e">🗂️</span> All Occasions</a>
                @foreach($occasions as $o)
                    <a href="{{ $link($o['name']) }}"><span class="e">{{ $o['icon'] }}</span> {{ $o['name'] }}{!! $countOf($o['name']) ? '<span class="et-rc">' . $countOf($o['name']) . '</span>' : '' !!}</a>
                @endforeach
            </nav>

            <div>
                <div class="et-tabs">
                    @foreach($groups as $i => $g)
                        <button type="button" class="et-tab {{ $i === 0 ? 'on' : '' }}">{{ $g }}</button>
                    @endforeach
                </div>

                <div class="et-fgrid">
                    <a href="{{ $link($featured['hero']['name']) }}" class="et-card et-hero-card">
                        <img src="{{ $featured['hero']['img'] }}" alt="{{ $featured['hero']['name'] }}" loading="lazy">
                        <div class="ov"></div>
                        <div class="txt">
                            <span class="et-badge-feat">FEATURED</span>
                            <h3>{{ $featured['hero']['name'] }}</h3>
                            <p>{{ $featured['hero']['blurb'] }}</p>
                            <span class="go">Explore {{ $featured['hero']['name'] }}{!! $countOf($featured['hero']['name']) ? ' · ' . $countOf($featured['hero']['name']) . ' packages' : '' !!} →</span>
                        </div>
                    </a>
                    @foreach(array_slice($featured['tiles'], 0, 2) as $t)
                        <a href="{{ $link($t['name']) }}" class="et-card et-tile">
                            <img src="{{ $t['img'] }}" alt="{{ $t['name'] }}" loading="lazy">
                            <div class="ov"></div>
                            <div class="txt"><h4>{{ $t['name'] }}</h4><p>{{ $t['blurb'] }}</p></div>
                            <span class="arw">→</span>
                        </a>
                    @endforeach
                </div>

                <div class="et-subgrid">
                    @foreach(array_slice($featured['tiles'], 2) as $t)
                        <a href="{{ $link($t['name']) }}" class="et-card et-tile">
                            <img src="{{ $t['img'] }}" alt="{{ $t['name'] }}" loading="lazy">
                            <div class="ov"></div>
                            <div class="txt"><h4>{{ $t['name'] }}</h4><p>{{ $t['blurb'] }}</p></div>
                            <span class="arw">→</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Popular Event Types --}}
        <h2 class="et-sec-h">Popular <span>Event Types</span></h2>
        <p class="et-sec-p">Browse the most in-demand occasions and celebrations.</p>
        <div class="et-pop">
            @foreach($popular as $pop)
                <a href="{{ $link($pop['name']) }}" class="et-pcard">
                    <div class="et-pmedia">
                        <img src="{{ $pop['img'] }}" alt="{{ $pop['name'] }}" loading="lazy">
                        <span class="et-pbadge" style="background: {{ $badgeColor[$pop['badge']] ?? '#2563eb' }};">{{ $pop['badge'] }}</span>
                    </div>
                    <div class="et-pbody">
                        <b>{{ $pop['name'] }}</b>
                        <span>{{ $pop['blurb'] }}</span>
                        <span class="et-pfrom">from ${{ number_format($pop['from']) }}{!! $countOf($pop['name']) ? '<span class="et-pcount">' . $countOf($pop['name']) . ' packages</span>' : '' !!}</span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- More Occasions --}}
        <h2 class="et-sec-h">More Occasions to Explore</h2>
        <p class="et-sec-p">Discover professionals for every kind of event.</p>
        <div class="et-more">
            @foreach($more as $m)
                <a href="{{ $link($m['name']) }}" class="et-mcard">
                    <img src="{{ $m['img'] }}" alt="{{ $m['name'] }}" loading="lazy">
                    <div class="ov"></div>
                    <div class="txt"><h5>{{ $m['name'] }}</h5><p>{{ $m['blurb'] }}</p></div>
                    <span class="arw">→</span>
                </a>
            @endforeach
        </div>

        {{-- CTA --}}
        <div class="et-cta">
            <div>
                <h3>Can't find your event type?</h3>
                <p>Post your event and let verified professionals come to you. Describe your needs and receive proposals.</p>
            </div>
            <div class="btns">
                <a class="b1" href="{{ route('register', ['role' => 'client']) }}">Post an Event</a>
                <a class="b2" href="{{ route('public.browse') }}">Find a Professional</a>
            </div>
        </div>
    </div>
</div>
@endsection
