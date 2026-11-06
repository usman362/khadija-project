@extends('layouts.landing')

@section('title', 'Pricing and Rates')

@push('styles')
<style>
    .pr-head { max-width: 760px; margin: 0 auto 28px; text-align: center; }
    .pr-head h1 { font-size: 34px; font-weight: 800; color: var(--ink); margin: 0 0 10px; line-height: 1.15; }
    .pr-head p { font-size: 15px; color: var(--muted); margin: 0; line-height: 1.6; }

    .pr-sec { max-width: 1040px; margin: 0 auto 34px; }
    .pr-sec h2 { font-size: 21px; font-weight: 800; color: var(--ink); margin: 0 0 4px; }
    .pr-sec h2 em { font-style: normal; color: var(--brand, #ea580c); }
    .pr-sec h2 em.blue { color: #2563eb; }
    .pr-sec > p { font-size: 13.5px; color: var(--muted); margin: 0 0 16px; }

    /* Khadijah's 28 October screen prices as cards, one per charge, so the
       figure is the thing you see rather than a column you read across to. */
    .pr-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; }
    .pr-card { background: #fff; border: 1px solid var(--line-soft); border-radius: 14px; padding: 18px; }
    .pr-card.client { background: #fff7ed; border-color: #fed7aa; }
    .pr-card.pro { background: #eff6ff; border-color: #bfdbfe; }
    .pr-card b { display: block; font-size: 14px; font-weight: 800; color: var(--ink); }
    .pr-card .amt { display: block; font-size: 26px; font-weight: 800; color: var(--ink); margin: 7px 0 8px; }
    .pr-card .when { font-size: 12.5px; color: var(--muted); line-height: 1.6; }
    .pr-card .note { display: block; font-size: 12px; color: var(--brand, #ea580c); font-weight: 600; margin-top: 5px; }

    .pr-plans { display: grid; grid-template-columns: repeat(auto-fit, minmax(265px, 1fr)); gap: 18px; align-items: start; }
    .pr-plan { position: relative; background: #fff; border: 2px solid var(--line-soft);
        border-radius: 16px; padding: 22px 20px; }
    .pr-plan.featured { border-color: #2563eb; }
    .pr-plan.best { border-color: #7c3aed; }
    .pr-badge { position: absolute; top: -11px; left: 50%; transform: translateX(-50%);
        font-size: 10px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase;
        padding: 4px 13px; border-radius: 20px; color: #fff; white-space: nowrap; }
    .pr-plan h3 { font-size: 18px; font-weight: 800; color: var(--ink); margin: 0 0 3px; }
    .pr-plan .blurb { font-size: 12.5px; color: var(--muted); margin: 0 0 13px; line-height: 1.5; }
    .pr-price { font-size: 30px; font-weight: 800; color: var(--ink); }
    .pr-price span { font-size: 13px; font-weight: 600; color: var(--muted); }
    .pr-share { margin: 13px 0; padding: 11px; border-radius: 10px; background: #f8fafc; text-align: center; }
    .pr-share b { display: block; font-size: 20px; font-weight: 800; color: var(--ink); }
    .pr-share span { font-size: 11.5px; color: var(--muted); }
    .pr-feats { list-style: none; margin: 0 0 16px; padding: 0; }
    .pr-feats li { position: relative; padding-left: 20px; font-size: 12.5px; color: var(--ink);
        line-height: 1.5; margin-bottom: 7px; }
    .pr-feats li::before { content: ""; position: absolute; left: 2px; top: 6px; width: 9px; height: 5px;
        border-left: 2px solid currentColor; border-bottom: 2px solid currentColor;
        transform: rotate(-45deg); color: #16a34a; }
    .pr-more { font-size: 12px; font-weight: 700; color: #2563eb; background: none; border: 0;
        padding: 0; cursor: pointer; }
    .pr-cta { display: block; text-align: center; font-size: 13.5px; font-weight: 800; padding: 11px;
        border-radius: 10px; text-decoration: none; border: 1.5px solid currentColor; }

    .pr-notes { max-width: 1040px; margin: 0 auto; display: grid; gap: 14px; }
    .pr-policy { border: 1px solid #fca5a5; background: #fef2f2; border-radius: 13px; padding: 15px 18px; }
    .pr-policy b { display: block; font-size: 14px; color: #991b1b; margin-bottom: 3px; }
    .pr-policy p { font-size: 13px; color: #7f1d1d; margin: 0; line-height: 1.6; }
    .pr-plain { border: 1px solid var(--line-soft); background: #fff; border-radius: 13px; padding: 15px 18px; }
    .pr-plain b { display: block; font-size: 14px; color: var(--ink); margin-bottom: 3px; }
    .pr-plain p { font-size: 13px; color: var(--muted); margin: 0; line-height: 1.6; }

    @media (max-width: 640px) {
        .pr-table th:nth-child(2), .pr-table td:nth-child(2) { display: none; }
        .pr-head h1 { font-size: 26px; }
    }
</style>
@endpush

@section('content')
<div class="lp-container" style="padding: 44px 0 60px;">

    {{-- Sir Peter, 20 October: "Maybe we should add a webpage named 'pricing
         and rates'?" Yes, and the reason it is worth having is that the same
         figure went stale in three separate places inside one month, each
         written out by hand. Every number below is read from
         App\Domain\Money\FeeSchedule, which is also what the request forms and
         the help rails read, so this page and those forms cannot disagree. --}}
    <div class="pr-head">
        <h1>Pricing and rates</h1>
        <p>
            Every charge on GigResource, on one page. Each fee is shown before you pay it, and listed on its
            own line at checkout.
        </p>
    </div>

    @php
        $viewer   = auth()->user();
        $clientFees   = \App\Domain\Money\FeeSchedule::forAudience(\App\Domain\Money\FeeSchedule::CLIENT, $viewer);
        $providerFees = \App\Domain\Money\FeeSchedule::forAudience(\App\Domain\Money\FeeSchedule::PROVIDER, $viewer);
        // Through the published ladder rather than reaching for Commission's
        // private rates: one answer about what each tier takes, shared with
        // every screen that shows it.
        $ladder = collect(\App\Support\Commission::ladderFor(100, $viewer))->keyBy('slug');
        $plans  = \App\Models\MembershipPlan::where('is_active', true)->orderBy('sort_order')->get();
    @endphp

    <div class="pr-sec">
        <h2>If you are hiring, <em>client fees</em></h2>
        <p>What a client pays.</p>
        <div class="pr-cards">
            @foreach($clientFees as $fee)
                <div class="pr-card client">
                    <b>{{ $fee['label'] }}</b>
                    <span class="amt">{{ $fee['formatted'] }}</span>
                    <span class="when">{{ $fee['when'] }}</span>
                    @if($fee['note'])<span class="note">{{ $fee['note'] }}</span>@endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="pr-sec">
        <h2>If you are offering services, <em class="blue">professional and influencer fees</em></h2>
        <p>What a professional or influencer pays on a package. Your membership share is separate and shown below.</p>
        <div class="pr-cards">
            @foreach($providerFees as $fee)
                <div class="pr-card pro">
                    <b>{{ $fee['label'] }}</b>
                    <span class="amt">{{ $fee['formatted'] }}</span>
                    <span class="when">{{ $fee['when'] }}</span>
                    @if($fee['note'])<span class="note">{{ $fee['note'] }}</span>@endif
                </div>
            @endforeach
        </div>
    </div>

    @if($plans->isNotEmpty())
        <div class="pr-sec">
            <h2>Professional and <em class="blue">influencer membership</em></h2>
            <p>Starter is free. The share is taken only when a job is completed and paid.</p>

            <div class="pr-plans">
                @foreach($plans as $plan)
                    @php
                        // The published ladder, not this page's own arithmetic,
                        // so the share quoted here is the share actually taken.
                        $rate = $ladder[$plan->slug]['rate'] ?? null;
                        $tone = match ($plan->slug) {
                            'professional' => ['featured', '#2563eb'],
                            'enterprise'   => ['best', '#7c3aed'],
                            default        => ['', '#ea580c'],
                        };
                        // Every feature this plan actually includes, from the
                        // same rows the plan itself is checked against.
                        $feats = $plan->features
                            ->where('is_included', true)
                            ->sortBy('sort_order')
                            ->pluck('feature')
                            ->values();
                    @endphp
                    <div class="pr-plan {{ $tone[0] }}">
                        @if($plan->badge_text)
                            <span class="pr-badge" style="background: {{ $tone[1] }};">{{ $plan->badge_text }}</span>
                        @endif

                        <h3>{{ $plan->name }}</h3>
                        @if($plan->description)
                            <p class="blurb">{{ $plan->description }}</p>
                        @endif

                        <div class="pr-price">
                            {{ $plan->isFree() ? '$0.00' : '$' . number_format((float) $plan->price, 2) }}<span>/month</span>
                        </div>

                        @if($rate !== null)
                            <div class="pr-share">
                                <b>{{ rtrim(rtrim(number_format($rate, 2), '0'), '.') }}%</b>
                                <span>of each completed job</span>
                            </div>
                        @endif

                        {{-- The first seven, then the rest behind a summary.
                             Nineteen lines of plan detail is not something
                             somebody reads while comparing three of them. --}}
                        <ul class="pr-feats">
                            @foreach($feats->take(7) as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                        @if($feats->count() > 7)
                            <details style="margin: -8px 0 14px;">
                                <summary class="pr-more">{{ $feats->count() - 7 }} more to know</summary>
                                <ul class="pr-feats" style="margin-top: 9px;">
                                    @foreach($feats->skip(7) as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif

                        <a class="pr-cta" style="color: {{ $tone[1] }};"
                           href="{{ auth()->check() ? url('/membership') : route('register') }}">
                            {{ $plan->isFree() ? 'Get started' : 'Choose ' . $plan->name }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Founders are deliberately absent. Peter, 20 October: "Before finalizing
         the founders' section, confirm exactly what their benefits are ... This
         will prevent usman from implementing unconfirmed pricing rules." Three
         different versions of that rule have been written down this month, so
         the section waits for his. --}}

    <div class="pr-notes">
        {{-- The refund rule applies to every figure above it, and Peter asked
             for it "everywhere". --}}
        <div class="pr-policy">
            <b>No refunds or credits</b>
            <p>{{ \App\Domain\Money\FeeSchedule::refundPolicy() }}</p>
        </div>
        <div class="pr-plain">
            <b>Card processing and tax</b>
            <p>
                Card processing is charged by our payment provider and is shown as its own line where it
                applies. Any sales tax is shown separately as well. You see the full total before you pay.
            </p>
        </div>
    </div>
</div>
@endsection
