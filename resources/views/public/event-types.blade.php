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
    .et-hero h1 { font-size: clamp(1.9rem, 4vw, 2.7rem); font-weight: 800; color: var(--ink); letter-spacing: -.02em; margin: 0 0 8px; }
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

    /* ── Mockup layout: chip row, rail, four-across wall ───── */
    .et-groupbar { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 22px; }
    .et-gchip { display: inline-flex; align-items: center; gap: 7px; border: 1.5px solid var(--line, #e5e7eb);
        background: #fff; color: #374151; border-radius: 999px; padding: 8px 16px; font-size: 12.5px;
        font-weight: 700; text-decoration: none; cursor: pointer; font-family: inherit; }
    .et-gchip b { font-weight: 800; font-size: 11px; color: #9a3412; background: #ffedd5; border-radius: 999px; padding: 1px 7px; }
    .et-gchip:hover { border-color: #fdba74; }
    .et-gchip.is-on { background: #ea580c; border-color: #ea580c; color: #fff; }
    .et-gchip.is-on b { background: rgba(255,255,255,.22); color: #fff; }
    .et-gextra { display: none; }

    .et-browse { display: grid; grid-template-columns: 250px minmax(0, 1fr); gap: 24px; align-items: start; margin-bottom: 46px; }
    .et-rail-card { border: 1.5px solid var(--line, #e5e7eb); border-radius: 16px; padding: 16px; background: #fff; position: sticky; top: 88px; }
    .et-rail-card h4 { margin: 0 0 2px; font-size: 14px; font-weight: 800; color: #111827; }
    .et-rail-note { margin: 0 0 10px; font-size: 11.5px; color: #6b7280; line-height: 1.4; }
    .et-rail-row { display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 9px 2px; border-bottom: 1px solid #f3f4f6; font-size: 13px; color: #374151; text-decoration: none; }
    .et-rail-row:hover { color: #ea580c; }
    .et-rail-row b { font-size: 11px; font-weight: 800; color: #9a3412; background: #ffedd5; border-radius: 999px; padding: 1px 8px; }
    .et-rail-all { display: inline-block; margin-top: 12px; font-size: 12.5px; font-weight: 800; color: #ea580c; text-decoration: none; }

    .et-all { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    .et-all-card { position: relative; border: 1.5px solid var(--line, #e5e7eb); border-radius: 14px;
        overflow: hidden; background: #fff; text-decoration: none; display: block; }
    .et-all-card:hover { border-color: #fdba74; }
    .et-all-img { height: 118px; background: linear-gradient(135deg, #fed7aa, #fdba74); position: relative; }
    .et-all-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .et-all-init { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        font-size: 30px; font-weight: 800; color: #9a3412; opacity: .55; }
    .et-all-body { padding: 11px 30px 13px 13px; }
    .et-all-name { font-size: 13.5px; font-weight: 800; color: #111827; margin-bottom: 2px; }
    .et-all-sub { font-size: 11.5px; color: #ea580c; font-weight: 700; }
    .et-all-arw { position: absolute; right: 12px; bottom: 12px; color: #9ca3af; font-size: 17px; line-height: 1; }
    .et-all-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px;
        flex-wrap: wrap; font-size: 13px; color: #6b7280; }

    @media (max-width: 1080px) { .et-browse { grid-template-columns: 1fr; } .et-rail-card { position: static; } .et-all { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 820px)  { .et-all { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .et-sec-h { margin: 8px 0 4px; font-size: 1.4rem; font-weight: 700; color: var(--ink); }
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
    .et-hero-card h3 { font-size: 1.7rem; font-weight: 700; color: #fff; margin: 0 0 4px; }
    .et-hero-card p { font-size: 13.5px; color: #e2e8f0; margin: 0 0 14px; }
    .et-hero-card .go { display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.5); color: #fff; border-radius: 10px; padding: 10px 16px; font-size: 13.5px; font-weight: 800; }

    .et-tile { min-height: 156px; }
    .et-tile .ov { position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,15,30,.72), transparent 62%); }
    .et-tile .txt { position: absolute; left: 14px; bottom: 12px; right: 40px; color: #fff; }
    .et-tile h4 { font-size: 15px; font-weight: 800; color: #fff; margin: 0 0 2px; }
    .et-tile p { font-size: 11.5px; color: #e2e8f0; margin: 0; }
    .et-tile .arw { position: absolute; right: 12px; bottom: 12px; width: 26px; height: 26px; border-radius: 50%; background: #fff; color: var(--et); display: flex; align-items: center; justify-content: center; font-weight: 800; }
    .et-subgrid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-top: 16px; }

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
    /* The real catalogue — every event type, paginated. */
    .et-all { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 22px; }
    .et-all-card { border: 1.5px solid var(--line, #e5e7eb); border-radius: 14px; overflow: hidden;
        background: #fff; text-decoration: none; display: block; }
    .et-all-card:hover { border-color: #fdba74; }
    .et-all-img { height: 120px; background: linear-gradient(135deg, #fed7aa, #fdba74); position: relative; }
    .et-all-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .et-all-init { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        font-size: 30px; font-weight: 800; color: #9a3412; opacity: .55; }
    .et-all-body { padding: 12px 13px 14px; }
    .et-all-name { font-size: 14px; font-weight: 800; color: #111827; margin-bottom: 3px; }
    .et-all-sub { font-size: 12px; color: #6b7280; }
    .et-all-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px;
        font-size: 13px; color: #6b7280; margin-bottom: 40px; flex-wrap: wrap; }
    @media (max-width: 1080px) { .et-all { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 820px)  { .et-all { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
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
    .et-cta h3 { font-size: 1.2rem; font-weight: 700; color: var(--ink); margin: 0 0 4px; }
    .et-cta p { color: var(--muted); font-size: 13.5px; margin: 0; }
    .et-cta .btns { display: flex; gap: 10px; flex-shrink: 0; }
    .et-cta .b1 { background: var(--et); color: #fff; border-radius: 11px; padding: 11px 20px; font-weight: 800; text-decoration: none; font-size: 14px; }
    .et-cta .b2 { background: #fff; color: var(--et); border: 1px solid var(--et); border-radius: 11px; padding: 11px 20px; font-weight: 800; text-decoration: none; font-size: 14px; }

    @media (max-width: 1180px) { .et-subgrid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 1000px) { .et-browse { grid-template-columns: 1fr; } .et-rail { display: flex; flex-wrap: wrap; } .et-rail a { flex: 1 1 44%; } .et-pop { grid-template-columns: repeat(2,1fr); } .et-more { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 640px) { .et-fgrid, .et-subgrid { grid-template-columns: 1fr; } .et-hero-card { grid-row: auto; } .et-pop { grid-template-columns: 1fr; } .et-cta { flex-direction: column; text-align: center; } }
</style>
@endpush

@section('content')
<div class="et et-wrap">
    <div class="et-hero">
        <h1>Explore <span>Event Types</span></h1>
        <p>Find the type of event you're planning and discover the services and professionals you need to bring it together.</p>
        <form class="et-search" method="GET" action="{{ route('public.browse') }}">
            <input type="text" name="q" placeholder="Search event types…">
            <button type="submit"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Search</button>
        </form>
    </div>

    {{-- The chips are the Category Masterlist's own 13 archetypes, which is how
         the 106 occasions are actually grouped. A hand-written set of themes
         would need somebody to keep it in step with the taxonomy; these cannot
         drift, because they come from it. --}}
    <div class="et-groupbar">
        <a class="et-gchip {{ $group === '' ? 'is-on' : '' }}" href="{{ route('public.event-types') }}">All Event Types</a>
        @foreach($chips as $name => $count)
            <a class="et-gchip {{ $group === $name ? 'is-on' : '' }} {{ $loop->index >= 5 ? 'et-gextra' : '' }}"
               href="{{ route('public.event-types', ['group' => $name]) }}">{{ $name }} <b>{{ $count }}</b></a>
        @endforeach
        @if($chips->count() > 5)
            <button type="button" class="et-gchip" data-et-more>More ({{ $chips->count() - 5 }}) ▾</button>
        @endif
    </div>

    <div class="et-browse">
        <aside class="et-rail-card">
            <h4>All Event Types</h4>
            <p class="et-rail-note">The occasions with the most to plan for.</p>
            @foreach($rail as $r)
                <a class="et-rail-row" href="{{ route('public.category', $r['slug']) }}">
                    <span>{{ $r['name'] }}</span><b>{{ $r['recommended'] }}</b>
                </a>
            @endforeach
            <a class="et-rail-all" href="{{ route('public.event-types') }}">View all event types →</a>
        </aside>

        <div>
            <h2 class="et-sec-h">Browse <span>Event Types</span></h2>
            <p class="et-sec-p">Choose an event type to start planning.</p>

            <div class="et-all">
                @forelse($wall as $et)
                    <a class="et-all-card" href="{{ route('public.category', $et['slug']) }}">
                        <div class="et-all-img">
                            @if($et['image'])
                                <img src="{{ $et['image'] }}" alt="{{ $et['name'] }}" loading="lazy">
                            @else
                                {{-- No artwork exists for any event type yet. A tinted
                                     tile with the initial reads as deliberate; an image
                                     tag pointing at nothing reads as broken. It fills in
                                     on its own once the pictures are uploaded. --}}
                                <span class="et-all-init">{{ mb_substr($et['name'], 0, 1) }}</span>
                            @endif
                        </div>
                        <div class="et-all-body">
                            <div class="et-all-name">{{ $et['name'] }}</div>
                            <div class="et-all-sub">
                                {{ $et['recommended'] }} {{ \Illuminate\Support\Str::plural('service', $et['recommended']) }} available
                            </div>
                        </div>
                        <span class="et-all-arw">›</span>
                    </a>
                @empty
                    <p class="et-sec-p">No event types in this group.</p>
                @endforelse
            </div>

            <div class="et-all-foot">
                <span>Showing {{ $wall->firstItem() ?? 0 }} to {{ $wall->lastItem() ?? 0 }} of {{ $wall->total() }} event types</span>
                {{ $wall->onEachSide(1)->links() }}
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var more = document.querySelector('[data-et-more]');
    if (!more) return;
    more.addEventListener('click', function () {
        document.querySelectorAll('.et-gextra').forEach(function (c) { c.style.display = 'inline-flex'; });
        more.remove();
    });
})();
</script>
@endsection
