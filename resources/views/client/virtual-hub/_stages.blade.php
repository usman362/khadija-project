@props(['current' => 1])

{{-- The seven-stage strip from the client's Virtual & Hybrid mockup.
     Wayfinding, not data: it tells the client where they are in a journey that
     runs across several screens. The stage it marks is derived from the event's
     real bookings, so it cannot say "Event day" for an event nobody booked. --}}
@php
    $stages = [
        1 => ['Entry',           'Choose what you want to do.'],
        2 => ['Plan',            'Tell us about your event.'],
        3 => ['Services',        'Select the services you need.'],
        4 => ['Hire',            'Find and hire the right professionals.'],
        5 => ['Event workspace', 'Manage everything in one place.'],
        6 => ['Event day',       'Run your event with confidence.'],
        7 => ['Complete',        'Close the loop and wrap up.'],
    ];
@endphp

<div class="vhs">
    <div class="vhs-head">
        <div class="vhs-title">Virtual &amp; Hybrid events</div>
        <div class="vhs-flow">Plan → Hire → Prepare → Run event → Complete</div>
    </div>

    <ol class="vhs-row">
        @foreach($stages as $n => [$name, $blurb])
            <li class="vhs-item {{ $n === $current ? 'is-now' : ($n < $current ? 'is-done' : '') }}">
                <div class="vhs-num">{{ $n < $current ? '✓' : $n }}</div>
                <div class="vhs-name">{{ $name }}</div>
                <div class="vhs-blurb">{{ $blurb }}</div>
                @if($n === $current)<div class="vhs-here">You are here</div>@endif
            </li>
        @endforeach
    </ol>
</div>

<style>
    .vhs{border:1px solid var(--border-color);border-radius:14px;padding:15px 17px 6px;margin-bottom:16px;background:var(--bg-card);}
    .vhs-head{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;margin-bottom:12px;}
    .vhs-title{font-size:14.5px;font-weight:800;}
    .vhs-flow{font-size:12px;color:var(--text-muted);}
    .vhs-row{display:grid;grid-template-columns:repeat(7,1fr);gap:8px;list-style:none;margin:0;padding:0;}
    @media(max-width:1100px){.vhs-row{grid-template-columns:repeat(4,1fr);}}
    @media(max-width:640px){.vhs-row{grid-template-columns:repeat(2,1fr);}}
    .vhs-item{position:relative;padding:10px 10px 12px;border:1px solid var(--border-color);border-radius:11px;}
    .vhs-item.is-now{border-color:var(--accent-orange,#f97316);background:rgba(249,115,22,.07);}
    .vhs-item.is-done{opacity:.72;}
    .vhs-num{width:21px;height:21px;border-radius:50%;display:flex;align-items:center;justify-content:center;
        font-size:11px;font-weight:800;background:var(--border-color);color:var(--text-primary);margin-bottom:6px;}
    .vhs-item.is-now .vhs-num{background:var(--accent-orange,#f97316);color:#fff;}
    .vhs-item.is-done .vhs-num{background:#16a34a;color:#fff;}
    .vhs-name{font-size:12.5px;font-weight:700;line-height:1.25;}
    .vhs-blurb{font-size:11px;color:var(--text-muted);line-height:1.35;margin-top:3px;}
    .vhs-here{font-size:9.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;
        color:var(--accent-orange,#f97316);margin-top:6px;}
</style>
