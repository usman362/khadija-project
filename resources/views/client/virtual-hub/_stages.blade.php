@props(['current' => 1])

{{-- The seven-stage strip from the client's Virtual & Hybrid mockup.
     Wayfinding, not data: it tells the client where they are in a journey that
     runs across several screens. The stage it marks is derived from the event's
     real bookings, so it cannot say "Event day" for an event nobody booked. --}}
@php
    /*
     * Where each stage can be reached, when it can be reached at all.
     *
     * Entry, Plan/Services and Hire are places you can always go. The last
     * three are states an event passes through, so they only lead somewhere
     * once an event exists — and Event day is not a page you can visit early.
     * A stage with nowhere to go renders as plain text rather than a link that
     * does nothing, which is what left "how do I get back to Entry?" with no
     * answer on this strip.
     */
    /*
     * Each stage opens its own panel on the hub -- these are tabs, not jumps
     * to other screens. Only Plan and Services leave, because the brief is a
     * form of its own. Stages 5-7 describe an event, so without one they are
     * not offered: a tab that opens an empty panel teaches nothing.
     */
    $hub  = fn (int $n) => route('client.virtual-hub.index', ['stage' => $n]);
    $has  = (bool) ($event ?? null);

    $stages = [
        1 => ['Entry',           'Choose what you want to do.',            $hub(1)],
        2 => ['Plan',            'Tell us about your event.',              route('client.virtual-hub.brief')],
        3 => ['Services',        'Select the services you need.',          route('client.virtual-hub.brief')],
        4 => ['Hire',            'Find and hire the right professionals.', $hub(4)],
        5 => ['Event workspace', 'Manage everything in one place.',        $has ? $hub(5) : null],
        6 => ['Event day',       'Run your event with confidence.',        $has ? $hub(6) : null],
        7 => ['Complete',        'Close the loop and wrap up.',            $has ? $hub(7) : null],
    ];
@endphp

<div class="vhs">
    <div class="vhs-head">
        <div class="vhs-title">Virtual &amp; Hybrid events</div>
        <div class="vhs-flow">Plan → Hire → Prepare → Run event → Complete</div>
        {{-- This marks the SCREEN you are on, not the state of your event.
             It used to mark the event's stage, so clicking Entry landed you
             here and the strip answered "You are here: Hire" — two different
             questions fighting over one marker. Your event's own progress is
             in the workspace panel, where it belongs. --}}
        <div class="vhs-note">Pick a step to see just that part. Nothing else moves.</div>
    </div>

    <ol class="vhs-row">
        @foreach($stages as $n => [$name, $blurb, $url])
            <li class="vhs-item {{ $n === $current ? 'is-now' : ($n < $current ? 'is-done' : '') }} {{ $url ? 'is-link' : '' }}">
                @if($url)
                    <a href="{{ $url }}" class="vhs-hit" aria-label="Go to {{ $name }}"></a>
                @endif
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
    .vhs-note{flex-basis:100%;font-size:11.5px;color:var(--text-muted);margin-top:2px;}
    .vhs-row{display:grid;grid-template-columns:repeat(7,1fr);gap:8px;list-style:none;margin:0;padding:0;}
    @media(max-width:1100px){.vhs-row{grid-template-columns:repeat(4,1fr);}}
    @media(max-width:640px){.vhs-row{grid-template-columns:repeat(2,1fr);}}
    .vhs-item{position:relative;padding:10px 10px 12px;border:1px solid var(--border-color);border-radius:11px;}
    .vhs-item.is-link:hover{border-color:var(--accent-blue);}
    .vhs-hit{position:absolute;inset:0;border-radius:11px;}
    .vhs-hit:focus-visible{outline:2px solid var(--accent-blue);outline-offset:2px;}
    .vhs-item.is-now{border-color:var(--accent-orange,#f97316);background:rgba(249,115,22,.07);}
    .vhs-item.is-done{opacity:.72;}
    .vhs-num{width:19px;height:19px;border-radius:50%;display:flex;align-items:center;justify-content:center;
        font-size:10.5px;font-weight:700;background:transparent;border:1.5px solid var(--border-color);
        color:var(--text-muted);margin-bottom:6px;}
    .vhs-item.is-now .vhs-num{background:var(--accent-orange,#f97316);border-color:transparent;color:#fff;}
    .vhs-item.is-done .vhs-num{background:transparent;border-color:#16a34a;color:#16a34a;}
    .vhs-name{font-size:12.5px;font-weight:700;line-height:1.25;}
    .vhs-blurb{font-size:11px;color:var(--text-muted);line-height:1.35;margin-top:3px;}
    .vhs-here{font-size:9.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;
        color:var(--accent-orange,#f97316);margin-top:6px;}
</style>
