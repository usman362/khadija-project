@extends('layouts.landing')

@section('title', 'Plan your ' . $category->name . ' — GigResource')

@push('styles')
<style>
    .etl-hero { background: linear-gradient(120deg, #fff7ed, #ffedd5); padding: 34px 0 40px; }
    .etl-chip { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 800;
        letter-spacing: .4px; text-transform: uppercase; color: #9a3412; background: #ffedd5;
        border: 1px solid #fdba74; border-radius: 999px; padding: 4px 11px; margin-bottom: 12px; }
    .etl-h1 { font-size: clamp(28px, 4.4vw, 44px); font-weight: 800; color: #111827; margin: 0 0 10px; line-height: 1.12; }
    .etl-h1 .b { color: #ea580c; }
    .etl-sub { font-size: 15px; color: #4b5563; line-height: 1.6; max-width: 620px; margin: 0; }

    .etl-main { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 26px; align-items: start; padding: 30px 0 50px; }
    .etl-sec-h { font-size: 19px; font-weight: 800; color: #111827; margin: 0 0 4px; }
    .etl-sec-p { font-size: 13.5px; color: #6b7280; margin: 0 0 16px; }

    .etl-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    .etl-card { position: relative; border: 1.5px solid #e5e7eb; border-radius: 14px; padding: 15px 14px 14px;
        background: #fff; cursor: pointer; display: block; }
    .etl-card:hover { border-color: #fdba74; }
    .etl-card input { position: absolute; opacity: 0; pointer-events: none; }
    .etl-card.sel { border-color: #ea580c; background: #fff7ed; }
    .etl-card-name { font-size: 14px; font-weight: 800; color: #111827; margin-bottom: 5px; padding-right: 26px; }
    .etl-card-blurb { font-size: 12px; color: #6b7280; line-height: 1.45; min-height: 34px; }
    .etl-box { position: absolute; top: 13px; right: 13px; width: 19px; height: 19px; border-radius: 6px;
        border: 1.6px solid #d1d5db; background: #fff; }
    .etl-card.sel .etl-box { background: #ea580c; border-color: #ea580c; }
    .etl-card.sel .etl-box::after { content: '✓'; color: #fff; font-size: 12px; font-weight: 800;
        display: block; text-align: center; line-height: 17px; }
    .etl-tier { display: inline-block; font-size: 9.5px; font-weight: 800; letter-spacing: .3px;
        text-transform: uppercase; padding: 2px 7px; border-radius: 999px; margin-bottom: 8px; }
    .etl-tier.Essential  { background: #dcfce7; color: #15803d; }
    .etl-tier.Common     { background: #e0e7ff; color: #3730a3; }
    .etl-tier.Occasional { background: #f3f4f6; color: #4b5563; }

    .etl-rail { position: sticky; top: 90px; border: 1.5px solid #e5e7eb; border-radius: 16px; padding: 18px; background: #fff; }
    .etl-rail h4 { font-size: 15px; font-weight: 800; color: #111827; margin: 0 0 3px; }
    .etl-count { font-size: 13px; font-weight: 800; color: #ea580c; margin-bottom: 4px; }
    .etl-hint { font-size: 12px; color: #4b5563; line-height: 1.5; background: #f9fafb;
        border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; margin: 12px 0; }
    .etl-hint b { color: #111827; }
    .etl-btn { display: block; width: 100%; text-align: center; border: none; border-radius: 11px;
        padding: 12px 16px; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .etl-btn-primary { background: #ea580c; color: #fff; }
    .etl-btn-primary:disabled { background: #fed7aa; color: #9a3412; cursor: not-allowed; }
    .etl-btn-ghost { background: #fff; border: 1.5px solid #e5e7eb; color: #374151; margin-top: 9px; text-decoration: none; }
    .etl-btn:focus-visible, .etl-card:focus-within { outline: 3px solid #9a3412; outline-offset: 2px; }

    .etl-pros { padding: 0 0 50px; }
    .etl-pro-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    .etl-pro { border: 1.5px solid #e5e7eb; border-radius: 14px; padding: 14px; background: #fff;
        display: flex; gap: 12px; align-items: center; text-decoration: none; }
    .etl-pro img { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .etl-pro .nm { font-size: 14px; font-weight: 800; color: #111827; }
    .etl-pro .sub { font-size: 12px; color: #6b7280; }
    .etl-empty { border: 1.5px dashed #e5e7eb; border-radius: 14px; padding: 34px; text-align: center; color: #6b7280; font-size: 13.5px; }

    @media (max-width: 1080px) { .etl-main { grid-template-columns: 1fr; } .etl-rail { position: static; } .etl-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 820px)  { .etl-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .etl-pro-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<section class="etl-hero">
    <div class="lp-container">
        <nav class="etl-crumb" style="font-size:12.5px;color:#6b7280;margin-bottom:14px;">
            <a href="{{ route('landing') }}" style="color:#6b7280;">Home</a>
            <span>›</span>
            <a href="{{ route('public.event-types') }}" style="color:#ea580c;font-weight:600;">Event Types</a>
            <span>›</span>
            <span style="color:#111827;">{{ $category->name }}</span>
        </nav>

        <span class="etl-chip">● Event Type</span>
        <h1 class="etl-h1">Plan Your <span class="b">{{ $category->name }}</span></h1>
        <p class="etl-sub">
            Choose the services you need for your {{ $category->name }}.
            Pick one or several to start building your request.
        </p>
    </div>
</section>

<div class="lp-container">
    <form method="POST" action="{{ route('client.bsr.from-event-type') }}" id="etlForm">
        @csrf
        <input type="hidden" name="event_type" value="{{ $category->name }}">

        <div class="etl-main">
            <div>
                <h2 class="etl-sec-h">Choose services for your {{ $category->name }}</h2>
                {{-- The order is the Category Masterlist's own: this occasion's
                     archetype ranks each service category Essential, Common or
                     Occasional. Everything stays on the page — a tier is a
                     ranking, not a permission. --}}
                <p class="etl-sec-p">Most relevant first, based on what this kind of event usually needs.</p>

                <div class="etl-grid">
                    @foreach($services as $svc)
                        <label class="etl-card" data-etl-card>
                            <input type="checkbox" name="categories[]" value="{{ $svc['id'] }}">
                            <span class="etl-box"></span>
                            @if($svc['tier'])<span class="etl-tier {{ $svc['tier'] }}">{{ $svc['tier'] }}</span>@endif
                            <div class="etl-card-name">{{ $svc['name'] }}</div>
                            <div class="etl-card-blurb">{{ \Illuminate\Support\Str::limit($svc['blurb'] ?? '', 72) }}</div>
                        </label>
                    @endforeach
                </div>
            </div>

            <aside class="etl-rail">
                <h4>Your {{ $category->name }}</h4>
                <div class="etl-count" data-etl-count>0 services selected</div>
                <p style="font-size:12.5px;color:#6b7280;margin:0;">Select services on the left to get started.</p>

                {{-- One service or several is not a different product, it is the
                     scope of the same request. The wording changes so the client
                     knows what they are about to post before they post it. --}}
                <div class="etl-hint" data-etl-hint hidden></div>

                <button type="submit" class="etl-btn etl-btn-primary" data-etl-go disabled>
                    Continue to your request →
                </button>
                <a href="{{ route('public.browse') }}" class="etl-btn etl-btn-ghost">Browse professionals instead</a>
            </aside>
        </div>
    </form>

    <section class="etl-pros">
        <h2 class="etl-sec-h">Professionals for your {{ $category->name }}</h2>
        <p class="etl-sec-p">People you can hire in your area.</p>

        @if($featured->isNotEmpty())
            <div class="etl-pro-grid">
                @foreach($featured as $pro)
                    <a class="etl-pro" href="{{ route('public.professional.show', $pro) }}">
                        <img src="{{ $pro->avatar_url ?? \App\Models\User::placeholderAvatarUri() }}" alt="" loading="lazy">
                        <div>
                            <div class="nm">{{ $pro->name }}</div>
                            <div class="sub">
                                {{ \Illuminate\Support\Str::limit($pro->profile?->headline ?? 'Event professional', 34) }}
                                @if($pro->reviews_avg) · ★ {{ number_format($pro->reviews_avg, 1) }}@endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            {{-- Says why, rather than looking broken. Under Rule R38 a signed-in
                 client only ever sees professionals in their own state. --}}
            <div class="etl-empty">
                No professionals to show here yet. You can still choose your services and post the request —
                professionals are notified when it goes out.
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form  = document.getElementById('etlForm');
    if (!form) return;

    var count = form.querySelector('[data-etl-count]');
    var hint  = form.querySelector('[data-etl-hint]');
    var go    = form.querySelector('[data-etl-go]');

    function refresh() {
        var picked = form.querySelectorAll('[data-etl-card] input:checked').length;

        form.querySelectorAll('[data-etl-card]').forEach(function (card) {
            card.classList.toggle('sel', card.querySelector('input').checked);
        });

        count.textContent = picked + (picked === 1 ? ' service selected' : ' services selected');
        go.disabled = picked === 0;

        if (picked === 0) { hint.hidden = true; return; }

        hint.hidden = false;
        hint.innerHTML = picked === 1
            ? 'One service — this posts as a <b>single-service request</b>.'
            : picked + ' services — this posts as a <b>multi-service request</b>. Professionals bid per service.';
    }

    form.querySelectorAll('[data-etl-card] input').forEach(function (cb) {
        cb.addEventListener('change', refresh);
    });

    refresh();
})();
</script>
@endpush
