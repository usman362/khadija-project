@extends('layouts.landing')

@section('title', 'Explore by Event Type | GigResource')
@section('meta_description', 'Search by occasion. Find the perfect professionals, services and packages for weddings, corporate events, birthdays and every kind of event.')

@push('styles')
<style>
    /* ══════ Explore Event Types, against the 2026 design file ══════
       Its content column is 89.5% of the viewport (1374px in a 1536px
       screenshot). Every number below is that file's own multiplied by
       0.926, the ratio between its column and a 1272px one, and the unit
       carries the rest: 1px at a 1272 column, growing with it, so the
       file's proportions hold at any width. */
    .et { --u: clamp(0.78px, 0.0704vw, 1.34px); }

    /* On this page the header, the content and the footer share the file's
       column. Pushed from here, so no other page is affected. */
    .lp-container { max-width: min(calc(89.5% + 48px), 1620px); }

    /* ── Hero band ────────────────────────────────────────────── */
    .et-hero { position: relative; background: #eef3f9; overflow: hidden;
        height: calc(144 * var(--u)); display: flex; align-items: center; }
    /* Both halves are cut from the design file. The middle is a pale panel
       there, so the words sit on the panel rather than on the photograph. */
    .et-hero-l, .et-hero-r { position: absolute; top: 0; bottom: 0; }
    .et-hero-l { left: 0; width: 30.6%;
        background-image: url('{{ asset('images/event-types/hero-left.png') }}');
        background-size: 100% 100%;
        -webkit-mask-image: linear-gradient(90deg, #000 58%, transparent 100%);
        mask-image: linear-gradient(90deg, #000 58%, transparent 100%); }
    .et-hero-r { right: 0; width: 27.7%;
        background-image: url('{{ asset('images/event-types/hero-right.png') }}');
        background-size: 100% 100%;
        -webkit-mask-image: linear-gradient(270deg, #000 58%, transparent 100%);
        mask-image: linear-gradient(270deg, #000 58%, transparent 100%); }
    .et-hero-in { position: relative; z-index: 2; width: 100%; text-align: center; }
    .et-hero h1 { font-size: calc(42 * var(--u)); font-weight: 800; letter-spacing: calc(-1 * var(--u));
        line-height: 1.1; color: #0b1340; margin: 0; }
    .et-hero h1 span { color: #0d6bf5; }
    .et-hero p { font-size: calc(15 * var(--u)); color: #3f4a63; line-height: 1.45;
        margin: calc(8 * var(--u)) auto 0; max-width: calc(700 * var(--u)); }

    .et-search { display: flex; align-items: center; margin: calc(12 * var(--u)) auto 0;
        width: calc(533 * var(--u)); background: #fff; border-radius: calc(10 * var(--u));
        box-shadow: 0 calc(6 * var(--u)) calc(22 * var(--u)) rgba(15,27,53,.10);
        padding: calc(5 * var(--u)); }
    .et-search .ic { display: flex; align-items: center; padding: 0 calc(4 * var(--u)) 0 calc(12 * var(--u)); color: #0b1340; }
    .et-search .ic svg { width: calc(21 * var(--u)); height: calc(21 * var(--u)); }
    .et-search input { flex: 1 1 auto; min-width: 0; border: 0; outline: 0; background: transparent;
        font-family: inherit; font-size: calc(15 * var(--u)); color: #0b1340;
        padding: calc(9 * var(--u)) calc(8 * var(--u)); }
    .et-search input::placeholder { color: #7b879c; }
    .et-search button { flex: none; border: 0; cursor: pointer; background: #0d6bf5; color: #fff;
        font-family: inherit; font-size: calc(14 * var(--u)); font-weight: 700;
        border-radius: calc(8 * var(--u)); padding: calc(10 * var(--u)) calc(30 * var(--u)); }

    .et-body { background: #f8fdfe; padding-bottom: calc(16 * var(--u)); }

    /* ── Group chips ──────────────────────────────────────────── */
    .et-chips { display: flex; flex-wrap: wrap; gap: calc(10 * var(--u)); padding-top: calc(11 * var(--u)); }
    .et-chip { display: inline-flex; align-items: center; gap: calc(9 * var(--u)); text-decoration: none;
        background: #fff; border: 1px solid #e3ecf7; border-radius: calc(999 * var(--u));
        padding: calc(3 * var(--u)) calc(15 * var(--u)); font-size: calc(13 * var(--u));
        font-weight: 600; color: #22304d; font-family: inherit; cursor: pointer; }
    .et-chip:hover { border-color: #9ec3f7; }
    .et-chip b { font-size: calc(13 * var(--u)); font-weight: 700; color: #e24a06;
        background: #ffeee4; border-radius: calc(999 * var(--u)); padding: calc(2 * var(--u)) calc(9 * var(--u)); }
    .et-chip.on { background: #ff5104; border-color: #ff5104; color: #fff; }
    .et-chip.on b { background: rgba(255,255,255,.26); color: #fff; }
    .et-chip svg { width: calc(12 * var(--u)); height: calc(12 * var(--u)); }
    .et-extra { display: none; }
    .et-note { font-size: calc(12.5 * var(--u)); color: #5a6679; text-align: center;
        margin: calc(8 * var(--u)) 0 0; }

    /* ── Rail and wall ────────────────────────────────────────── */
    .et-browse { display: grid; grid-template-columns: calc(246 * var(--u)) minmax(0, 1fr);
        gap: calc(21 * var(--u)); align-items: start; margin-top: calc(10 * var(--u)); }
    .et-rail { background: #fff; border: 1px solid #e7eef8; border-radius: calc(13 * var(--u));
        padding: calc(13 * var(--u)) calc(15 * var(--u)) calc(11 * var(--u)); position: sticky; top: calc(88 * var(--u)); }
    .et-rail h4 { font-size: calc(17 * var(--u)); font-weight: 800; color: #0b1340; margin: 0; }
    .et-rail > p { font-size: calc(12 * var(--u)); color: #5a6679; line-height: 1.4;
        margin: calc(4 * var(--u)) 0 calc(4 * var(--u)); }
    .et-rrow { display: flex; align-items: center; justify-content: space-between;
        gap: calc(10 * var(--u)); text-decoration: none; border-top: 1px solid #eef3f9;
        padding: calc(3 * var(--u)) 0; font-size: calc(13 * var(--u)); color: #22304d; }
    .et-rrow:hover { color: #e24a06; }
    .et-rrow .go { flex: none; width: calc(26 * var(--u)); height: calc(20 * var(--u));
        border-radius: calc(999 * var(--u)); background: #ffeee4; color: #e24a06;
        display: flex; align-items: center; justify-content: center; }
    .et-rrow .go svg { width: calc(11 * var(--u)); height: calc(11 * var(--u)); }
    .et-rall { display: inline-flex; align-items: center; gap: calc(8 * var(--u));
        margin-top: calc(9 * var(--u)); font-size: calc(13.5 * var(--u)); font-weight: 700;
        color: #e24a06; text-decoration: none; border-top: 1px solid #eef3f9;
        padding-top: calc(9 * var(--u)); width: 100%; }
    .et-rall svg { width: calc(14 * var(--u)); height: calc(14 * var(--u)); }

    .et-wall-h { font-size: calc(23 * var(--u)); font-weight: 800; color: #0b1340; margin: 0; }
    .et-wall-h span { color: #0d6bf5; }
    .et-wall-p { font-size: calc(13 * var(--u)); color: #5a6679; margin: calc(2 * var(--u)) 0 calc(9 * var(--u)); }
    .et-wall-p a { color: #0d6bf5; }

    .et-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: calc(13 * var(--u)); }
    .et-card { display: block; text-decoration: none; background: #fff; border: 1px solid #e7eef8;
        border-radius: calc(11 * var(--u)); overflow: hidden; }
    .et-card:hover { border-color: #b9d1f7; }
    /* display:block, or the height is ignored: a span is inline, and an
       inline box takes its height from the picture inside it. That is the
       fourth time this has cost this project a layout. */
    .et-card .pic { display: block; position: relative; height: calc(75 * var(--u)); background: #eef3f9; }
    /* One height for every card, cover kept, and what has to be cropped
       comes off the bottom rather than off the top: these pictures carry
       signs and decorations up there, and the box is wider than they are
       (Sir Peter's rule, kept from the older card). */
    .et-card .pic img { width: 100%; height: 100%; object-fit: cover;
        object-position: center 35%; display: block; }
    .et-card .pic .init { position: absolute; inset: 0; display: flex; align-items: center;
        justify-content: center; font-size: calc(30 * var(--u)); font-weight: 800; color: #9fb4d0; }
    .et-card .txt { display: grid; grid-template-columns: minmax(0, 1fr) calc(15 * var(--u));
        align-items: center; padding: calc(6 * var(--u)) calc(11 * var(--u)) calc(7 * var(--u)); }
    .et-card b { font-size: calc(14 * var(--u)); font-weight: 700; color: #0b1340; line-height: 1.2; }
    .et-card span.sub { grid-column: 1; font-size: calc(11.5 * var(--u)); color: #5a6679;
        margin-top: calc(2 * var(--u)); white-space: nowrap; }
    .et-card .go { grid-row: span 2; color: #0d6bf5; display: flex; justify-content: flex-end; }
    .et-card .go svg { width: calc(14 * var(--u)); height: calc(14 * var(--u)); }

    /* ── Count and pages ──────────────────────────────────────── */
    .et-foot { display: flex; align-items: center; justify-content: space-between;
        gap: calc(16 * var(--u)); flex-wrap: wrap; margin-top: calc(12 * var(--u)); }
    .et-count { font-size: calc(13.5 * var(--u)); color: #5a6679; }
    .et-pages { display: flex; align-items: center; gap: calc(7 * var(--u)); }
    .et-pages a, .et-pages span { min-width: calc(31 * var(--u)); height: calc(31 * var(--u));
        display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
        border: 1px solid #e3ecf7; border-radius: calc(8 * var(--u)); background: #fff;
        font-size: calc(13.5 * var(--u)); font-weight: 600; color: #22304d;
        padding: 0 calc(5 * var(--u)); }
    .et-pages a:hover { border-color: #9ec3f7; color: #0d6bf5; }
    .et-pages .on { background: #0d6bf5; border-color: #0d6bf5; color: #fff; }
    .et-pages .off { color: #b4c0d2; }
    .et-pages svg { width: calc(12 * var(--u)); height: calc(12 * var(--u)); }

    /* ── The band above the footer ────────────────────────────── */
    .et-cta { position: relative; background: #fdf2ee; overflow: hidden;
        margin-top: calc(16 * var(--u)); }
    .et-cta-art { position: absolute; left: 0; top: 0; bottom: 0; width: 28.6%;
        background-image: url('{{ asset('images/event-types/cta-left.png') }}');
        background-position: left center; background-size: 100% 100%;
        -webkit-mask-image: linear-gradient(270deg, transparent 0, #000 22%);
        mask-image: linear-gradient(270deg, transparent 0, #000 22%); }
    .et-cta-in { position: relative; z-index: 2; display: flex; align-items: center;
        justify-content: flex-end; gap: calc(26 * var(--u));
        padding: calc(5 * var(--u)) 0 calc(5 * var(--u)) 22%; flex-wrap: nowrap; }
    .et-cta h2 { font-size: calc(23 * var(--u)); font-weight: 800; color: #0b1340; margin: 0 0 calc(5 * var(--u)); }
    .et-cta p { font-size: calc(14 * var(--u)); color: #3f4a63; line-height: 1.45; margin: 0; }
    .et-cta-btns { display: flex; gap: calc(12 * var(--u)); flex: none; }
    .et-cbtn { display: inline-flex; align-items: center; gap: calc(10 * var(--u)); text-decoration: none;
        color: #fff; font-size: calc(15 * var(--u)); font-weight: 700;
        padding: calc(12 * var(--u)) calc(22 * var(--u)); border-radius: calc(9 * var(--u)); white-space: nowrap; }
    .et-cbtn svg { width: calc(18 * var(--u)); height: calc(18 * var(--u)); }
    .et-cbtn .go { width: calc(15 * var(--u)); height: calc(15 * var(--u)); }
    .et-cbtn.orange { background: #f04e19; }
    .et-cbtn.blue { background: #0d6bf5; }

    @media (max-width: 1080px) {
        .et-browse { grid-template-columns: minmax(0, 1fr); }
        .et-rail { position: static; order: 2; }
        .et-browse > div { order: 1; }
        .et-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 760px) {
        .et-hero { height: auto; padding: calc(26 * var(--u)) 0; }
        .et-hero-l, .et-hero-r, .et-cta-art { display: none; }
        .et-search { width: 100%; }
        .et-grid { grid-template-columns: minmax(0, 1fr); }
        .et-cta-in { padding-left: 0; justify-content: center; text-align: center; flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')

@php
    $chev  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 5 7 7-7 7"/></svg>';
    $arrow = '<svg class="go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h15"/><path d="m13 6 6 6-6 6"/></svg>';
@endphp

<div class="et">

{{-- ── Hero band ────────────────────────────────────────────── --}}
<section class="et-hero">
    <span class="et-hero-l" role="presentation"></span>
    <span class="et-hero-r" role="presentation"></span>
    <div class="lp-container et-hero-in">
        <h1>Explore <span>Event Types</span></h1>
        <p>Find the type of event you&rsquo;re planning and discover the services and professionals you need to bring it together.</p>

        {{-- Searches this page. It used to submit to Find Professionals, so
             "wedding" left here and asked which PROFESSIONALS have wedding in
             their name, a different question, usually answered with nothing. --}}
        <form class="et-search" method="GET" action="{{ route('public.event-types') }}">
            @if($group !== '')<input type="hidden" name="group" value="{{ $group }}">@endif
            <span class="ic" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>
            </span>
            <label for="etQ" style="position:absolute;left:-9999px;">Search event types</label>
            <input id="etQ" type="text" name="q" value="{{ $q }}" placeholder="Search event types&hellip;">
            <button type="submit">Search</button>
        </form>
    </div>
</section>

<div class="et-body">
<div class="lp-container">

    {{-- The chips are the Category Masterlist's own 13 archetypes, which is
         how the 106 occasions are actually grouped. A hand-written set of
         themes would need somebody to keep it in step with the taxonomy;
         these cannot drift, because they come from it. --}}
    <div class="et-chips">
        {{-- A chip narrows the search rather than throwing it away. --}}
        <a class="et-chip {{ $group === '' ? 'on' : '' }}"
           href="{{ route('public.event-types', array_filter(['q' => $q])) }}">All Event Types <b>{{ $chips->sum() }}</b></a>

        @foreach($chips as $name => $count)
            <a class="et-chip {{ $group === $name ? 'on' : '' }} {{ $loop->index >= 5 ? 'et-extra' : '' }}"
               href="{{ route('public.event-types', array_filter(['group' => $name, 'q' => $q])) }}">{{ $name }} <b>{{ $count }}</b></a>
        @endforeach

        @if($chips->count() > 5)
            {{-- OA-113: the number is event types, like every other chip, so
                 the row adds up to the total. --}}
            <button type="button" class="et-chip" data-et-more>More <b>{{ $chips->slice(5)->sum() }}</b>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8.5 12 15.5 19 8.5"/></svg>
            </button>
        @endif
    </div>

    <p class="et-note">Numbers show how many event types are in each group, {{ $chips->sum() }} in all.</p>

    <div class="et-browse">
        <aside class="et-rail">
            {{-- Was headed "All Event Types" above a list of ten, with a "View
                 all event types" link directly underneath contradicting it. --}}
            <h4>Most Popular to Plan For</h4>
            <p>These event types are commonly planned and have many service options available.</p>

            @foreach($rail as $r)
                {{-- The count is in the title rather than on the row: the
                     design file does not put a number here, and a badge of 17
                     opening onto 27 tiles is what made the number read as
                     broken in the first place. --}}
                <a class="et-rrow" href="{{ route('public.category', $r['slug']) }}"
                   title="{{ $r['recommended'] }} of {{ $r['of_total'] }} service categories recommended for a {{ $r['name'] }}">
                    <span>{{ $r['name'] }}</span>
                    <span class="go" aria-hidden="true">{!! $chev !!}</span>
                </a>
            @endforeach

            <a class="et-rall" href="{{ route('public.event-types') }}">
                View all event types
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h15"/><path d="m13 6 6 6-6 6"/></svg>
            </a>
        </aside>

        <div>
            <h2 class="et-wall-h">Browse <span>Event Types</span></h2>
            <p class="et-wall-p">
                @if($q !== '')
                    {{ $wall->total() }} {{ \Illuminate\Support\Str::plural('event type', $wall->total()) }}
                    matching &ldquo;{{ $q }}&rdquo;{{ $group !== '' ? ' in '.$group : '' }} &middot;
                    <a href="{{ route('public.event-types', array_filter(['group' => $group])) }}">Clear search</a>
                @else
                    Choose an event type to start planning.
                @endif
            </p>

            <div class="et-grid">
                @forelse($wall as $et)
                    <a class="et-card" href="{{ route('public.category', $et['slug']) }}">
                        <span class="pic">
                            @if($et['image'])
                                <img src="{{ $et['image'] }}" alt="{{ $et['name'] }}" loading="lazy">
                            @else
                                {{-- No picture uploaded for this occasion yet. A
                                     tinted tile with the initial reads as
                                     deliberate; an <img> pointing at nothing reads
                                     as broken, and a stock photograph reads as a
                                     decision nobody made. It fills in on its own
                                     the moment a picture is uploaded. --}}
                                <span class="init">{{ mb_substr($et['name'], 0, 1) }}</span>
                            @endif
                        </span>
                        <span class="txt">
                            <b>{{ $et['name'] }}</b>
                            <span class="go" aria-hidden="true">{!! $chev !!}</span>
                            {{-- "17 services available" was wrong twice over: the
                                 number counts service CATEGORIES, not services
                                 (a wedding touches 171 of the 241 services), and
                                 the page it opens lists all 27 categories with
                                 these ones marked. --}}
                            <span class="sub">{{ $et['recommended'] }} recommended service {{ \Illuminate\Support\Str::plural('category', $et['recommended']) }}</span>
                        </span>
                    </a>
                @empty
                    {{-- Say which search found nothing, and offer the way back. --}}
                    <p class="et-wall-p">
                        @if($q !== '')
                            No event type matches &ldquo;{{ $q }}&rdquo;{{ $group !== '' ? ' in '.$group : '' }}.
                            <a href="{{ route('public.event-types', array_filter(['group' => $group])) }}">Clear search</a>
                        @else
                            No event types in this group.
                        @endif
                    </p>
                @endforelse
            </div>

            @if($wall->total() > 0)
                <div class="et-foot">
                    {{-- The sentence and the page numbers are one row on the
                         file, the sentence on the left. Laravel's own paginator
                         prints both stacked and centred, so the pages are drawn
                         here from the same paginator rather than restyled
                         around it. --}}
                    <span class="et-count">
                        Showing {{ $wall->firstItem() }} to {{ $wall->lastItem() }} of {{ $wall->total() }}
                        {{ \Illuminate\Support\Str::plural('event type', $wall->total()) }}
                    </span>

                    @if($wall->hasPages())
                        <nav class="et-pages" aria-label="Pages of event types">
                            @if($wall->onFirstPage())
                                <span class="off" aria-hidden="true">{!! str_replace('m9 5 7 7-7 7', 'm15 5-7 7 7 7', $chev) !!}</span>
                            @else
                                <a href="{{ $wall->previousPageUrl() }}" rel="prev" aria-label="Previous page">{!! str_replace('m9 5 7 7-7 7', 'm15 5-7 7 7 7', $chev) !!}</a>
                            @endif

                            @foreach($wall->onEachSide(3)->getUrlRange(1, $wall->lastPage()) as $page => $url)
                                @if($page == $wall->currentPage())
                                    <span class="on" aria-current="page">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if($wall->hasMorePages())
                                <a href="{{ $wall->nextPageUrl() }}" rel="next" aria-label="Next page">{!! $chev !!}</a>
                            @else
                                <span class="off" aria-hidden="true">{!! $chev !!}</span>
                            @endif
                        </nav>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
</div>

{{-- ── The band above the footer ────────────────────────────── --}}
<section class="et-cta">
    <span class="et-cta-art" role="presentation"></span>
    <div class="lp-container">
        <div class="et-cta-in">
            <div>
                <h2>Ready to Get Started with GigResource?</h2>
                <p>Find event services or offer your services through the GigResource marketplace.</p>
            </div>
            <div class="et-cta-btns">
                <a class="et-cbtn orange" href="{{ auth()->check() ? route('client.post-event.event-info') : route('register') }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 2a1 1 0 0 1 1 1v1h6V3a1 1 0 1 1 2 0v1h1.5A2.5 2.5 0 0 1 21 6.5v12a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 18.5v-12A2.5 2.5 0 0 1 5.5 4H7V3a1 1 0 0 1 1-1zM5 10v8.5c0 .3.2.5.5.5h13c.3 0 .5-.2.5-.5V10z"/></svg>
                    Post an event {!! $arrow !!}
                </a>
                <a class="et-cbtn blue" href="{{ route('register', ['role' => 'professional']) }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 2h6a2.5 2.5 0 0 1 2.5 2.5V6h2A2.5 2.5 0 0 1 22 8.5v10A2.5 2.5 0 0 1 19.5 21h-15A2.5 2.5 0 0 1 2 18.5v-10A2.5 2.5 0 0 1 4.5 6h2V4.5A2.5 2.5 0 0 1 9 2zm.4 2.4a.6.6 0 0 0-.6.6V6h6.4V5a.6.6 0 0 0-.6-.6zM10.8 12a1.2 1.2 0 0 0 0 2.4h2.4a1.2 1.2 0 0 0 0-2.4z"/></svg>
                    Join as professional {!! $arrow !!}
                </a>
            </div>
        </div>
    </div>
</section>

</div>

<script>
(function () {
    var more = document.querySelector('[data-et-more]');
    if (!more) { return; }
    more.addEventListener('click', function () {
        document.querySelectorAll('.et-extra').forEach(function (c) { c.style.display = 'inline-flex'; });
        more.remove();
    });
})();
</script>
@endsection
