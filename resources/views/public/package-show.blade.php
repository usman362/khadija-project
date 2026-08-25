@extends('layouts.landing')

@php
    $pro     = $package->user;
    $profile = $pro?->profile;
    $seoTitle = $package->title . ' — ' . ($pro?->name ?? 'GigResource');
    $seoDescription = \Illuminate\Support\Str::limit(strip_tags((string) $package->description), 155)
        ?: ('Book ' . $package->title . ' on GigResource.');

    $shots  = $package->heroUrls(12);
    $ownArt = ! empty($shots);
    if (! $ownArt) { $shots = [$package->fallbackHeroUrl(1200)]; }

    $rating   = $pro?->reviews_avg ? number_format($pro->reviews_avg, 1) : null;
    $area     = trim(($profile?->city ? $profile->city . ', ' : '') . ($profile?->state ?: $package->state ?: ''), ', ');
    $services = $package->services ?: [];
    $occasions = $package->event_types ?: [];
@endphp

@push('styles')
<style>
    .pk-wrap { background: var(--bg-soft); }
    .pk-container { max-width: 1240px; margin: 0 auto; padding: 22px 24px 60px; }
    .pk-crumb { font-size: 12.5px; color: var(--muted); margin-bottom: 14px; display: flex; gap: 7px; flex-wrap: wrap; }
    .pk-crumb a { color: var(--muted); }
    .pk-crumb a:hover { color: var(--blue); }
    .pk-crumb span.sep { color: var(--line); }

    .pk-grid { display: grid; grid-template-columns: minmax(0,1fr) 358px; gap: 26px; align-items: start; }

    /* Gallery */
    .pk-gal { position: relative; border-radius: 16px; overflow: hidden; aspect-ratio: 16/9; background: linear-gradient(135deg,#e2e8f0,#eef2ff); }
    .pk-gal img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity .35s; }
    .pk-gal img.on { opacity: 1; }
    .pk-gal-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 38px; height: 38px; border-radius: 50%; border: none; background: rgba(255,255,255,.94); color: #111827; font-size: 20px; line-height: 1; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,.22); z-index: 2; }
    .pk-gal-nav.prev { left: 12px; } .pk-gal-nav.next { right: 12px; }
    .pk-gal-tag { position: absolute; top: 13px; left: 13px; background: rgba(0,0,0,.6); color: #fff; font-size: 11.5px; font-weight: 700; padding: 5px 11px; border-radius: 8px; z-index: 2; }
    .pk-gal-count { position: absolute; top: 13px; right: 13px; background: rgba(0,0,0,.6); color: #fff; font-size: 11.5px; font-weight: 700; padding: 5px 11px; border-radius: 8px; z-index: 2; }
    .pk-thumbs { display: grid; grid-template-columns: repeat(auto-fill, minmax(88px,1fr)); gap: 8px; margin-top: 8px; }
    .pk-thumb { border: none; padding: 0; border-radius: 10px; overflow: hidden; aspect-ratio: 4/3; cursor: pointer; background: #eef2ff; }
    .pk-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pk-thumb.on { outline: 2px solid var(--orange); outline-offset: 1px; }
    .pk-stock { font-size: 11.5px; color: var(--faint); margin-top: 7px; }

    /* Head */
    .pk-title { font-size: clamp(1.6rem, 3vw, 2.1rem); margin: 20px 0 10px; }
    .pk-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
    .pk-chip { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--line); border-radius: 999px; padding: 5px 13px; font-size: 12.5px; font-weight: 600; color: var(--ink-2); background: #fff; }

    /* Professional */
    .pk-pro { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 16px; align-items: center; padding: 16px 0; border-top: 1px solid var(--line); }
    .pk-pro-who { display: flex; gap: 12px; align-items: center; }
    .pk-avatar { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background: var(--bg-soft-2); display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--blue); }
    .pk-pro-name { font-size: 16px; font-weight: 800; color: var(--ink); display: flex; align-items: center; gap: 6px; }
    .pk-pro-role { font-size: 12.5px; color: var(--muted); }
    .pk-pro-link { font-size: 12.5px; font-weight: 800; color: var(--blue); }
    .pk-biz { display: flex; gap: 12px; align-items: center; border: 1px solid var(--line); border-radius: 13px; padding: 12px 14px; background: #fff; }
    .pk-biz .mark { width: 42px; height: 42px; border-radius: 11px; background: var(--orange); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; flex-shrink: 0; }
    .pk-biz b { display: block; font-size: 14px; color: var(--ink); }
    .pk-biz span { font-size: 12px; color: var(--muted); }

    .pk-stats { display: flex; gap: 18px; flex-wrap: wrap; align-items: center; padding: 14px 0; border-top: 1px solid var(--line); font-size: 13px; color: var(--muted); font-weight: 600; }
    .pk-stats .star { color: #b45309; font-weight: 800; font-family: var(--ff); }
    .pk-stats .dot { color: var(--line); }
    .pk-badges { display: flex; gap: 18px; flex-wrap: wrap; padding: 0 0 16px; border-bottom: 1px solid var(--line); font-size: 12.5px; }
    .pk-badge { display: inline-flex; align-items: center; gap: 6px; font-weight: 700; }
    .pk-badge.yes { color: var(--green-onwhite); }
    .pk-badge.no { color: var(--faint); font-weight: 600; }

    .pk-purpose { font-size: 1.02rem; color: var(--ink-2); line-height: 1.6; margin: 18px 0 0; font-weight: 600; }
    .pk-desc { color: var(--ink-2); line-height: 1.75; font-size: 15px; white-space: pre-line; margin-top: 10px; }

    .pk-sec { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 20px 22px; margin-top: 20px; }
    .pk-sec h2 { font-size: 1.1rem; margin: 0 0 4px; }
    .pk-sec p.lede { font-size: 13px; color: var(--muted); margin: 0 0 14px; }
    .pk-inc { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap: 10px 20px; }
    .pk-inc li { display: flex; gap: 9px; align-items: flex-start; color: var(--ink-2); font-size: 14px; line-height: 1.5; }
    .pk-inc svg { width: 17px; height: 17px; color: var(--green-onwhite); flex-shrink: 0; margin-top: 2px; }

    /* Right rail */
    .pk-rail { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 88px; }
    .pk-card { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 18px; box-shadow: 0 12px 30px -22px rgba(15,27,53,.4); }
    .pk-card h3 { font-size: 15px; margin: 0 0 12px; }
    .pk-avail { display: flex; gap: 10px; align-items: flex-start; border-radius: 11px; padding: 11px 13px; font-size: 13px; line-height: 1.5; margin-bottom: 14px; }
    .pk-avail.free { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
    .pk-avail.busy { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
    .pk-avail.ask  { background: var(--bg-soft); border: 1px solid var(--line); color: var(--muted); }
    .pk-avail b { display: block; font-weight: 800; }
    .pk-f { margin-bottom: 12px; }
    .pk-f label { display: block; font-size: 12px; font-weight: 700; color: var(--ink-2); margin-bottom: 5px; }
    .pk-f input, .pk-f select { width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 10px 12px; font-size: 13.5px; font-family: inherit; color: var(--ink); background: #fff; }
    .pk-cta { display: block; width: 100%; text-align: center; padding: 12px; border-radius: 11px; font-weight: 800; font-size: 14.5px; border: none; cursor: pointer; font-family: inherit; }
    .pk-cta-primary { background: var(--orange); color: #fff; }
    .pk-cta-primary:hover { background: var(--orange-dark); }
    .pk-cta-ghost { border: 1px solid var(--line); color: var(--ink); background: #fff; margin-top: 9px; }
    .pk-mini { display: flex; gap: 8px; margin-top: 10px; }
    .pk-mini > * { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid var(--line); border-radius: 10px; padding: 8px; font-size: 12.5px; font-weight: 700; color: var(--ink-2); background: #fff; cursor: pointer; font-family: inherit; }
    .pk-mini .on { border-color: #dc2626; color: #dc2626; }

    .pk-snap dt { font-size: 12.5px; color: var(--muted); }
    .pk-snap dd { margin: 0; font-size: 13px; font-weight: 700; color: var(--ink); text-align: right; }
    .pk-snap .row { display: flex; justify-content: space-between; gap: 14px; padding: 9px 0; border-top: 1px solid var(--line-soft); }
    .pk-snap .row:first-child { border-top: none; }

    .pk-note { display: flex; gap: 9px; align-items: flex-start; background: var(--bg-soft-2); border: 1px solid var(--line); border-radius: 12px; padding: 12px 15px; font-size: 12.5px; color: var(--muted); line-height: 1.55; margin-top: 20px; }

    .pk-more { margin-top: 34px; }
    .pk-more h2 { font-size: 1.25rem; margin-bottom: 14px; }
    .pk-more-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px,1fr)); gap: 14px; }
    .pk-more-card { background: #fff; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; display: block; }
    .pk-more-card .m-img { height: 124px; background: linear-gradient(135deg,#e2e8f0,#eef2ff); }
    .pk-more-card .m-img img { width: 100%; height: 100%; object-fit: cover; }
    .pk-more-card .m-body { padding: 11px 13px; }
    .pk-more-card .m-title { font-weight: 800; color: var(--ink); font-size: 13.5px; }
    .pk-more-card .m-price { color: var(--orange-dark); font-weight: 800; font-size: 13.5px; margin-top: 3px; font-family: var(--ff); }

    .pk-previewbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; background: #fff7ed; border: 1.5px solid #fdba74; color: #7c2d12; border-radius: 12px; padding: 12px 16px; margin: 0 0 16px; font-size: 13px; }
    .pk-previewbar b { font-weight: 800; }
    .pk-previewbar a { margin-left: auto; font-weight: 800; color: #9a3412; white-space: nowrap; }

    @media (max-width: 1000px) { .pk-grid { grid-template-columns: 1fr; } .pk-rail { position: static; } .pk-pro { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="pk-wrap">
    <div class="pk-container">
        @if($preview ?? false)
            <div class="pk-previewbar">
                <b>Preview — this package is not live.</b>
                <span>This is exactly what a client will see once you publish it. Nobody else can open this page.</span>
                <a href="{{ route('professional.packages.index') }}">Back to My Packages →</a>
            </div>
        @endif

        {{-- The category path, so the reader can climb back out the way they
             came in rather than only to the top. --}}
        <nav class="pk-crumb">
            <a href="{{ route('landing') }}">Home</a><span class="sep">›</span>
            @if($package->category?->parent)
                <a href="{{ route('public.category', $package->category->parent->slug) }}">{{ $package->category->parent->name }}</a><span class="sep">›</span>
            @endif
            @if($package->category)
                <a href="{{ route('public.category', $package->category->slug) }}">{{ $package->category->name }}</a><span class="sep">›</span>
            @else
                <a href="{{ route('public.packages') }}">Packages</a><span class="sep">›</span>
            @endif
            <span>{{ $package->title }}</span>
        </nav>

        <div class="pk-grid">
            <div>
                {{-- Gallery --}}
                <div class="pk-gal" id="pkGal">
                    @foreach($shots as $i => $src)
                        <img class="pk-shot {{ $i === 0 ? 'on' : '' }}" src="{{ $src }}" alt="{{ $package->title }}" @if($i) loading="lazy" @endif>
                    @endforeach
                    @if($package->category)<span class="pk-gal-tag">{{ $package->category->name }}</span>@endif
                    @if($ownArt)<span class="pk-gal-count">{{ count($shots) }} {{ \Illuminate\Support\Str::plural('Photo', count($shots)) }}</span>@endif
                    @if(count($shots) > 1)
                        <button class="pk-gal-nav prev" type="button" onclick="pkShot(-1)" aria-label="Previous photo">‹</button>
                        <button class="pk-gal-nav next" type="button" onclick="pkShot(1)" aria-label="Next photo">›</button>
                    @endif
                </div>

                @if(count($shots) > 1)
                    <div class="pk-thumbs">
                        @foreach($shots as $i => $src)
                            <button type="button" class="pk-thumb {{ $i === 0 ? 'on' : '' }}" onclick="pkGo({{ $i }})" aria-label="Photo {{ $i + 1 }}">
                                <img src="{{ $src }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif

                @unless($ownArt)
                    {{-- Said out loud. A stand-in photograph that reads as this
                         professional's own work is a claim about them. --}}
                    <p class="pk-stock">Stock image — this professional has not uploaded photos for this package yet.</p>
                @endunless

                <h1 class="pk-title">{{ $package->title }}</h1>

                <div class="pk-chips">
                    @foreach(array_slice($services, 0, 4) as $s)<span class="pk-chip">◇ {{ $s }}</span>@endforeach
                    @foreach(array_slice($occasions, 0, 2) as $o)<span class="pk-chip">◷ {{ $o }}</span>@endforeach
                </div>

                {{-- Who it is --}}
                <div class="pk-pro">
                    <div class="pk-pro-who">
                        <span class="pk-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($pro?->name ?? '?', 0, 2)) }}</span>
                        <span>
                            <span class="pk-pro-name">{{ $pro?->name }} @if($pro?->isVerified())<span style="color:var(--green-onwhite);">✔</span>@endif</span>
                            @if($profile?->headline)<span class="pk-pro-role">{{ $profile->headline }}</span>@endif
                            <a class="pk-pro-link" href="{{ route('public.professional.show', $pro) }}">View full profile →</a>
                        </span>
                    </div>

                    @if($profile?->company_name)
                        <div class="pk-biz">
                            <span class="mark">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($profile->company_name, 0, 2)) }}</span>
                            <span>
                                <b>{{ $profile->company_name }}</b>
                                <span>{{ $area ?: 'Service area not set' }}</span>
                            </span>
                        </div>
                    @endif
                </div>

                {{-- What is true about them. Each figure appears only when there
                     is something behind it — a new professional is not "0
                     bookings". And bookings are counted once: the mockup shows
                     both "63 Bookings" and "128 Events Completed", which on this
                     platform is one number read twice. --}}
                <div class="pk-stats">
                    @if($rating)
                        <span><span class="star">★ {{ $rating }}</span> ({{ $pro->reviews_count }} {{ \Illuminate\Support\Str::plural('review', $pro->reviews_count) }})</span>
                    @else
                        <span>New on GigResource</span>
                    @endif
                    @if($bookings)<span class="dot">•</span><span>{{ $bookings }} {{ \Illuminate\Support\Str::plural('booking', $bookings) }} completed</span>@endif
                    @if($responds)<span class="dot">•</span><span>{{ $responds }}</span>@endif
                </div>

                @if(! empty($badges))
                    <div class="pk-badges">
                        @foreach($badges as $b)
                            <span class="pk-badge {{ $b['verified'] ? 'yes' : 'no' }}">
                                {{ $b['verified'] ? '✔' : '○' }} {{ $b['label'] }}{{ $b['verified'] ? ' verified' : ' not verified' }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if($package->purpose)<p class="pk-purpose">{{ $package->purpose }}</p>@endif
                @if($package->description)<div class="pk-desc">{{ $package->description }}</div>@endif

                @if(! empty($package->includes))
                    <div class="pk-sec">
                        <h2>What's included</h2>
                        <p class="lede">Everything below is part of the price.</p>
                        <ul class="pk-inc">
                            @foreach($package->includes as $item)
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    {{ is_array($item) ? ($item['label'] ?? reset($item)) : $item }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="pk-note">
                    <span>ℹ️</span>
                    <span>Money is held by the payment processor until the work is confirmed, so nothing is released before the event is delivered.</span>
                </div>
            </div>

            {{-- ── Right rail ──────────────────────────────────────── --}}
            <aside class="pk-rail">
                <div class="pk-card">
                    <h3>Check availability for your event</h3>

                    {{-- Read from the same calendar My Gigs reads, so this page
                         cannot offer a day the professional's own diary has
                         taken. It answers "is this day already committed ON
                         GigResource" — never "they are free", which nobody can
                         know. --}}
                    @if($checkDate !== '' && $freeOnDate)
                        <div class="pk-avail free">
                            <span>✅</span>
                            <span>
                                <b>Nothing booked on {{ \Illuminate\Support\Carbon::parse($checkDate)->format('M j, Y') }}</b>
                                <span>Their GigResource calendar is clear that day.</span>
                            </span>
                        </div>
                    @elseif($checkDate !== '')
                        <div class="pk-avail busy">
                            <span>⚠️</span>
                            <span>
                                <b>Already committed on {{ \Illuminate\Support\Carbon::parse($checkDate)->format('M j, Y') }}</b>
                                <span>Try another date, or message them.</span>
                            </span>
                        </div>
                    @else
                        <div class="pk-avail ask">
                            <span>📅</span>
                            <span>Pick your date to see whether they are already booked.</span>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('public.package', $package->slug) }}">
                        <div class="pk-f">
                            <label for="pkDate">Event date</label>
                            <input type="date" name="date" id="pkDate" value="{{ $checkDate }}" min="{{ now()->toDateString() }}">
                        </div>
                        <button type="submit" class="pk-cta pk-cta-ghost" style="margin-top:0;">Check this date</button>
                    </form>

                    <div style="margin-top:14px;">
                        <div style="font-size:26px;font-weight:800;color:var(--ink);font-family:var(--ff);">{{ $package->priceLabel() }}</div>
                        {{-- Built in PHP rather than inline @if: "package@if("
                             puts the directive straight after a letter, which
                             Blade does not read as one. --}}
                        @php
                            $priceNote = 'Total package';
                            if ($package->savings_pct) {
                                $priceNote .= ' · save up to ' . $package->savings_pct . '% vs booking separately';
                            }
                        @endphp
                        <div style="font-size:12px;color:var(--muted);margin-bottom:12px;">{{ $priceNote }}</div>

                        {{-- This button used to open the Direct Request form.
                             That is a different product — the client writes a
                             brief and waits for a quote — and the package's own
                             price appeared nowhere in it. A package is already
                             priced and already scoped; buying it is its own
                             path. The owner of the package is sent to their own
                             shelf instead, because nobody books themselves. --}}
                        @php
                            $isOwner = auth()->id() === $package->user_id;
                            $bookUrl = auth()->check()
                                ? route('client.packages.book', array_filter([
                                    'package' => $package->id,
                                    'date'    => $checkDate ?: null,
                                  ]))
                                : route('login');
                        @endphp

                        @if ($isOwner)
                            <a class="pk-cta pk-cta-ghost" href="{{ route('professional.packages.index') }}">This is your package · manage it</a>
                        @else
                            <a class="pk-cta pk-cta-primary" href="{{ $bookUrl }}">Book this package · ${{ number_format((float) $package->price, 0) }}</a>
                            <div style="font-size:11.5px;color:var(--muted);margin-top:7px;text-align:center;line-height:1.5;">
                                You are not charged when you send it — {{ $pro?->name ?? 'the professional' }} confirms the date first.
                            </div>
                        @endif

                        @auth
                            @if(auth()->user()->hasRole('client'))
                                <form method="POST" action="{{ route('public.package.save', $package) }}" style="margin-top:9px;">
                                    @csrf
                                    <button type="submit" class="pk-cta pk-cta-ghost {{ $saved ? 'on' : '' }}" style="margin-top:0;">
                                        {{ $saved ? '♥ Saved' : '♡ Save' }}
                                    </button>
                                </form>
                            @endif
                        @endauth

                        <div class="pk-mini">
                            <a href="{{ route('public.packages.compare', ['ids' => $package->id]) }}">⚖ Compare</a>
                            <a href="{{ \App\Support\Inbox::urlFor() }}">✉ Message</a>
                        </div>
                    </div>
                </div>

                <div class="pk-card">
                    <h3>Package snapshot</h3>
                    <dl class="pk-snap">
                        <div class="row"><dt>Starting price</dt><dd>{{ $package->priceLabel() }}</dd></div>
                        @if($package->coverage || $package->duration)
                            <div class="row"><dt>Coverage</dt><dd>{{ $package->coverage ?: $package->duration }}</dd></div>
                        @endif
                        @if($package->guests)<div class="row"><dt>Guests</dt><dd>{{ $package->guests }}</dd></div>@endif
                        @if($services)<div class="row"><dt>Services</dt><dd>{{ count($services) }} included</dd></div>@endif
                        @if($package->availability)<div class="row"><dt>Availability</dt><dd>{{ $package->availability }}</dd></div>@endif
                        {{-- One state, not a radius. R38: a package is bookable
                             inside its professional's own state, so "within 20
                             miles" would describe a marketplace this one is not. --}}
                        <div class="row"><dt>Service area</dt><dd>{{ $area ?: 'Not set' }}</dd></div>
                        <div class="row"><dt>Payment</dt><dd>Held until confirmed</dd></div>
                    </dl>
                </div>
            </aside>
        </div>

        @if($more->isNotEmpty())
            <div class="pk-more">
                <h2>More packages you may like</h2>
                <div class="pk-more-grid">
                    @foreach($more as $m)
                        @php $mh = $m->heroUrls(1)[0] ?? $m->fallbackHeroUrl(420); @endphp
                        <a href="{{ route('public.package', $m->slug) }}" class="pk-more-card">
                            <div class="m-img"><img src="{{ $mh }}" alt="{{ $m->title }}" loading="lazy"></div>
                            <div class="m-body">
                                <div class="m-title">{{ \Illuminate\Support\Str::limit($m->title, 44) }}</div>
                                <div class="m-price">{{ $m->priceLabel() }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    var pkAt = 0;
    function pkPaint() {
        document.querySelectorAll('#pkGal .pk-shot').forEach(function (el, i) { el.classList.toggle('on', i === pkAt); });
        document.querySelectorAll('.pk-thumb').forEach(function (el, i) { el.classList.toggle('on', i === pkAt); });
    }
    function pkGo(i) { pkAt = i; pkPaint(); }
    function pkShot(dir) {
        var n = document.querySelectorAll('#pkGal .pk-shot').length;
        pkAt = (pkAt + dir + n) % n;
        pkPaint();
    }
</script>
@endsection
