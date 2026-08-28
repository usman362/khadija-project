@extends('layouts.landing')

@section('title', 'Compare Packages — GigResource')
@section('meta_description', 'Put up to three service packages side by side — price, what is included, coverage, guests and service area.')

@push('styles')
<style>
    .cmp { --pk: var(--orange, #f97316); --pk-dark: #ea580c; --pk-soft: #fff4ec; background: var(--bg-soft); }
    .cmp-shell { max-width: 1180px; margin: 0 auto; padding: 30px 22px 70px; }
    .cmp-head { margin-bottom: 20px; }
    .cmp-note { border: 1px solid rgba(245,158,11,.4); background: rgba(245,158,11,.07); border-radius: 11px;
                padding: 12px 15px; margin-bottom: 18px; }
    .cmp-note b { display: block; font-size: 13.5px; color: var(--text, #1f2937); margin-bottom: 4px; }
    .cmp-note span { font-size: 12.5px; color: var(--muted, #6b7280); line-height: 1.6; }
    .cmp-note a { color: var(--brand-text, #ea580c); font-weight: 700; text-decoration: none; }
    .cmp-head h1 { font-size: clamp(1.5rem, 3vw, 2.1rem); margin: 0 0 6px; }
    .cmp-head p { color: var(--muted); font-size: 14.5px; margin: 0; }
    .cmp-back { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 800; color: var(--pk-dark); margin-bottom: 14px; }

    .cmp-scroll { overflow-x: auto; background: #fff; border: 1px solid var(--line); border-radius: 18px; }
    .cmp-table { width: 100%; border-collapse: collapse; min-width: 720px; }
    .cmp-table th, .cmp-table td { padding: 14px 16px; text-align: left; vertical-align: top; border-bottom: 1px solid var(--line-soft); }
    .cmp-table tr:last-child th, .cmp-table tr:last-child td { border-bottom: none; }
    .cmp-table th.row { width: 168px; font-size: 12px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; color: var(--muted); background: var(--bg-soft); }
    .cmp-table td { font-size: 13px; color: var(--ink-2); }

    .cmp-hero img { width: 100%; height: 132px; object-fit: cover; border-radius: 11px; margin-bottom: 10px; }
    .cmp-title { font-size: 15px; font-weight: 800; color: var(--ink); line-height: 1.3; }
    .cmp-pro { font-size: 12px; color: var(--muted); margin-top: 3px; }
    .cmp-price { font-family: var(--ff); font-size: 22px; font-weight: 800; color: var(--ink); }
    .cmp-price small { display: block; font-size: 11px; font-weight: 700; color: var(--muted); }
    .cmp-saving { font-family: var(--ff); font-size: 12px; font-weight: 800; color: var(--green-onwhite); margin-top: 4px; }
    .cmp-best { display: inline-block; font-size: 10px; font-weight: 800; letter-spacing: .3px; color: var(--pk-dark); background: var(--pk-soft); border: 1px solid #fed7aa; padding: 2px 8px; border-radius: 6px; margin-top: 6px; }
    .cmp-yes { color: var(--green-onwhite); font-weight: 800; }
    .cmp-no { color: #94a3b8; }
    .cmp-list { margin: 0; padding-left: 17px; }
    .cmp-list li { margin-bottom: 4px; line-height: 1.45; }
    .cmp-btn { display: block; text-align: center; background: var(--pk); color: #fff; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 800; }
    .cmp-btn:hover { background: var(--pk-dark); }

    .cmp-empty { background: #fff; border: 1px dashed var(--line); border-radius: 18px; padding: 56px 20px; text-align: center; }
    .cmp-empty h2 { font-size: 18px; margin: 8px 0 6px; }
    .cmp-empty p { color: var(--muted); margin: 0 0 18px; }
    .cmp-empty a { display: inline-flex; background: var(--pk); color: #fff; border-radius: 11px; padding: 11px 22px; font-weight: 800; }
</style>
@endpush

@section('content')
<div class="cmp">
    <div class="cmp-shell">
        <a class="cmp-back" href="{{ route('public.packages') }}">← Back to packages</a>

        @if($packages->isEmpty())
            <div class="cmp-empty">
                <div style="font-size:38px;">⚖️</div>
                <h2>Nothing to compare yet</h2>
                <p>Tick “Compare” on up to {{ $compareMax }} packages, then come back here.</p>
                <a href="{{ route('public.packages') }}">Browse packages</a>
            </div>
        @else
            @php
                // "Best value" is the lowest total on this screen — a fact about
                // the three packages in front of the reader, not a rating.
                $cheapest = $packages->min('price');
                // The union of every service any of them includes, so a package
                // that lacks one shows a real gap rather than a shorter list.
                $allServices = $packages->flatMap(fn ($p) => $p->services ?: [])->unique()->values();
            @endphp

            <div class="cmp-head">
                <h1>Compare {{ $packages->count() }} Package{{ $packages->count() === 1 ? '' : 's' }}</h1>
                <p>Side by side — price, what is included, coverage, guests and where the professional works.</p>
            </div>

            {{-- Say where the missing ones went.
                 A client ticked three and the page said "Compare 1 Package"
                 with no explanation — the other two were dropped by the
                 same-state rule. The comparison was working; the page was
                 keeping a secret, and it read as a broken feature. --}}
            @if(($missing ?? 0) > 0)
                <div class="cmp-note">
                    <b>{{ $missing }} of the {{ $askedFor }} you picked {{ $missing === 1 ? 'is' : 'are' }} not shown here.</b>
                    <span>
                        {{ $missing === 1 ? 'It is' : 'They are' }} offered in another state, and GigResource works
                        within one state for now — so {{ $missing === 1 ? 'it is not' : 'they are not' }} something
                        you can book. <a href="{{ route('public.packages') }}">Back to packages</a>
                    </span>
                </div>
            @endif

            <div class="cmp-scroll">
                <table class="cmp-table">
                    <tbody>
                        <tr>
                            <th class="row" scope="row">Package</th>
                            @foreach($packages as $p)
                                <td class="cmp-hero">
                                    <img src="{{ $p->heroUrls(1)[0] ?? $p->fallbackHeroUrl(420) }}" alt="{{ $p->title }}" loading="lazy">
                                    <div class="cmp-title">{{ $p->title }}</div>
                                    <div class="cmp-pro">by {{ $p->user?->profile?->company_name ?: $p->user?->name }}</div>
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <th class="row" scope="row">Total price</th>
                            @foreach($packages as $p)
                                <td>
                                    <div class="cmp-price">${{ number_format($p->price) }}<small>Total package</small></div>
                                    @if($p->savings_pct)<div class="cmp-saving">Save up to {{ $p->savings_pct }}%</div>@endif
                                    @if($packages->count() > 1 && $p->price === $cheapest)<span class="cmp-best">LOWEST TOTAL HERE</span>@endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <th class="row" scope="row">Coverage</th>
                            @foreach($packages as $p)<td>{{ $p->coverage ?: $p->duration ?: '—' }}</td>@endforeach
                        </tr>
                        <tr>
                            <th class="row" scope="row">Guests</th>
                            @foreach($packages as $p)<td>{{ $p->guests ?: '—' }}</td>@endforeach
                        </tr>
                        <tr>
                            <th class="row" scope="row">Service area</th>
                            @foreach($packages as $p)
                                <td>{{ trim(($p->user?->profile?->city ? $p->user->profile->city . ', ' : '') . ($p->user?->profile?->state ?: $p->state ?: ''), ', ') ?: '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <th class="row" scope="row">Availability</th>
                            @foreach($packages as $p)<td>{{ $p->availability ?: '—' }}</td>@endforeach
                        </tr>

                        {{-- One row per service any of them offers, so the gaps line
                             up. This is the whole point of the screen. --}}
                        @foreach($allServices as $svc)
                            <tr>
                                <th class="row" scope="row">{{ $svc }}</th>
                                @foreach($packages as $p)
                                    <td>
                                        @if(in_array($svc, $p->services ?: [], true))
                                            <span class="cmp-yes">✓ Included</span>
                                        @else
                                            <span class="cmp-no">— Not included</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        @if($packages->contains(fn ($p) => ! empty($p->includes)))
                            <tr>
                                <th class="row" scope="row">Also includes</th>
                                @foreach($packages as $p)
                                    <td>
                                        @if(! empty($p->includes))
                                            <ul class="cmp-list">
                                                @foreach(array_slice($p->includes, 0, 6) as $inc)<li>{{ is_array($inc) ? ($inc['label'] ?? reset($inc)) : $inc }}</li>@endforeach
                                            </ul>
                                        @else — @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endif

                        <tr>
                            <th class="row" scope="row"></th>
                            @foreach($packages as $p)
                                <td><a class="cmp-btn" href="{{ route('public.package', $p->slug) }}">View Package</a></td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
