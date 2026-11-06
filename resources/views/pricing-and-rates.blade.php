@extends('layouts.landing')

@section('title', 'Pricing and Rates')

@push('styles')
<style>
    /* ══════ Pricing and Rates, against the 2026 design file ══════
       Every length below is taken off that file and scaled by the ratio
       between its content column (924px in a 1023px screenshot) and this
       site's own (.lp-container, 1272px inside its padding): 1.377. The
       header and the footer are the layout's and are not touched here, so
       the content lines up with them rather than with the design's slightly
       wider gutters. */

    /* ── 1. Hero band ─────────────────────────────────────────── */
    .pr-hero { position: relative; background: #e5f1fd; overflow: hidden; }
    .pr-hero-in { position: relative; z-index: 2; padding: 34px 0 30px; max-width: 760px; }
    .pr-hero h1 { font-size: clamp(34px, 5.1vw, 73px); font-weight: 800; letter-spacing: -1.8px;
        line-height: 1.05; color: #02022f; margin: 0 0 14px; }
    .pr-hero h1 span { color: #0259fb; }
    .pr-hero p { font-size: clamp(14px, 1.35vw, 18px); color: #3f4a63; line-height: 1.45;
        margin: 0; max-width: 700px; }

    /* The artwork is cut from the design file itself rather than stood in
       for: on that file the photograph, its fade into the band and the
       handwriting over it are one picture, so they are one picture here.
       It is decorative and says nothing the heading does not. */
    .pr-hero-art { position: absolute; top: 0; right: 0; bottom: 0; width: 42.7%;
        background-image: url('{{ asset('images/pricing/hero.png') }}');
        background-position: right center; background-size: 100% 100%; z-index: 1;
        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 16%);
        mask-image: linear-gradient(90deg, transparent 0, #000 16%); }

    .pr-body { background: #f3f9fe; }

    /* ── 2 and 3. Fee sections ────────────────────────────────── */
    .pr-sec { margin: 0 0 26px; }
    .pr-sec:first-of-type { margin-top: 5px; }
    .pr-sec h2 { font-size: clamp(21px, 2.4vw, 32px); font-weight: 800; letter-spacing: -.5px;
        color: #11204a; margin: 0; line-height: 1.2; }
    .pr-sec h2 em { font-style: normal; color: #fb3c02; }
    .pr-sec h2 em.blue { color: #0259fb; }
    .pr-sec > p.lede { font-size: clamp(13px, 1.25vw, 17px); color: #5a6679; margin: 0 0 14px; }

    .pr-cards { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    .pr-card { border-radius: 13px; padding: 18px 20px 20px 23px; border: 1px solid transparent; }
    .pr-card.client { background: #fcf4ec; border-color: #f4ece5; }
    .pr-card.pro { background: #edf7ff; border-color: #e4effb; }
    .pr-card .top { display: grid; grid-template-columns: 83px minmax(0, 1fr); align-items: start; margin-right: -21px; }
    /* Each icon is lifted straight off the design file, at the size it is
       drawn there, on the same card colour it sits on there. Redrawing them
       by hand produced a handshake that was not one. */
    .pr-card .ic { grid-row: span 2; align-self: center; width: 68px; height: 66px;
        background-repeat: no-repeat; background-position: left center; }
    .pr-card b { font-size: clamp(13px, 1.2vw, 17px); font-weight: 700; color: #16254f;
        line-height: 1.3; align-self: center; }
    .pr-card .amt { font-size: clamp(20px, 1.95vw, 27px); font-weight: 800; color: #02022f;
        line-height: 1.1; margin-top: 6px; }
    .pr-card .when { display: block; margin-top: 14px; font-size: clamp(12px, 1.1vw, 15px);
        color: #55607a; line-height: 1.48; }

    /* ── 4. Membership plans ──────────────────────────────────── */
    .pr-plans { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px;
        align-items: stretch; }
    .pr-plan { position: relative; display: flex; flex-direction: column;
        border: 1.5px solid var(--pl-line); background: var(--pl-bg);
        border-radius: 14px; padding: 18px 34px 8px; }
    .pr-plan.starter { --pl-line: #efe0cc; --pl-bg: #fef8f1; --pl-tint: #faecdf; --pl-key: #fb3c02; }
    .pr-plan.mid     { --pl-line: #005afd; --pl-bg: #f2f8fd; --pl-tint: #e2ebf8; --pl-key: #005afd; }
    .pr-plan.top     { --pl-line: #ce87ec; --pl-bg: #f8f7fe; --pl-tint: #ede6f8; --pl-key: #901acb; }
    .pr-badge { position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
        font-size: 12.5px; font-weight: 700; letter-spacing: .3px; color: #fff;
        padding: 6px 18px; border-radius: 999px; white-space: nowrap; }
    .pr-plan.mid .pr-badge { background: #1255d6; }
    .pr-plan.top .pr-badge { background: #0c8d68; }

    .pr-plan .head { display: grid; grid-template-columns: 95px minmax(0, 1fr); align-items: start; }
    .pr-plan .head .ic { grid-row: span 2; align-self: center; width: 70px; height: 58px;
        background-repeat: no-repeat; background-position: left center; }
    .pr-plan h3 { font-size: clamp(16px, 1.65vw, 22px); font-weight: 800; color: #11204a; margin: 0; line-height: 1.2; }
    .pr-plan .blurb { font-size: clamp(12px, 1.1vw, 15px); color: #5a6679; line-height: 1.3; margin: 3px 0 0; }
    .pr-price { font-size: clamp(22px, 2.4vw, 33px); font-weight: 800; color: #02022f;
        margin: 17px 0 0; line-height: .95; }
    .pr-price span { font-size: clamp(12px, 1.1vw, 16px); font-weight: 500; color: #5a6679; }

    .pr-share { margin: 6px 0 13px; padding: 7px 10px 8px; border-radius: 9px;
        background: var(--pl-tint); text-align: center; }
    .pr-share b { display: block; font-size: clamp(17px, 1.8vw, 24px); font-weight: 700; color: #11204a; line-height: 1.2; }
    .pr-share span { display: block; margin-top: 3px; font-size: clamp(11px, 1.05vw, 14.5px); color: #55607a; }

    .pr-feats { list-style: none; margin: 0; padding: 0; }
    .pr-feats li { position: relative; padding-left: 30px; font-size: clamp(12px, 1.16vw, 16px);
        color: #30405f; line-height: 1.36; margin-bottom: 4px; }
    .pr-feats li svg { position: absolute; left: 2px; top: 1px; width: 15px; height: 15px; color: var(--pl-key); }
    .pr-more { background: none; border: 0; padding: 0; margin: 2px 0 0; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
        font-size: clamp(12px, 1.1vw, 15px); font-weight: 700; color: #0259fb; }
    .pr-more svg { width: 13px; height: 13px; }
    .pr-plan details[open] .pr-more svg { transform: rotate(180deg); }
    .pr-plan .foot { margin-top: auto; padding-top: 8px; }
    .pr-cta { display: block; text-align: center; border-radius: 9px; text-decoration: none;
        font-size: clamp(13px, 1.25vw, 17px); font-weight: 700; padding: 11px 10px;
        border: 1.5px solid var(--pl-key); background: #fff; color: var(--pl-key); }
    .pr-plan.mid .pr-cta { background: #005afd; color: #fff; }

    /* ── 5. The two notes ─────────────────────────────────────── */
    .pr-notes { display: grid; gap: 9px; margin-top: 14px; }
    .pr-note { display: grid; grid-template-columns: 83px minmax(0, 1fr); align-items: start;
        border-radius: 11px; padding: 13px 23px; }
    .pr-note .ic { width: 34px; height: 34px; }
    .pr-note .ic svg { width: 34px; height: 34px; display: block; }
    .pr-note b { display: block; font-size: clamp(13px, 1.35vw, 19px); font-weight: 700; margin-bottom: 3px; }
    .pr-note p { font-size: clamp(12px, 1.1vw, 15px); line-height: 1.5; margin: 0; }
    .pr-note.policy { background: #fdebea; }
    .pr-note.policy .ic { color: #ed1b0c; }
    .pr-note.policy b { color: #ed1b0c; }
    .pr-note.policy p { color: #3b4663; }
    .pr-note.plain { background: #e5f1fd; }
    .pr-note.plain .ic { color: #005afd; }
    .pr-note.plain b { color: #11204a; }
    .pr-note.plain p { color: #3b4663; }

    /* ── 6. The band above the footer ─────────────────────────── */
    .pr-ready { position: relative; margin-top: 10px; background: #e3eefc; overflow: hidden; }
    /* Both pieces are cut from the design file: the photograph with its fade
       already in it, and the handwriting with its underline. */
    .pr-ready-art { position: absolute; left: 0; top: 0; bottom: 0; width: 29.3%;
        background-image: url('{{ asset('images/pricing/cta-left.png') }}');
        background-position: left center; background-size: 100% 100%;
        -webkit-mask-image: linear-gradient(270deg, transparent 0, #000 16%);
        mask-image: linear-gradient(270deg, transparent 0, #000 16%); }
    .pr-ready-script { position: absolute; right: 0; top: 0; bottom: 0; width: 21.2%;
        background-image: url('{{ asset('images/pricing/cta-script.png') }}');
        background-position: right center; background-size: 100% 100%;
        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 12%);
        mask-image: linear-gradient(90deg, transparent 0, #000 12%); }
    .pr-ready-in { position: relative; z-index: 2; text-align: center; padding: 10px 0 8px; }
    .pr-ready h2 { font-size: clamp(19px, 2.1vw, 29px); font-weight: 800; color: #11204a; margin: 0 0 8px; line-height: 1.2; }
    .pr-ready p { font-size: clamp(12.5px, 1.15vw, 16px); color: #44506a; line-height: 1.45;
        margin: 0 auto 15px; max-width: 540px; }
    .pr-ready-btns { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; }
    .pr-rbtn { display: inline-flex; align-items: center; gap: 11px; text-decoration: none;
        color: #fff; font-size: clamp(13px, 1.2vw, 16.5px); font-weight: 700;
        padding: 14px 26px; border-radius: 9px; }
    .pr-rbtn svg { width: 20px; height: 20px; }
    .pr-rbtn .go { width: 17px; height: 17px; }
    .pr-rbtn.orange { background: #f04e19; }
    .pr-rbtn.blue { background: #0d5bea; }

    @media (max-width: 980px) {
        .pr-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pr-plans { grid-template-columns: minmax(0, 1fr); }
        .pr-hero-in { max-width: none; padding: 38px 0 34px; }
        .pr-hero-art, .pr-ready-art, .pr-ready-script { display: none; }
        .pr-ready-in { padding: 30px 0; }
    }
    @media (max-width: 560px) {
        .pr-cards { grid-template-columns: minmax(0, 1fr); }
        .pr-card .top, .pr-plan .head, .pr-note { grid-template-columns: 54px minmax(0, 1fr); }
    }
</style>
@endpush

@section('content')

@php
    /* Sir Peter, 20 October: "Maybe we should add a webpage named 'pricing
       and rates'?" Yes, and the reason it is worth having is that the same
       figure went stale in three separate places inside one month, each
       written out by hand. Every number below is read from
       App\Domain\Money\FeeSchedule, which is also what the request forms and
       the help rails read, so this page and those forms cannot disagree. */
    $viewer       = auth()->user();
    $clientFees   = \App\Domain\Money\FeeSchedule::forAudience(\App\Domain\Money\FeeSchedule::CLIENT, $viewer);
    $providerFees = \App\Domain\Money\FeeSchedule::forAudience(\App\Domain\Money\FeeSchedule::PROVIDER, $viewer);

    /* Through the published ladder rather than reaching for Commission's
       private rates: one answer about what each tier takes, shared with every
       screen that shows it. */
    $ladder = collect(\App\Support\Commission::ladderFor(100, $viewer))->keyBy('slug');
    $plans  = \App\Models\MembershipPlan::where('is_active', true)->orderBy('sort_order')->get();

    /* One picture per fee, keyed on the fee's own key so a fee that is
       renamed does not silently lose it. Each is a cut from the design file
       and is drawn at the size it has there, scaled by the same 1.377 that
       maps that file's content column onto this one's. */
    $feeArt = [
        'posting'           => ['icon-posting.png', 48, 58],
        'agreement'         => ['icon-agreement.png', 65, 45],
        'request_extension' => ['icon-request-extension.png', 51, 58],
        'repost'            => ['icon-repost.png', 54, 65],
        'package_posting'   => ['icon-package-posting.png', 51, 58],
        'package_agreement' => ['icon-package-agreement.png', 45, 58],
        'package_extension' => ['icon-package-extension.png', 51, 58],
        'package_repost'    => ['icon-package-repost.png', 54, 54],
        'plan-starter'      => ['icon-plan-starter.png', 63, 50],
        'plan-mid'          => ['icon-plan-mid.png', 61, 50],
        'plan-top'          => ['icon-plan-top.png', 62, 51],
    ];

    $art = function (string $key) use ($feeArt): string {
        [$file, $w, $h] = $feeArt[$key] ?? $feeArt['posting'];

        return 'background-image:url(' . asset('images/pricing/' . $file) . ');'
            . 'background-size:' . $w . 'px ' . $h . 'px;';
    };

    $tick    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.8 9.2 18 20 6.4"/></svg>';
    $chevron = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8.5 12 15.5 19 8.5"/></svg>';
    $arrow   = '<svg class="go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h15"/><path d="m13 6 6 6-6 6"/></svg>';

@endphp

{{-- ── 1. Hero band ─────────────────────────────────────────── --}}
<section class="pr-hero">
    <div class="pr-hero-art" role="presentation"></div>
    <div class="lp-container">
        <div class="pr-hero-in">
            <h1>Pricing and <span>Rates</span></h1>
            <p>
                Every charge on GigResource, on one page. Each fee is shown before you pay it,
                and listed on its own line at checkout.
            </p>
        </div>
    </div>
</section>

{{-- The design file does not set these sections on white. Everything
     between the two bands sits on a light blue, which is what gives that
     file its depth; on white the cards read as flat. --}}
<div class="pr-body">
<div class="lp-container">

    {{-- ── 2. Client fees ───────────────────────────────────── --}}
    <section class="pr-sec">
        {{-- The design file sets these two headings with a spaced em dash.
             That punctuation is banned across the project (Ali, 10 September)
             and a guard test fails the build on it, so the colon stands in
             for it. Everything else about the heading is the file's. --}}
        <h2>If You&rsquo;re Hiring: <em>Client Fees</em></h2>
        <p class="lede">What a client pays.</p>

        <div class="pr-cards">
            @foreach($clientFees as $fee)
                <div class="pr-card client">
                    <div class="top">
                        <span class="ic" aria-hidden="true" style="{{ $art($fee['key']) }}"></span>
                        <b>{{ $fee['label'] }}</b>
                        <span class="amt">{{ $fee['formatted'] }}</span>
                    </div>
                    {{-- The note is the second half of the same sentence on
                         the design file, in the same grey, not a coloured
                         line of its own. --}}
                    <span class="when">{{ trim($fee['when'] . ' ' . ($fee['note'] ?? '')) }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── 3. Professional and influencer fees ──────────────── --}}
    <section class="pr-sec">
        <h2>If You&rsquo;re Offering Services: <em class="blue">Professional &amp; Influencer Fees</em></h2>
        <p class="lede">What a professional or influencer pays on a package. Your membership share is separate and shown below.</p>

        <div class="pr-cards">
            @foreach($providerFees as $fee)
                <div class="pr-card pro">
                    <div class="top">
                        <span class="ic" aria-hidden="true" style="{{ $art($fee['key']) }}"></span>
                        <b>{{ $fee['label'] }}</b>
                        <span class="amt">{{ $fee['formatted'] }}</span>
                    </div>
                    <span class="when">{{ trim($fee['when'] . ' ' . ($fee['note'] ?? '')) }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── 4. Membership plans ──────────────────────────────── --}}
    @if($plans->isNotEmpty())
        <section class="pr-sec">
            <h2>Professional &amp; <em class="blue">Influencer Membership Plans</em></h2>
            <p class="lede">Choose the plan that&rsquo;s right for you. The share is taken only when a job is completed and paid.</p>

            <div class="pr-plans">
                @foreach($plans as $i => $plan)
                    @php
                        // The published ladder, not this page's own
                        // arithmetic, so the share quoted here is the share
                        // actually taken.
                        $rate = $ladder[$plan->slug]['rate'] ?? null;

                        // The design file dresses the three in order: the
                        // free one, the one in the middle, the dearest. Read
                        // off the plans themselves rather than off their
                        // slugs, so a renamed plan keeps its place.
                        $tone = $plan->isFree() ? 'starter' : ($i === $plans->count() - 1 ? 'top' : 'mid');

                        $feats = $plan->features
                            ->where('is_included', true)
                            ->sortBy('sort_order')
                            ->pluck('feature')
                            // The tinted band above the list already says
                            // what the commission is. On the design file the
                            // list does not say it a second time.
                            ->reject(fn (string $f) => str_contains(strtolower($f), 'booking commission'))
                            ->values();
                    @endphp

                    <div class="pr-plan {{ $tone }}">
                        @if($plan->badge_text && $tone !== 'starter')
                            <span class="pr-badge">{{ strtoupper($plan->badge_text) }}</span>
                        @endif

                        <div class="head">
                            <span class="ic" aria-hidden="true" style="{{ $art('plan-' . $tone) }}"></span>
                            <h3>{{ $plan->name }}</h3>
                            @if($plan->description)
                                <p class="blurb">{{ $plan->description }}</p>
                            @endif
                        </div>

                        <div class="pr-price">
                            {{ $plan->isFree() ? '$0.00' : '$' . number_format((float) $plan->price, 2) }}<span>/month</span>
                        </div>

                        @if($rate !== null)
                            <div class="pr-share">
                                <b>{{ rtrim(rtrim(number_format($rate, 2), '0'), '.') }}%</b>
                                <span>booking commission per finalized contract</span>
                            </div>
                        @endif

                        {{-- The first seven, then the rest behind a summary.
                             Nineteen lines of plan detail is not something
                             somebody reads while comparing three of them. --}}
                        <ul class="pr-feats">
                            @foreach($feats->take(7) as $feature)
                                <li>{!! $tick !!}{{ $feature }}</li>
                            @endforeach
                        </ul>

                        @if($feats->count() > 7)
                            <details>
                                <summary class="pr-more">+ {{ $feats->count() - 7 }} more to know {!! $chevron !!}</summary>
                                <ul class="pr-feats" style="margin-top: 12px;">
                                    @foreach($feats->skip(7) as $feature)
                                        <li>{!! $tick !!}{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif

                        <div class="foot">
                            @if($tone === 'top')
                                <a class="pr-cta" href="{{ route('about-us') }}">Contact Sales</a>
                            @else
                                <a class="pr-cta" href="{{ auth()->check() ? url('/membership') : route('register') }}">Get Started</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Founders are deliberately absent. Peter, 20 October: "Before
         finalizing the founders' section, confirm exactly what their benefits
         are ... This will prevent usman from implementing unconfirmed pricing
         rules." Three different versions of that rule have been written down
         this month, so the section waits for his. --}}

    {{-- ── 5. The two notes ─────────────────────────────────── --}}
    <div class="pr-notes">
        {{-- The refund rule applies to every figure above it, and Peter asked
             for it "everywhere". --}}
        <div class="pr-note policy">
            <span class="ic" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zm0 4.1a1.2 1.2 0 0 0-1.2 1.32l.45 5a.76.76 0 0 0 1.5 0l.45-5A1.2 1.2 0 0 0 12 6.1zm0 9.1a1.35 1.35 0 1 0 0 2.7 1.35 1.35 0 0 0 0-2.7z"/></svg>
            </span>
            <div>
                <b>No refunds or credits</b>
                <p>{{ \App\Domain\Money\FeeSchedule::refundPolicy() }}</p>
            </div>
        </div>

        <div class="pr-note plain">
            <span class="ic" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3.5 5h17A1.5 1.5 0 0 1 22 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-17A1.5 1.5 0 0 1 2 17.5v-11A1.5 1.5 0 0 1 3.5 5zm1.9 6.4a1.7 1.7 0 1 0 0 3.4 1.7 1.7 0 0 0 0-3.4zm4.4.35a1.35 1.35 0 0 0 0 2.7h2.1a1.35 1.35 0 0 0 0-2.7z"/></svg>
            </span>
            <div>
                <b>Card processing and tax</b>
                <p>
                    Card processing is charged by our payment provider and is shown as its own line when it
                    applies. Any sales tax is shown separately as well. You see the full total before you pay.
                </p>
            </div>
        </div>
    </div>
</div>
</div>

{{-- ── 6. The band above the footer ─────────────────────────── --}}
<section class="pr-ready">
    <div class="pr-ready-art" role="presentation"></div>
    <div class="pr-ready-script" role="presentation"></div>
    <div class="lp-container">
        <div class="pr-ready-in">
            <h2>Ready to Get Started with GigResource?</h2>
            <p>Whether you&rsquo;re looking for trusted professionals or want to showcase your services, GigResource is here to help you succeed.</p>
            <div class="pr-ready-btns">
                <a class="pr-rbtn orange" href="{{ auth()->check() ? route('client.post-event.event-info') : route('register') }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 2a1 1 0 0 1 1 1v1h6V3a1 1 0 1 1 2 0v1h1.5A2.5 2.5 0 0 1 21 6.5v12a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 18.5v-12A2.5 2.5 0 0 1 5.5 4H7V3a1 1 0 0 1 1-1zM5 9v9.5c0 .3.2.5.5.5h13c.3 0 .5-.2.5-.5V9z"/></svg>
                    Post an event {!! $arrow !!}
                </a>
                <a class="pr-rbtn blue" href="{{ route('register') }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.5 5h17A1.5 1.5 0 0 1 22 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-17A1.5 1.5 0 0 1 2 17.5v-11A1.5 1.5 0 0 1 3.5 5zm.8 2.3 7.1 5.3a1 1 0 0 0 1.2 0l7.1-5.3z"/></svg>
                    Join a professional {!! $arrow !!}
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
