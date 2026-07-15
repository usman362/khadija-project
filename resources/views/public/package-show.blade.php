@extends('layouts.landing')

@php
    $pro     = $package->user;
    $profile = $pro?->profile;
    $seoTitle = $package->title . ' — ' . ($pro?->name ?? 'GigResource');
    $seoDescription = \Illuminate\Support\Str::limit(strip_tags((string) $package->description), 155)
        ?: ('Book ' . $package->title . ' on GigResource.');
    $hero  = $package->heroUrls(4);
    $rating = $pro?->reviews_avg ? number_format($pro->reviews_avg, 1) : null;
@endphp

@push('styles')
<style>
    .pk-wrap { background: var(--bg-soft); }
    .pk-container { max-width: 1080px; margin: 0 auto; padding: 26px 24px 60px; }
    .pk-crumb { font-size: 13px; color: var(--muted); margin-bottom: 16px; }
    .pk-crumb a { color: var(--muted); text-decoration: none; }
    .pk-crumb a:hover { color: var(--blue); }
    .pk-hero { position: relative; border-radius: 18px; overflow: hidden; aspect-ratio: 16/7; background: linear-gradient(135deg,#e2e8f0,#eef2ff); margin-bottom: 24px; }
    .pk-hero img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity .5s; }
    .pk-hero img.on { opacity: 1; }
    .pk-hero-cat { position: absolute; top: 14px; left: 14px; background: rgba(255,255,255,.94); color: #0f1b35; font-size: 12px; font-weight: 800; padding: 6px 13px; border-radius: 999px; }
    .pk-grid { display: grid; grid-template-columns: 1fr 340px; gap: 28px; align-items: start; }
    .pk-eyebrow { font-size: 12px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; color: var(--blue); }
    .pk-title { font-size: 2rem; font-weight: 900; color: var(--ink); line-height: 1.12; margin: 8px 0 14px; letter-spacing: -.02em; }
    .pk-pro { display: flex; align-items: center; gap: 12px; padding: 14px 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); margin-bottom: 20px; }
    .pk-pro img { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; }
    .pk-pro-name { font-weight: 800; color: var(--ink); }
    .pk-pro-meta { font-size: 13px; color: var(--muted); }
    .pk-star { color: #f59e0b; font-weight: 800; }
    .pk-desc { color: var(--ink-2); line-height: 1.75; font-size: 1rem; white-space: pre-line; }
    .pk-inc { list-style: none; padding: 0; margin: 18px 0 0; display: grid; gap: 10px; }
    .pk-inc li { display: flex; gap: 10px; align-items: flex-start; color: var(--ink-2); font-size: 14.5px; }
    .pk-inc svg { width: 18px; height: 18px; color: #16a34a; flex-shrink: 0; margin-top: 1px; }
    .pk-card { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 22px; box-shadow: 0 12px 30px -18px rgba(15,27,53,.4); position: sticky; top: 90px; }
    .pk-price { font-size: 2rem; font-weight: 900; color: var(--ink); }
    .pk-price small { font-size: 13px; font-weight: 600; color: var(--muted); display: block; margin-top: 2px; }
    .pk-cta { display: block; text-align: center; margin-top: 16px; padding: 13px; border-radius: 12px; font-weight: 800; text-decoration: none; font-size: 15px; }
    .pk-cta-primary { background: linear-gradient(135deg, var(--blue), #6366f1); color: #fff; box-shadow: 0 12px 26px -12px rgba(99,102,241,.5); }
    .pk-cta-ghost { border: 1px solid var(--line); color: var(--ink); margin-top: 10px; }
    .pk-trust { display: flex; flex-direction: column; gap: 8px; margin-top: 16px; font-size: 13px; color: var(--muted); }
    .pk-trust span { display: flex; align-items: center; gap: 8px; }
    .pk-trust svg { width: 15px; height: 15px; color: #16a34a; }
    .pk-more { margin-top: 40px; }
    .pk-more h2 { font-size: 1.35rem; font-weight: 800; color: var(--ink); margin-bottom: 16px; }
    .pk-more-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px,1fr)); gap: 16px; }
    .pk-more-card { background: #fff; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; text-decoration: none; color: inherit; }
    .pk-more-card .m-img { height: 130px; background: linear-gradient(135deg,#e2e8f0,#eef2ff); }
    .pk-more-card .m-img img { width: 100%; height: 100%; object-fit: cover; }
    .pk-more-card .m-body { padding: 12px 14px; }
    .pk-more-card .m-title { font-weight: 800; color: var(--ink); font-size: 14px; }
    .pk-more-card .m-price { color: var(--blue); font-weight: 800; font-size: 14px; margin-top: 3px; }
    @media (max-width: 820px){ .pk-grid { grid-template-columns: 1fr; } .pk-card { position: static; } }
</style>
@endpush

@section('content')
<div class="pk-wrap">
    <div class="pk-container">
        <nav class="pk-crumb">
            <a href="{{ route('landing') }}">Home</a> ›
            <a href="{{ route('public.packages') }}">Packages</a> ›
            <span>{{ $package->title }}</span>
        </nav>

        <div class="pk-hero">
            @forelse($hero as $i => $img)
                <img class="pk-hero-el {{ $i === 0 ? 'on' : '' }}" src="{{ $img }}" alt="{{ $package->title }}" loading="lazy">
            @empty
                <img class="on" src="https://images.unsplash.com/photo-1519741497674-611481863552?w=1200&q=75&auto=format&fit=crop" alt="{{ $package->title }}">
            @endforelse
            @if($package->category)<span class="pk-hero-cat">{{ $package->category->name }}</span>@endif
        </div>

        <div class="pk-grid">
            <div>
                <span class="pk-eyebrow">{{ $package->type === 'co-op' ? 'Co-op Package' : 'Service Package' }}</span>
                <h1 class="pk-title">{{ $package->title }}</h1>

                <div class="pk-pro">
                    <img src="{{ $pro?->avatar_url }}" alt="{{ $pro?->name }}">
                    <div>
                        <div class="pk-pro-name">{{ $profile?->company_name ?: $pro?->name }}</div>
                        <div class="pk-pro-meta">
                            @if($rating)<span class="pk-star">★ {{ $rating }}</span> ({{ $pro->reviews_count }}) · @endif
                            {{ $profile?->city ?? 'Location on request' }}
                        </div>
                    </div>
                </div>

                @if($package->description)
                    <div class="pk-desc">{{ $package->description }}</div>
                @endif

                @if(!empty($package->includes))
                    <h2 style="font-size:1.15rem;font-weight:800;color:var(--ink);margin:26px 0 4px;">What's included</h2>
                    <ul class="pk-inc">
                        @foreach($package->includes as $item)
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <aside>
                <div class="pk-card">
                    <div class="pk-price">{{ $package->priceLabel() }}
                        <small>{{ $package->duration ? $package->duration : 'Package price' }}</small>
                    </div>
                    <a href="{{ auth()->check() ? route('client.chat.index') : route('login') }}" class="pk-cta pk-cta-primary">Request this Package</a>
                    <a href="{{ route('public.professional.show', $pro) }}" class="pk-cta pk-cta-ghost">View {{ \Illuminate\Support\Str::limit($pro?->name, 18) }}'s profile</a>
                    <div class="pk-trust">
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Secure, protected payment</span>
                        @if($pro?->isVerified())
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Verified professional</span>
                        @endif
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Message before you book</span>
                    </div>
                </div>
            </aside>
        </div>

        @if($more->isNotEmpty())
            <div class="pk-more">
                <h2>More packages you may like</h2>
                <div class="pk-more-grid">
                    @foreach($more as $m)
                        @php $mh = $m->heroUrls(1)[0] ?? null; @endphp
                        <a href="{{ route('public.package', $m->slug) }}" class="pk-more-card">
                            <div class="m-img">@if($mh)<img src="{{ $mh }}" alt="{{ $m->title }}" loading="lazy">@endif</div>
                            <div class="m-body">
                                <div class="m-title">{{ \Illuminate\Support\Str::limit($m->title, 42) }}</div>
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
    (function () {
        var imgs = document.querySelectorAll('.pk-hero .pk-hero-el');
        if (imgs.length < 2) return;
        var i = 0;
        setInterval(function () {
            imgs[i].classList.remove('on');
            i = (i + 1) % imgs.length;
            imgs[i].classList.add('on');
        }, 3500);
    })();
</script>
@endsection
