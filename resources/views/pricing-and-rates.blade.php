@extends('layouts.landing')

@section('title', 'Pricing and Rates')

@push('styles')
<style>
    .pr-head { max-width: 760px; margin: 0 auto 28px; text-align: center; }
    .pr-head h1 { font-size: 34px; font-weight: 800; color: var(--ink); margin: 0 0 10px; line-height: 1.15; }
    .pr-head p { font-size: 15px; color: var(--muted); margin: 0; line-height: 1.6; }

    .pr-sec { max-width: 860px; margin: 0 auto 26px; }
    .pr-sec h2 { font-size: 18px; font-weight: 800; color: var(--ink); margin: 0 0 4px; }
    .pr-sec > p { font-size: 13.5px; color: var(--muted); margin: 0 0 14px; }

    .pr-table { width: 100%; border-collapse: collapse; background: #fff;
        border: 1px solid var(--line-soft); border-radius: 14px; overflow: hidden; }
    .pr-table th { text-align: left; font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .5px; color: var(--muted); padding: 11px 16px; background: #fafafa;
        border-bottom: 1px solid var(--line-soft); }
    .pr-table td { padding: 13px 16px; border-bottom: 1px solid var(--line-soft); vertical-align: top; }
    .pr-table tr:last-child td { border-bottom: none; }
    .pr-amt { font-size: 15px; font-weight: 800; color: var(--ink); white-space: nowrap; }
    .pr-what { font-size: 14px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 2px; }
    .pr-when { font-size: 12.5px; color: var(--muted); line-height: 1.55; }
    .pr-note { font-size: 12px; color: var(--brand, #ea580c); font-weight: 600; margin-top: 3px; display: block; }

    .pr-plans td:first-child { font-weight: 700; color: var(--ink); }

    .pr-policy { max-width: 860px; margin: 0 auto 34px; border: 1px solid #fca5a5;
        background: #fef2f2; border-radius: 13px; padding: 15px 18px; }
    .pr-policy b { display: block; font-size: 14px; color: #991b1b; margin-bottom: 3px; }
    .pr-policy p { font-size: 13px; color: #7f1d1d; margin: 0; line-height: 1.6; }

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

    {{-- The refund rule sits above the tables, not under them. It applies to
         every row, and Peter asked for it "everywhere". --}}
    <div class="pr-policy">
        <b>No refunds or credits</b>
        <p>{{ \App\Domain\Money\FeeSchedule::refundPolicy() }}</p>
    </div>

    <div class="pr-sec">
        <h2>If you are hiring</h2>
        <p>What a client pays.</p>
        <table class="pr-table">
            <thead><tr><th>Charge</th><th>When it applies</th><th>Amount</th></tr></thead>
            <tbody>
                @foreach($clientFees as $fee)
                    <tr>
                        <td>
                            <span class="pr-what">{{ $fee['label'] }}</span>
                            <span class="pr-when" style="display:none;">{{ $fee['when'] }}</span>
                        </td>
                        <td>
                            <span class="pr-when">{{ $fee['when'] }}</span>
                            @if($fee['note'])<span class="pr-note">{{ $fee['note'] }}</span>@endif
                        </td>
                        <td class="pr-amt">{{ $fee['formatted'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pr-sec">
        <h2>If you are offering services</h2>
        <p>What a professional or influencer pays on a package. Your membership share is separate and shown below.</p>
        <table class="pr-table">
            <thead><tr><th>Charge</th><th>When it applies</th><th>Amount</th></tr></thead>
            <tbody>
                @foreach($providerFees as $fee)
                    <tr>
                        <td><span class="pr-what">{{ $fee['label'] }}</span></td>
                        <td>
                            <span class="pr-when">{{ $fee['when'] }}</span>
                            @if($fee['note'])<span class="pr-note">{{ $fee['note'] }}</span>@endif
                        </td>
                        <td class="pr-amt">{{ $fee['formatted'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($plans->isNotEmpty())
        <div class="pr-sec">
            <h2>Membership</h2>
            <p>Starter is free. The share is taken only when a job is completed and paid.</p>
            <table class="pr-table pr-plans">
                <thead><tr><th>Plan</th><th>Share of completed jobs</th><th>Monthly</th></tr></thead>
                <tbody>
                    @foreach($plans as $plan)
                        <tr>
                            <td>{{ $plan->name }}</td>
                            <td><span class="pr-when">{{ rtrim(rtrim(number_format($ladder[$plan->slug]['rate'] ?? 0, 2), '0'), '.') }}% of each completed job</span></td>
                            <td class="pr-amt">{{ $plan->isFree() ? 'Free' : '$' . number_format((float) $plan->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Founders are deliberately absent. Peter, 20 October: "Before finalizing
         the founders' section, confirm exactly what their benefits are ... This
         will prevent usman from implementing unconfirmed pricing rules." Three
         different versions of that rule have been written down this month, so
         the section waits for his. --}}

    <div class="pr-sec">
        <h2>Card processing and tax</h2>
        <p>
            Card processing is charged by our payment provider and is shown as its own line where it
            applies. Any sales tax is shown separately as well. You see the full total before you pay.
        </p>
    </div>
</div>
@endsection
