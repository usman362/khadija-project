@extends('layouts.landing')

@php
    $seoTitle       = 'Our Story: Built for Better Event Experiences | GigResource';
    $seoDescription = 'GigResource was created to help clients find the right professionals, communicate easily, and manage bookings with confidence, for every kind of event.';
@endphp

@push('styles')
<style>
    /* ══════ About, against the 2026 design file ══════
       Its content column is 94.4% of the viewport (967px in a 1024px
       screenshot). Every number below is that file's own multiplied by
       1.316, the ratio between its column and a 1272px one, and the unit
       carries the rest: 1px at a 1272 column, growing with it. */
    .ab { --u: clamp(0.78px, 0.0742vw, 1.34px); }

    /* On this page the header, the content and the footer share the file's
       column. Pushed from here, so no other page is affected. */
    .lp-container { max-width: min(calc(94.4% + 48px), 1620px); }

    .ab-badge { display: inline-block; font-size: calc(13 * var(--u)); font-weight: 800;
        letter-spacing: calc(1 * var(--u)); text-transform: uppercase;
        padding: calc(5 * var(--u)) calc(14 * var(--u)); border-radius: calc(999 * var(--u)); }
    .ab-badge.blue { background: #e4eefd; color: #1565e8; }
    .ab-badge.peach { background: #ffeadd; color: #f4561a; }

    .ab-h2 { font-size: calc(33 * var(--u)); font-weight: 800; color: #0b1340;
        letter-spacing: calc(-.6 * var(--u)); margin: calc(10 * var(--u)) 0 0; line-height: 1.2; }

    /* ── 1. Our Story hero ────────────────────────────────────── */
    .ab-hero { position: relative; overflow: hidden; background: #e9f1fd; }
    /* The photograph, its warm light and the two floating panels over it
       are one picture on the file, so they are one picture here. The words
       inside those panels are not legible on it, and inventing words to put
       on a page about trust is not something to do. */
    .ab-hero-art { position: absolute; top: 0; right: 0; bottom: 0; width: 54.1%;
        background-image: url('{{ asset('images/about/hero.jpg') }}');
        background-position: right center; background-size: 100% 100%;
        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 13%);
        mask-image: linear-gradient(90deg, transparent 0, #000 13%); }
    .ab-hero-in { position: relative; z-index: 2; padding: calc(30 * var(--u)) 0 calc(34 * var(--u));
        max-width: calc(620 * var(--u)); }
    .ab-hero h1 { font-size: calc(55 * var(--u)); font-weight: 800; letter-spacing: calc(-1.5 * var(--u));
        line-height: 1.08; color: #0b1340; margin: calc(11 * var(--u)) 0 0; }
    .ab-hero h1 .o { color: #f4561a; }
    .ab-hero h1 .b { color: #1565e8; }
    .ab-hero p { font-size: calc(17 * var(--u)); color: #3f4a63; line-height: 1.55;
        margin: calc(14 * var(--u)) 0 0; max-width: calc(560 * var(--u)); }

    .ab-body { background: #f7fbfe; }

    /* ── 2. Mission and Vision ────────────────────────────────── */
    .ab-mv { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: calc(16 * var(--u)); padding-top: calc(13 * var(--u)); }
    .ab-mvcard { display: grid; grid-template-columns: calc(118 * var(--u)) minmax(0, 1fr);
        align-items: center; gap: calc(6 * var(--u)); border-radius: calc(15 * var(--u));
        padding: calc(22 * var(--u)) calc(24 * var(--u)); border: 1px solid var(--ln); background: var(--bgc); }
    .ab-mvcard.mission { --ln: #cfe0fb; --bgc: linear-gradient(130deg, #e6effd, #eef4fe); }
    .ab-mvcard.vision { --ln: #fbd9c1; --bgc: linear-gradient(130deg, #fff2e8, #fff7f1); }
    .ab-mvcard .circ { width: calc(86 * var(--u)); height: calc(86 * var(--u)); border-radius: 50%;
        display: flex; align-items: center; justify-content: center; }
    .ab-mvcard.mission .circ { background: #1565e8; }
    .ab-mvcard.vision .circ { background: #ffddc4; }
    .ab-mvcard .circ svg { width: calc(48 * var(--u)); height: calc(48 * var(--u)); }
    .ab-mvcard h3 { font-size: calc(25 * var(--u)); font-weight: 800; margin: 0 0 calc(5 * var(--u)); }
    .ab-mvcard.mission h3 { color: #1565e8; }
    .ab-mvcard.vision h3 { color: #f4561a; }
    .ab-mvcard p { font-size: calc(16 * var(--u)); color: #3f4a63; line-height: 1.5; margin: 0; }

    /* ── 3. Our Values ────────────────────────────────────────── */
    .ab-sec { text-align: center; padding-top: calc(27 * var(--u)); }
    .ab-sec > p.lede { font-size: calc(15 * var(--u)); color: #5a6679; margin: calc(8 * var(--u)) 0 0; }
    .ab-grid4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: calc(16 * var(--u)); margin-top: calc(18 * var(--u)); text-align: center; }
    .ab-val { background: #fff; border: 1px solid #e7eef8; border-radius: calc(14 * var(--u));
        padding: calc(21 * var(--u)) calc(18 * var(--u)) calc(19 * var(--u));
        box-shadow: 0 calc(5 * var(--u)) calc(18 * var(--u)) rgba(15,27,53,.05); }
    .ab-val .circ { width: calc(62 * var(--u)); height: calc(62 * var(--u)); border-radius: 50%;
        margin: 0 auto calc(15 * var(--u)); display: flex; align-items: center; justify-content: center;
        background: var(--k); color: #fff; }
    .ab-val .circ svg { width: calc(32 * var(--u)); height: calc(32 * var(--u)); }
    .ab-val h4 { font-size: calc(19 * var(--u)); font-weight: 800; color: #0b1340; margin: 0 0 calc(8 * var(--u)); }
    .ab-val p { font-size: calc(14.5 * var(--u)); color: #5a6679; line-height: 1.5; margin: 0; }

    /* ── 4. Why choose us ─────────────────────────────────────── */
    .ab-why { display: grid; grid-template-columns: calc(78 * var(--u)) minmax(0, 1fr);
        align-items: center; gap: calc(4 * var(--u)); text-align: left;
        background: #fff; border: 1px solid #e7eef8; border-radius: calc(13 * var(--u));
        padding: calc(16 * var(--u)) calc(16 * var(--u));
        box-shadow: 0 calc(5 * var(--u)) calc(18 * var(--u)) rgba(15,27,53,.05); }
    .ab-why .circ { width: calc(58 * var(--u)); height: calc(58 * var(--u)); border-radius: 50%;
        background: #e4eefd; color: #1565e8; display: flex; align-items: center; justify-content: center; }
    .ab-why .circ svg { width: calc(28 * var(--u)); height: calc(28 * var(--u)); }
    .ab-why h4 { font-size: calc(17 * var(--u)); font-weight: 800; color: #0b1340; margin: 0 0 calc(5 * var(--u)); }
    .ab-why p { font-size: calc(14 * var(--u)); color: #5a6679; line-height: 1.45; margin: 0; }

    /* ── 5. Who we serve ──────────────────────────────────────── */
    .ab-grid3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: calc(16 * var(--u)); margin-top: calc(22 * var(--u)); text-align: left; }
    .ab-serve { background: #fff; border: 1px solid #e7eef8; border-radius: calc(14 * var(--u));
        overflow: hidden; box-shadow: 0 calc(5 * var(--u)) calc(18 * var(--u)) rgba(15,27,53,.06); }
    /* display:block, or the height is ignored: a span is inline and takes
       its height from the picture inside it. */
    .ab-serve .pic { display: block; height: calc(152 * var(--u)); background: #eef3f9; }
    .ab-serve .pic img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ab-serve .txt { display: grid; grid-template-columns: calc(62 * var(--u)) minmax(0, 1fr);
        align-items: center; gap: calc(2 * var(--u));
        padding: calc(14 * var(--u)) calc(16 * var(--u)) calc(16 * var(--u)); }
    .ab-serve .circ { grid-row: span 2; width: calc(48 * var(--u)); height: calc(48 * var(--u));
        border-radius: 50%; background: var(--k); color: #fff;
        display: flex; align-items: center; justify-content: center; }
    .ab-serve .circ svg { width: calc(25 * var(--u)); height: calc(25 * var(--u)); }
    .ab-serve h4 { font-size: calc(18 * var(--u)); font-weight: 800; color: #0b1340; margin: 0; align-self: end; }
    .ab-serve p { grid-column: 2; font-size: calc(14.5 * var(--u)); color: #5a6679;
        line-height: 1.45; margin: calc(3 * var(--u)) 0 0; align-self: start; }

    /* ── 6. The band above the footer ─────────────────────────── */
    .ab-cta { display: grid; grid-template-columns: calc(118 * var(--u)) minmax(0, 1fr) auto;
        align-items: center; gap: calc(8 * var(--u));
        margin: calc(26 * var(--u)) 0 calc(26 * var(--u));
        background: linear-gradient(120deg, #fff4ec, #fff9f4); border: 1px solid #fbdcc6;
        border-radius: calc(16 * var(--u)); padding: calc(22 * var(--u)) calc(26 * var(--u)); }
    .ab-cta .env { width: calc(92 * var(--u)); height: calc(92 * var(--u)); }
    .ab-cta h2 { font-size: calc(27 * var(--u)); font-weight: 800; color: #0b1340; margin: 0 0 calc(6 * var(--u)); }
    .ab-cta p { font-size: calc(15.5 * var(--u)); color: #3f4a63; line-height: 1.5; margin: 0;
        max-width: calc(560 * var(--u)); }
    .ab-cta-btns { display: flex; gap: calc(14 * var(--u)); flex: none; }
    .ab-cbtn { display: inline-flex; align-items: center; gap: calc(10 * var(--u)); text-decoration: none;
        color: #fff; font-size: calc(16 * var(--u)); font-weight: 700;
        padding: calc(14 * var(--u)) calc(24 * var(--u)); border-radius: calc(10 * var(--u)); white-space: nowrap; }
    .ab-cbtn svg { width: calc(19 * var(--u)); height: calc(19 * var(--u)); flex: none; }
    .ab-cbtn .go { width: calc(16 * var(--u)); height: calc(16 * var(--u)); }
    .ab-cbtn.orange { background: #f4561a; }
    .ab-cbtn.blue { background: #1565e8; }

    @media (max-width: 1080px) {
        .ab-grid4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ab-grid3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 760px) {
        .ab-hero-art { display: none; }
        .ab-hero-in { max-width: none; }
        .ab-mv, .ab-grid4, .ab-grid3 { grid-template-columns: minmax(0, 1fr); }
        .ab-cta { grid-template-columns: minmax(0, 1fr); text-align: center; justify-items: center; }
        .ab-cta-btns { justify-content: center; flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')

@php
    $arrow = '<svg class="go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h15"/><path d="m13 6 6 6-6 6"/></svg>';

    $people = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.6 3.4a3.5 3.5 0 1 1 0 7 3.5 3.5 0 0 1 0-7zm7.6 1a2.9 2.9 0 1 1 0 5.8 2.9 2.9 0 0 1 0-5.8zM8.6 12.2c3.5 0 6.3 1.9 6.3 4.4 0 1-.8 1.8-1.8 1.8H4.1c-1 0-1.8-.8-1.8-1.8 0-2.5 2.8-4.4 6.3-4.4zm7.6.4c3 0 5.3 1.6 5.3 3.7 0 .9-.7 1.6-1.6 1.6h-3.7c.2-.4.3-.8.3-1.3 0-1.6-.8-3-2.1-3.9a8.5 8.5 0 0 1 1.8-.1z"/></svg>';
    $smile  = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zM8.6 8.1a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm6.8 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM6.9 13.6a1.1 1.1 0 0 0-.8 1.8A7.6 7.6 0 0 0 12 18a7.6 7.6 0 0 0 5.9-2.6 1.1 1.1 0 1 0-1.7-1.4A5.4 5.4 0 0 1 12 15.8a5.4 5.4 0 0 1-4.2-1.8 1.1 1.1 0 0 0-.9-.4z"/></svg>';
    $bulb   = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1.8a7.4 7.4 0 0 1 4.4 13.35c-.5.37-.84.9-.95 1.5l-.16.85a1.4 1.4 0 0 1-1.38 1.15h-3.82a1.4 1.4 0 0 1-1.38-1.15l-.16-.85c-.11-.6-.45-1.13-.95-1.5A7.4 7.4 0 0 1 12 1.8zM9.6 20.3h4.8a1.1 1.1 0 0 1 0 2.2H9.6a1.1 1.1 0 0 1 0-2.2z"/></svg>';
    $person = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.6a4.8 4.8 0 1 1 0 9.6 4.8 4.8 0 0 1 0-9.6zm0 11.1c4.3 0 7.8 2.4 7.8 5.4 0 1.1-.9 1.9-2 1.9H6.2c-1.1 0-2-.8-2-1.9 0-3 3.5-5.4 7.8-5.4z"/></svg>';
    $lock   = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1.8A5.2 5.2 0 0 0 6.8 7v2.3H6A2.5 2.5 0 0 0 3.5 11.8v7.7A2.5 2.5 0 0 0 6 22h12a2.5 2.5 0 0 0 2.5-2.5v-7.7A2.5 2.5 0 0 0 18 9.3h-.8V7A5.2 5.2 0 0 0 12 1.8zm0 2.4A2.8 2.8 0 0 1 14.8 7v2.3H9.2V7A2.8 2.8 0 0 1 12 4.2z"/></svg>';
    $chat   = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.6c5.2 0 9.4 3.5 9.4 7.9s-4.2 7.9-9.4 7.9c-1 0-2-.13-2.9-.37l-4.2 1.8a.6.6 0 0 1-.82-.72l1-3.3c-1.3-1.45-2.08-3.26-2.08-5.3 0-4.4 4.2-7.9 9.4-7.9z"/></svg>';
    $head   = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.2A9.3 9.3 0 0 0 2.7 11.5v5.1A3.4 3.4 0 0 0 6.1 20h.8a1.6 1.6 0 0 0 1.6-1.6v-5.2a1.6 1.6 0 0 0-1.6-1.6H5.1v-.1a6.9 6.9 0 1 1 13.8 0v.1h-1.8a1.6 1.6 0 0 0-1.6 1.6v5.2a1.6 1.6 0 0 0 1.6 1.6h1.4a4.6 4.6 0 0 1-3.9 2.2h-1.2a1.15 1.15 0 0 0 0 2.3h1.2a6.9 6.9 0 0 0 6.7-5.2c.07-.3.1-.6.1-.9v-6.4A9.3 9.3 0 0 0 12 2.2z"/></svg>';
    $brief  = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 2h6a2.5 2.5 0 0 1 2.5 2.5V6h2A2.5 2.5 0 0 1 22 8.5v10A2.5 2.5 0 0 1 19.5 21h-15A2.5 2.5 0 0 1 2 18.5v-10A2.5 2.5 0 0 1 4.5 6h2V4.5A2.5 2.5 0 0 1 9 2zm.4 2.4a.6.6 0 0 0-.6.6V6h6.4V5a.6.6 0 0 0-.6-.6zM10.8 12a1.2 1.2 0 0 0 0 2.4h2.4a1.2 1.2 0 0 0 0-2.4z"/></svg>';
    $mega   = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 3.3a1.3 1.3 0 0 1 2.2.95v15.5a1.3 1.3 0 0 1-2.2.95l-3.9-3.7H12v3.4a1.6 1.6 0 0 1-1.6 1.6H8.2a1.6 1.6 0 0 1-1.6-1.6V17H5A3 3 0 0 1 2 14v-4.1a3 3 0 0 1 3-3h8.7zM8.9 17v2.6h1.1V17z"/></svg>';

    /* Four values, four reasons, three audiences: the file's own words. */
    $values = [
        ['Trust First',  'Honest profiles and transparent communication help build confidence from day one.', $people, '#1565e8'],
        ['Simplicity',   'Finding and hiring event professionals should be straightforward and stress-free.', $smile,  '#f4561a'],
        ['Community',    'We believe successful events happen when talented professionals and clients work together.', $people, '#1565e8'],
        ['Innovation',   'We continuously improve the platform to make event planning more efficient and enjoyable.', $bulb, '#f4561a'],
    ];

    $why = [
        ['Professional Profiles', 'Helping clients and event professionals showcase their talent and services.', $person],
        ['Secure Bookings',       'Protected transactions for both clients and professionals.', $lock],
        ['Easy Communication',    'Discuss requirements and manage details in one place.', $chat],
        ['Dedicated Support',     'Guidance whenever you need assistance.', $head],
    ];

    $serve = [
        ['Clients',              'Find the right professionals for your event.', $people, '#f4561a', 'clients.jpg',     route('public.browse')],
        ['Event Professionals',  'Showcase your expertise and grow your business.', $brief, '#1565e8', 'pros.jpg',        route('register', ['role' => 'professional'])],
        ['Influencers',          'Create and share event experiences and grow your audience.', $mega, '#9a1ae8', 'influencers.jpg', route('register', ['role' => 'influencer'])],
    ];
@endphp

<div class="ab">

{{-- ── 1. Our Story ─────────────────────────────────────────── --}}
<section class="ab-hero">
    <span class="ab-hero-art" role="presentation"></span>
    <div class="lp-container">
        <div class="ab-hero-in">
            <span class="ab-badge peach">Our Story</span>
            <h1>Built for Better<br><span class="o">Event</span> <span class="b">Experiences</span></h1>
            <p>Planning an event should be exciting, not stressful. GigResource was created to help clients find the right professionals, communicate easily, and manage bookings with confidence.</p>
            <p>Whether it&rsquo;s a corporate event, wedding, private celebration, or live experience, our goal is to simplify every step of the process.</p>
        </div>
    </div>
</section>

<div class="ab-body">
<div class="lp-container">

    {{-- ── 2. Mission and Vision ────────────────────────────── --}}
    <div class="ab-mv">
        <div class="ab-mvcard mission">
            <span class="circ" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="none">
                    <circle cx="24" cy="24" r="17" stroke="#fff" stroke-width="3.4"/>
                    <circle cx="24" cy="24" r="9" stroke="#fff" stroke-width="3.4"/>
                    <circle cx="24" cy="24" r="2.6" fill="#fff"/>
                    <path d="M24 24 40 8" stroke="#f97316" stroke-width="3.6" stroke-linecap="round"/>
                    <path d="M33 8h7v7" stroke="#f97316" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <div>
                <h3>Our Mission</h3>
                <p>To make it easier for people to discover, connect with, and hire trusted event professionals for every type of event.</p>
            </div>
        </div>

        <div class="ab-mvcard vision">
            <span class="circ" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="none">
                    <path d="M28.6 5.3c5.2-1.7 10.4-1.2 12.6 1s2.7 7.4 1 12.6c-1.6 4.7-4.5 9.1-8.3 12.6l-.8.7-1 6.4a2 2 0 0 1-1 1.45l-5.8 3.3a2 2 0 0 1-2.9-2l.6-5.6-6.4-6.4-5.6.6a2 2 0 0 1-2-2.9l3.3-5.8a2 2 0 0 1 1.45-1l6.4-1 .7-.8c3.5-3.8 7.9-6.7 12.6-8.3z" fill="#f4561a"/>
                    <circle cx="31" cy="17" r="3.6" fill="#fff"/>
                    <path d="M12.4 31.4c1.2 1.2.8 3.7-1 5.5-1.4 1.4-5.1 2.5-6.7 2.9-.4.1-.8-.3-.7-.7.4-1.6 1.5-5.3 2.9-6.7 1.8-1.8 4.3-2.2 5.5-1z" fill="#ff8a4c"/>
                </svg>
            </span>
            <div>
                <h3>Our Vision</h3>
                <p>To become the most trusted marketplace where event professionals and clients collaborate to create extraordinary experiences.</p>
            </div>
        </div>
    </div>

    {{-- ── 3. Our Values ────────────────────────────────────── --}}
    <section class="ab-sec">
        <span class="ab-badge blue">Our Values</span>
        <h2 class="ab-h2">The Principles Behind GigResource</h2>

        <div class="ab-grid4">
            @foreach($values as [$name, $copy, $icon, $key])
                <div class="ab-val">
                    <span class="circ" style="--k: {{ $key }};" aria-hidden="true">{!! $icon !!}</span>
                    <h4>{{ $name }}</h4>
                    <p>{{ $copy }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── 4. Why choose us ─────────────────────────────────── --}}
    <section class="ab-sec">
        <span class="ab-badge blue">Why Choose Us</span>
        <h2 class="ab-h2">Designed Around Real Event Needs</h2>

        <div class="ab-grid4">
            @foreach($why as [$name, $copy, $icon])
                <div class="ab-why">
                    <span class="circ" aria-hidden="true">{!! $icon !!}</span>
                    <div>
                        <h4>{{ $name }}</h4>
                        <p>{{ $copy }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── 5. Who we serve ──────────────────────────────────── --}}
    <section class="ab-sec">
        <span class="ab-badge blue">Who We Serve</span>
        <h2 class="ab-h2">Bringing the Event Industry Together</h2>

        <div class="ab-grid3">
            @foreach($serve as [$name, $copy, $icon, $key, $file, $href])
                <a class="ab-serve" href="{{ $href }}">
                    <span class="pic">
                        <img src="{{ asset('images/about/' . $file) }}" alt="" loading="lazy">
                    </span>
                    <span class="txt">
                        <span class="circ" style="--k: {{ $key }};" aria-hidden="true">{!! $icon !!}</span>
                        <h4>{{ $name }}</h4>
                        <p>{{ $copy }}</p>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ── 6. The band above the footer ─────────────────────── --}}
    <div class="ab-cta">
        <svg class="env" viewBox="0 0 92 92" fill="none" aria-hidden="true">
            <defs><linearGradient id="abEnv" x1="0" y1="0" x2="92" y2="92" gradientUnits="userSpaceOnUse"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#1565e8"/></linearGradient></defs>
            <rect x="12" y="24" width="68" height="50" rx="9" fill="url(#abEnv)"/>
            <path d="M12 30l34 24 34-24" stroke="#fff" stroke-width="4" fill="none" stroke-linejoin="round"/>
            <path d="M12 30l34 24 34-24v-2a4 4 0 0 0-4-4H16a4 4 0 0 0-4 4v2Z" fill="#1049b8"/>
            <circle cx="70" cy="26" r="13" fill="#f4561a"/>
            <path d="M64 26l4 4 8-8" stroke="#fff" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div>
            <h2>Ready to Build Amazing Events Together?</h2>
            <p>Whether you&rsquo;re looking for trusted professionals or want to showcase your services, GigResource is here to help you succeed.</p>
        </div>
        <div class="ab-cta-btns">
            <a class="ab-cbtn orange" href="{{ auth()->check() ? route('client.post-event.event-info') : route('register') }}">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 2a1 1 0 0 1 1 1v1h6V3a1 1 0 1 1 2 0v1h1.5A2.5 2.5 0 0 1 21 6.5v12a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 18.5v-12A2.5 2.5 0 0 1 5.5 4H7V3a1 1 0 0 1 1-1zM5 10v8.5c0 .3.2.5.5.5h13c.3 0 .5-.2.5-.5V10z"/></svg>
                Post an event {!! $arrow !!}
            </a>
            <a class="ab-cbtn blue" href="{{ route('register', ['role' => 'professional']) }}">
                {!! $brief !!}
                Join as professional {!! $arrow !!}
            </a>
        </div>
    </div>
</div>
</div>

</div>
@endsection
