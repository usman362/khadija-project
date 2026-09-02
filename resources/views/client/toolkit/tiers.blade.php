@extends($layout)

@section('title', 'Toolkit Tiers')
@section('page-title', 'Toolkit Tiers')
@section('page-subtitle', 'What each tier unlocks.')

@php
    $money = fn ($n) => '$' . number_format($n, 2);
    $tierOrder = ['manual', 'semi', 'maximum'];
@endphp

@push('styles')
<style>
    .tk { --tk: #ea580c; --tk-soft: #fff7ed; }
    .tk-head { margin-bottom: 20px; }
    .tk-head h2 { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 24px; font-weight: 800; color: var(--text-primary); margin: 0 0 6px; }
    .tk-pill { font-size: 11px; font-weight: 800; letter-spacing: .3px; color: var(--tk); background: var(--tk-soft); border: 1px solid #fed7aa; border-radius: 999px; padding: 4px 11px; }
    .tk-head p { font-size: 13.5px; color: var(--text-muted); margin: 0; }

    .tk-note { display: flex; gap: 10px; align-items: flex-start; border-radius: 12px; padding: 12px 16px; font-size: 13px; line-height: 1.55; margin-bottom: 18px;
               background: rgba(16,163,74,.09); border: 1px solid rgba(16,163,74,.3); color: var(--ok-text); }
    .tk-note b { font-weight: 800; }
    .tk-note span.sub { color: var(--text-muted); }

    /* The three cards */
    .tk-cards { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 16px; align-items: start; }
    .tk-card { position: relative; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px 18px; display: flex; flex-direction: column; }
    .tk-card.rec { border-color: var(--tk); box-shadow: 0 0 0 1px var(--tk) inset; }
    .tk-rec { position: absolute; top: -11px; left: 50%; transform: translateX(-50%); font-size: 10px; font-weight: 800; letter-spacing: .4px; color: #fff; background: var(--tk); border-radius: 999px; padding: 4px 12px; white-space: nowrap; }
    .tk-card .ic { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 17px; background: var(--bg-card-hover, rgba(120,120,120,.09)); margin-bottom: 10px; }
    .tk-card.rec .ic, .tk-card.top .ic { background: var(--tk); color: #fff; }
    .tk-card h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin: 0; letter-spacing: .3px; }
    .tk-card .sub { font-size: 12px; color: var(--text-muted); margin-bottom: 14px; }
    .tk-price { font-size: 30px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .tk-price.free { color: var(--text-primary); }
    .tk-terms { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .tk-unlocked { font-size: 12.5px; font-weight: 700; color: var(--text-primary); margin: 10px 0 12px; }

    .tk-includes { font-size: 11.5px; font-weight: 800; color: var(--tk); margin-bottom: 9px; }
    .tk-mini { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 8px; margin-bottom: 16px; }
    .tk-mini div { text-align: center; border: 1px solid var(--border-color); border-radius: 9px; padding: 8px 4px; }
    .tk-mini .e { font-size: 14px; }
    .tk-mini span { display: block; font-size: 9.5px; color: var(--text-muted); line-height: 1.3; margin-top: 3px; overflow-wrap: anywhere; }
    .tk-empty { border: 1px dashed var(--border-color); border-radius: 11px; padding: 14px; font-size: 12px; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px; }
    .tk-empty b { display: block; color: var(--text-primary); font-weight: 800; margin-bottom: 3px; }

    .tk-cta { display: block; width: 100%; margin-top: auto; text-align: center; border-radius: 11px; padding: 11px; font-size: 14px; font-weight: 800; text-decoration: none; border: 1px solid var(--tk); color: var(--tk); background: transparent; font-family: inherit; }
    .tk-cta.solid { background: var(--tk); color: #fff; }
    .tk-cta.off { border-color: var(--border-color); color: var(--text-muted); cursor: not-allowed; }
    .tk-foot { font-size: 11px; color: var(--text-muted); text-align: center; margin-top: 9px; }

    /* Upgrade bar */
    .tk-upgrade { display: flex; gap: 12px; align-items: flex-start; background: var(--tk-soft); border: 1px solid #fed7aa; border-radius: 13px; padding: 14px 16px; margin: 16px 0 22px; font-size: 13px; color: #7c2d12; }
    .tk-upgrade b { font-weight: 800; }
    .tk-upgrade span { display: block; font-size: 12px; color: #9a3412; margin-top: 2px; }

    /* Comparison */
    .tk-comp { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; }
    .tk-comp-h { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; }
    .tk-comp-h h3 { font-size: 17px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .tk-legend { display: inline-flex; gap: 16px; font-size: 12px; color: var(--text-muted); }
    .tk-legend b { color: var(--ok-text); }
    .tk-scroll { overflow-x: auto; }
    .tk-table { width: 100%; border-collapse: collapse; min-width: 700px; font-size: 13px; }
    .tk-table th { text-align: left; font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); font-weight: 800; padding: 0 12px 10px; }
    .tk-table th.tier { text-align: center; width: 116px; }
    .tk-table th.tier small { display: block; font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: none; letter-spacing: 0; }
    .tk-table td { padding: 11px 12px; border-top: 1px solid var(--border-color); vertical-align: top; }
    .tk-table td.mark { text-align: center; }
    .tk-suite td { background: var(--bg-card-hover, rgba(120,120,120,.05)); }
    .tk-suite b { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .tk-suite span { display: block; font-size: 11px; color: var(--text-muted); margin-top: 2px; }
    .tk-tool b { display: block; font-weight: 700; color: var(--text-primary); }
    .tk-tool span { font-size: 12px; color: var(--text-muted); line-height: 1.45; }
    .tk-yes { color: var(--ok-text); font-weight: 800; }
    .tk-no { color: var(--text-muted); }

    /* Assurances */
    .tk-assure { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 12px; margin-top: 18px; }
    .tk-a { display: flex; gap: 9px; align-items: flex-start; border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; }
    .tk-a .e { font-size: 15px; }
    .tk-a b { display: block; font-size: 12px; font-weight: 800; color: var(--text-primary); }
    .tk-a span { display: block; font-size: 11px; color: var(--text-muted); line-height: 1.4; }

    .tk-help { display: flex; gap: 12px; align-items: center; justify-content: space-between; flex-wrap: wrap; background: var(--tk-soft); border: 1px solid #fed7aa; border-radius: 13px; padding: 14px 16px; margin-top: 16px; }
    .tk-help b { font-size: 13px; font-weight: 800; color: #7c2d12; }
    .tk-help span { display: block; font-size: 12px; color: #9a3412; }
    .tk-help a { border: 1px solid var(--tk); color: var(--tk); background: var(--bg-card); border-radius: 10px; padding: 9px 16px; font-size: 12.5px; font-weight: 800; text-decoration: none; white-space: nowrap; }
    .tk-small { font-size: 11.5px; color: var(--text-muted); margin-top: 14px; line-height: 1.5; }

    @media (max-width: 1000px) { .tk-cards { grid-template-columns: 1fr; } .tk-assure { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 560px) { .tk-assure { grid-template-columns: 1fr; } .tk-mini { grid-template-columns: repeat(3, minmax(0,1fr)); } }
</style>
@endpush

@section('content')
<div class="tk">
    <div class="tk-head">
        <h2>Choose Your Toolkit Power <span class="tk-pill">ONE-TIME PURCHASE</span></h2>
        <p>Unlock the right tools to plan, hire and manage your event with confidence.</p>
    </div>

    @if($everythingUnlocked)
        {{-- The prices below are decided (R31) but there is no checkout behind
             them yet, and the launch flag currently unlocks every tool for
             every account. Saying so is the difference between a price list and
             a button that pretends to take money. --}}
        <div class="tk-note">
            <span>✅</span>
            <span>
                <b>Every tool is already unlocked on your account.</b>
                <span class="sub">The toolkit is open to everyone during launch. The tiers below are what it will cost once it goes on sale.</span>
            </span>
        </div>
    @endif

    <div class="tk-cards">
        @foreach($cards as $card)
            @php
                $isRec = $card['key'] === $recommended;
                $isTop = $card['key'] === 'maximum';
            @endphp
            <div class="tk-card {{ $isRec ? 'rec' : '' }} {{ $isTop ? 'top' : '' }}">
                @if($isRec)<span class="tk-rec">RECOMMENDED</span>@endif

                <span class="ic">{{ $card['key'] === 'manual' ? '👤' : ($isRec ? '🚀' : '👑') }}</span>
                <h3>{{ Str::upper($card['label']) }}</h3>
                <div class="sub">{{ $card['tagline'] }}</div>

                @if($card['price'] > 0)
                    <div class="tk-price">{{ $money($card['price']) }}</div>
                    <div class="tk-terms">one-time payment</div>
                @else
                    <div class="tk-price free">FREE</div>
                    <div class="tk-terms">included with your account</div>
                @endif

                <div class="tk-unlocked">{{ $card['unlocked'] }} / {{ $card['total'] }} tools unlocked</div>

                @if($card['adds']->isEmpty())
                    {{-- Manual is a preset: always nothing, on both sides.
                         Twelve "not included" rows would only make it look
                         broken. --}}
                    <div class="tk-empty">
                        <b>Manual includes no tools.</b>
                        Choose Semi or Maximum to unlock the toolkit.
                    </div>
                @else
                    <div class="tk-includes">
                        {{ $card['key'] === 'maximum'
                            ? 'Everything in Semi, plus ' . $card['adds']->count() . ' more'
                            : 'Includes ' . $card['adds']->count() . ' essential tools' }}
                    </div>
                    <div class="tk-mini">
                        @foreach($card['adds'] as $tool)
                            <div><span class="e">🧰</span><span>{{ $tool['name'] }}</span></div>
                        @endforeach
                    </div>
                @endif

                @if($card['key'] === 'manual')
                    <span class="tk-cta off">Included with your account</span>
                @elseif($everythingUnlocked)
                    {{-- Checked BEFORE membership. While the launch flag is on
                         this person can open every tool right now, and telling
                         them the tier is "not offered on your membership" would
                         contradict the banner at the top of the same page. --}}
                    <a class="tk-cta {{ $isRec || $isTop ? 'solid' : '' }}" href="{{ route('ai-tools.index') }}">Open the tools</a>
                    <div class="tk-foot">Already unlocked during launch</div>
                @elseif(! $card['purchasable'])
                    {{-- Elite is offered Maximum only; Starter membership is
                         Manual only. --}}
                    <span class="tk-cta off">Not offered on your membership</span>
                @else
                    {{-- No checkout exists for the toolkit yet. Rather than a
                         button that goes nowhere, this asks the team to set it
                         up — which is a thing that actually happens. --}}
                    <a class="tk-cta {{ $isRec || $isTop ? 'solid' : '' }}"
                       href="{{ \App\Domain\Forms\FormRegistry::url('support_request') }}">{{ $isTop ? 'Unlock Maximum' : 'Choose ' . $card['label'] }}</a>
                    <div class="tk-foot">One-time payment · No monthly fees</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="tk-upgrade">
        <span>🔄</span>
        <span>
            <b>Semi {{ $money($cards->firstWhere('key', 'semi')['price'] ?? 0) }} → Maximum? Pay only the {{ $money($difference) }} difference.</b>
            <span>Upgrade anytime and we credit your Semi payment toward Maximum.</span>
        </span>
    </div>

    <div class="tk-comp">
        <div class="tk-comp-h">
            <h3>Compare Your Toolkit Access</h3>
            <span class="tk-legend"><span><b>✓</b> Included</span><span><span class="tk-no">—</span> Not included</span></span>
        </div>

        <div class="tk-scroll">
            <table class="tk-table">
                <thead>
                    <tr>
                        <th scope="col">Tool</th>
                        @foreach($tierOrder as $tier)
                            <th scope="col" class="tier">
                                {{ Str::upper($tiers[$tier] ?? $tier) }}
                                <small>{{ ($cards->firstWhere('key', $tier)['price'] ?? 0) > 0 ? $money($cards->firstWhere('key', $tier)['price']) : 'Free' }}</small>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    {{-- Grouped by the suite each tool belongs to, read from
                         AiToolCatalog. A tool's suite is a fact about the tool;
                         a second list of it here would drift from the hub. --}}
                    @foreach($suites as $suite)
                        <tr class="tk-suite">
                            <td colspan="{{ count($tierOrder) + 1 }}">
                                <b>{{ $suite['emoji'] }} {{ $suite['name'] }}</b>
                                <span>{{ $suite['tagline'] }}</span>
                            </td>
                        </tr>
                        @foreach($suite['tools'] as $tool)
                            <tr>
                                <td class="tk-tool">
                                    <b>{{ $tool['name'] }}</b>
                                    @if($tool['purpose'])<span>{{ $tool['purpose'] }}</span>@endif
                                </td>
                                @foreach($tierOrder as $tier)
                                    <td class="mark">
                                        @if($tool['tiers'][$tier] ?? false)
                                            <span class="tk-yes" title="Included">✓</span>
                                        @else
                                            <span class="tk-no" title="Not included">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="tk-assure">
            <div class="tk-a"><span class="e">🔒</span><span><b>One-Time Payment</b><span>Pay once. Access stays with your account while it is open.</span></span></div>
            <div class="tk-a"><span class="e">🛡</span><span><b>No Monthly Fees</b><span>No recurring toolkit subscription.</span></span></div>
            <div class="tk-a"><span class="e">↗</span><span><b>Upgrade Anytime</b><span>Pay only the difference when you upgrade.</span></span></div>
            <div class="tk-a"><span class="e">⚡</span><span><b>Instant Access</b><span>Tools unlock immediately after purchase.</span></span></div>
            <div class="tk-a"><span class="e">🎧</span><span><b>Help When You Need It</b><span>Support is here when you need it.</span></span></div>
        </div>

        <div class="tk-help">
            <span>
                <b>Not sure which level is right for you?</b>
                <span>Start with Semi for the essentials. Upgrade to Maximum anytime for the full set.</span>
            </span>
            <a href="{{ \App\Domain\Forms\FormRegistry::url('support_request') }}">Need help deciding?</a>
        </div>

        <p class="tk-small">
            All toolkit purchases are one-time payments. There are no monthly fees, and toolkit access stays
            with your account while it is open. Upgrading from Semi to Maximum pays the difference.
        </p>
    </div>
</div>
@endsection
