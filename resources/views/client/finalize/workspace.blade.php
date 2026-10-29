@extends('layouts.client')

@section('title', 'Event Agreement')
@section('page-title', 'Event Agreement')
@section('page-subtitle', 'Work out the terms with this professional before anyone is booked.')

@push('styles')
<style>
    /* Sir Peter's layout, 27 September: client, agreement, professional,
       and the conversation, "all within the same area so they both can be
       directly one on one". */
    .ws { display: grid; grid-template-columns: 210px minmax(0, 1fr) 210px 320px; gap: 16px; align-items: start; }
    .ws-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; }
    .ws-pad { padding: 16px 18px; }

    .ws-who { text-align: center; padding: 20px 16px; }
    .ws-who img { width: 84px; height: 84px; border-radius: 50%; object-fit: cover; display: block; margin: 0 auto 10px; }
    .ws-who b { display: block; font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .ws-role { display: inline-block; margin: 8px 0 6px; padding: 3px 12px; border-radius: 999px; font-size: 11.5px; font-weight: 800; }
    .ws-meta { font-size: 11.5px; color: var(--text-muted); line-height: 1.6; }
    .ws-prof { display: inline-block; margin-top: 12px; padding: 7px 14px; border-radius: 9px; border: 1px solid var(--border-color);
        font-size: 12px; font-weight: 800; color: var(--text-secondary); text-decoration: none; }
    .ws-prof:hover { background: var(--bg-card-hover, #f8fafc); }

    .ws-phase { margin: 0; padding: 9px 16px; font-size: 12px; font-weight: 800; letter-spacing: .01em; }
    /* The three phases keep their hues; the text is mixed against the page's
       own ink so it darkens on white and lightens on the dark shell, where
       these deep values were coming out at 2.5:1. */
    .ws-p1 { background: rgba(37,99,235,.08);  color: color-mix(in srgb, #2563eb 72%, var(--text-primary)); }
    .ws-p2 { background: rgba(16,185,129,.1);  color: color-mix(in srgb, #10b981 72%, var(--text-primary)); }
    .ws-p3 { background: rgba(124,58,237,.09); color: color-mix(in srgb, #7c3aed 72%, var(--text-primary)); }

    .ws-sec { display: flex; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--border-color); }
    .ws-n { flex: none; width: 22px; height: 22px; border-radius: 50%; background: var(--bg-card-hover, #f1f5f9);
        color: var(--text-secondary); font-size: 11.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
    .ws-sec-b { flex: 1; min-width: 0; }
    .ws-sec-t { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .ws-sec-n { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; line-height: 1.5; }
    .ws-rows { margin-top: 8px; }
    .ws-row { display: flex; justify-content: space-between; gap: 14px; padding: 4px 0; font-size: 12.5px; }
    .ws-row span { color: var(--text-muted); }
    .ws-row b { color: var(--text-primary); font-weight: 700; text-align: right; }
    .ws-unset { color: var(--text-muted); font-weight: 600; font-style: italic; }
    .ws-fix { font-size: 11.5px; font-weight: 800; color: var(--brand-text, #c2410c); text-decoration: none; white-space: nowrap; }

    .ws-svc { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 12.5px; }
    .ws-svc th { text-align: left; font-size: 10.5px; text-transform: uppercase; color: var(--text-muted); padding: 0 8px 5px 0; font-weight: 800; }
    .ws-svc td { padding: 6px 8px 6px 0; border-top: 1px solid var(--border-color); color: var(--text-primary); }

    .ws-foot { display: flex; gap: 10px; flex-wrap: wrap; padding: 14px 16px; }
    .ws-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border-color);
        background: var(--bg-card); color: var(--text-secondary); font-family: inherit; font-size: 13px; font-weight: 800; cursor: pointer; text-decoration: none; }
    .ws-btn.go { background: var(--accent-orange, #ea580c); border-color: transparent; color: #fff; margin-left: auto; }
    .ws-btn svg { width: 15px; height: 15px; }

    .ws-msgs { display: flex; flex-direction: column; max-height: 640px; }
    .ws-msgs-h { padding: 13px 16px; border-bottom: 1px solid var(--border-color); font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .ws-msgs-b { flex: 1; overflow-y: auto; padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; }
    .ws-m { font-size: 12.5px; line-height: 1.5; }
    .ws-m-who { font-size: 10.5px; font-weight: 800; color: var(--text-muted); margin-bottom: 3px; }
    .ws-m-body { padding: 8px 11px; border-radius: 11px; background: var(--bg-card-hover, #f1f5f9); color: var(--text-primary); }
    .ws-m.mine .ws-m-body { background: var(--brand-soft, rgba(249,115,22,.1)); }
    .ws-msgs-f { padding: 12px 14px; border-top: 1px solid var(--border-color); }
    .ws-none { font-size: 12.5px; color: var(--text-muted); line-height: 1.6; }

    /* Folding the conversation away.
       This is the one screen a client reads line by line before signing, and
       the chat was taking a third of the width from the terms. Sir Peter asked
       for a way to put it aside while reading. */
    .ws-msgs-h { display: flex; align-items: center; gap: 8px; }
    .ws-fold { margin-left: auto; border: 0; background: none; cursor: pointer; padding: 3px;
        border-radius: 7px; color: var(--text-muted); display: inline-flex; font-family: inherit; }
    .ws-fold:hover { background: var(--bg-card-hover, #f1f5f9); color: var(--text-primary); }
    .ws-fold svg { width: 16px; height: 16px; }

    /* Collapsed: a rail you can still see and still click. Hiding it outright
       would lose the way back, and the unread mark with it. */
    .ws.is-folded { grid-template-columns: 210px minmax(0, 1fr) 210px 46px; }
    .ws.is-folded .ws-msgs { max-height: none; }
    .ws.is-folded .ws-msgs-b, .ws.is-folded .ws-msgs-f { display: none; }
    .ws.is-folded .ws-msgs-h { flex-direction: column; gap: 10px; padding: 12px 6px; border-bottom: 0; }
    .ws-rail { writing-mode: vertical-rl; font-size: 12px; font-weight: 800; letter-spacing: .04em;
        color: var(--text-muted); display: none; }
    .ws.is-folded .ws-rail { display: inline-block; }
    .ws.is-folded .ws-msgs-title { display: none; }
    .ws.is-folded .ws-fold { margin-left: 0; }

    /* Something was said while it was folded away. */
    .ws-newdot { display: none; width: 8px; height: 8px; border-radius: 50%;
        background: var(--accent-orange, #ea580c); }
    .ws-newdot.is-on { display: block; }


    /* ── Edit Agreement ─────────────────────────────────── */
    .ws-dpill { font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 999px;
        background: rgba(245,158,11,.16); color: #b45309; margin-left: 6px; }
    .eg-scrim { position: fixed; inset: 0; background: rgba(15,23,42,.45); z-index: 60; }
    .eg { position: fixed; top: 0; right: 0; bottom: 0; width: min(560px, 100%); z-index: 61;
        background: var(--bg-card); border-left: 1px solid var(--border-color); overflow-y: auto; }
    .eg-form { padding: 20px 22px 24px; }
    .eg-head { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 14px; }
    .eg-head h2 { font-size: 22px; font-weight: 800; color: var(--text-primary); margin: 0;
        border-bottom: 3px solid var(--accent-orange, #ea580c); display: inline-block; padding-bottom: 3px; }
    .eg-head p { font-size: 12.5px; color: var(--text-muted); margin: 8px 0 0; line-height: 1.55; }
    .eg-x { margin-left: auto; border: 0; background: none; font-size: 26px; line-height: 1;
        color: var(--text-muted); cursor: pointer; padding: 0 4px; }

    .eg-note { display: flex; gap: 10px; align-items: flex-start; border-radius: 12px; padding: 12px 14px;
        font-size: 12.5px; line-height: 1.55; color: var(--text-secondary); margin-bottom: 14px;
        background: rgba(37,99,235,.07); border: 1px solid rgba(37,99,235,.2); }
    .eg-note svg { width: 17px; height: 17px; color: var(--info-text, #1d4ed8); flex: none; margin-top: 1px; }
    .eg-note b { color: var(--text-primary); }
    .eg-note.draft { background: rgba(245,158,11,.1); border-color: rgba(245,158,11,.3); }
    .eg-note.bad { background: rgba(220,38,38,.08); border-color: rgba(220,38,38,.3); color: #991b1b; }
    .eg-link { border: 0; background: none; padding: 0; margin-left: auto; font: inherit; font-size: 12.5px;
        font-weight: 700; color: var(--accent-orange, #ea580c); cursor: pointer; white-space: nowrap; }

    .eg-sections { display: flex; flex-direction: column; gap: 9px; margin-bottom: 14px; }
    .eg-sec { border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
    .eg-sec > summary { display: flex; align-items: center; gap: 11px; padding: 12px 14px; cursor: pointer; list-style: none; }
    .eg-sec > summary::-webkit-details-marker { display: none; }
    .eg-num { width: 28px; height: 28px; border-radius: 9px; flex: none; display: flex; align-items: center;
        justify-content: center; font-size: 12.5px; font-weight: 800;
        background: rgba(249,115,22,.12); color: var(--brand-text, #c2410c); }
    .eg-sec-t { flex: 1; min-width: 0; }
    .eg-sec-t b { display: block; font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
    .eg-sec-t span { display: block; font-size: 11.5px; color: var(--text-muted);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .eg-chev { width: 15px; height: 15px; color: var(--text-muted); flex: none; transition: transform .15s; }
    .eg-sec[open] .eg-chev { transform: rotate(180deg); }
    .eg-fields { padding: 2px 14px 14px; display: flex; flex-direction: column; gap: 11px; }

    .eg-field { display: block; }
    .eg-label { display: flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 800;
        color: var(--text-secondary); margin-bottom: 5px; }
    .eg-moved { font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 999px;
        background: rgba(245,158,11,.16); color: #b45309; }
    .eg-field input, .eg-field textarea { width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
        padding: 9px 11px; font: inherit; font-size: 13px; color: var(--text-primary); background: var(--bg-card); }
    .eg-field input:focus, .eg-field textarea:focus { outline: none; border-color: var(--accent-orange, #ea580c); }
    .eg-field.is-moved input, .eg-field.is-moved textarea { border-color: rgba(245,158,11,.6); }
    .eg-err { display: block; font-size: 11.5px; color: #dc2626; margin-top: 4px; }

    .eg-hist { border-top: 1px solid var(--border-color); margin-top: 16px; padding-top: 14px; }
    .eg-hist h3 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin: 0 0 10px; }
    .eg-rev { border: 1px solid var(--border-color); border-radius: 11px; padding: 11px 13px; margin-bottom: 8px; }
    .eg-rev-h { display: flex; flex-wrap: wrap; gap: 8px; align-items: baseline; }
    .eg-rev-h b { font-size: 12.5px; color: var(--text-primary); }
    .eg-rev-h span { font-size: 11.5px; color: var(--text-muted); }
    .eg-rev-l { margin: 7px 0 0; padding-left: 16px; }
    .eg-rev-l li { font-size: 12px; color: var(--text-secondary); line-height: 1.6; }
    .eg-was { color: var(--text-muted); text-decoration: line-through; }
    .eg-now { color: var(--text-primary); font-weight: 600; }
    .eg-rev-s { font-size: 11.5px; color: var(--text-muted); margin: 6px 0 0; }

    .eg-foot { display: flex; gap: 9px; justify-content: flex-end; flex-wrap: wrap;
        border-top: 1px solid var(--border-color); margin-top: 16px; padding-top: 14px; }
    .eg-btn { padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border-color);
        background: var(--bg-card); color: var(--text-primary); font: inherit; font-size: 13px;
        font-weight: 700; cursor: pointer; }
    .eg-btn.go { background: var(--accent-orange, #ea580c); border-color: transparent; color: #fff; }

    @media (max-width: 620px) { .eg-foot .eg-btn { flex: 1; } }

    @media (max-width: 1500px) {
        .ws { grid-template-columns: 180px minmax(0,1fr) 300px; }
        .ws.is-folded { grid-template-columns: 180px minmax(0,1fr) 46px; }
        .ws-pro-col { grid-column: 1 / -1; }
    }
    @media (max-width: 1100px) {
        .ws, .ws.is-folded { grid-template-columns: 1fr; }
        .ws-msgs { max-height: none; }
        /* Stacked, the conversation is below the terms rather than beside
           them, so it is not stealing any width and folding buys nothing. */
        .ws-fold { display: none; }
        .ws.is-folded .ws-msgs-b, .ws.is-folded .ws-msgs-f { display: flex; }
        .ws.is-folded .ws-msgs-h { flex-direction: row; padding: 13px 16px; border-bottom: 1px solid var(--border-color); }
        .ws.is-folded .ws-rail { display: none; }
        .ws.is-folded .ws-msgs-title { display: block; }
    }
</style>
@endpush

@section('content')
@php
    use App\Domain\Agreements\Workspace;
    use App\Support\RoleColours;

    $state = Workspace::status($fin);
    [$sLabel, $sColour, $sMeaning] = Workspace::STATES[$state];

    $svc   = $fin->bid?->category?->name ?? $fin->event?->categories->first()?->name;
    $unset = '<span class="ws-unset">Not set yet</span>';

    // Where a phase is not settled, the link goes to the step that settles it.
    $fix = fn (string $step, string $word = 'Set this') =>
        '<a class="ws-fix" href="' . route('client.finalize.step', [$fin, $step]) . '">' . $word . ' &rsaquo;</a>';
@endphp

@if(session('status'))
    <p style="background:rgba(16,163,74,.12);border:1px solid rgba(16,163,74,.35);color:var(--ok-text);padding:10px 15px;border-radius:11px;font-size:13.5px;margin:0 0 14px;">{{ session('status') }}</p>
@endif

<div class="ws">
    {{-- ── The client ─────────────────────────────────────── --}}
    <aside class="ws-card ws-who">
        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}">
        <b>{{ auth()->user()->name }}</b>
        <span class="ws-role" style="background:{{ RoleColours::tintFor('client') }};color:{{ RoleColours::strongFor('client') }};">Event Host</span>
        <div class="ws-meta">
            Member since {{ auth()->user()->created_at?->format('Y') }}
            @if(auth()->user()->profile?->city)<br>{{ auth()->user()->profile->city }}@endif
        </div>
    </aside>

    {{-- ── The agreement ──────────────────────────────────── --}}
    <section class="ws-card">
        <div class="ws-pad" style="border-bottom:1px solid var(--border-color);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;">
                <div>
                    <div style="font-size:17px;font-weight:800;color:var(--text-primary);">Event Agreement</div>
                    {{-- The event it is for. An agreement that does not name
                         what it is about is a page of figures. --}}
                    <p style="margin:3px 0 0;font-size:13px;font-weight:700;color:var(--text-secondary);">{{ $event?->title ?? 'This event' }}</p>
                    @if($event)<div class="gr-ref" style="margin-top:3px;">{{ $event->reference() }}</div>@endif
                    <p style="margin:2px 0 0;font-size:12.5px;color:var(--text-muted);">Your request and this professional's proposal, in one place.</p>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                    <span class="ws-role" style="margin:0;background:{{ $sColour }}1f;color:{{ $sColour }};">{{ $sLabel }}</span>
                    {{-- Read it away from the screen, or send it to whoever
                         else has to agree. It says "draft" on every page: it
                         is not the signed contract, which comes from the
                         booking once both sides have signed. --}}
                    <a class="ws-btn" href="{{ route('client.finalize.draft', $fin) }}" style="white-space:nowrap;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download agreement draft
                    </a>
                </div>
            </div>
            <p style="margin:10px 0 0;font-size:12.5px;color:var(--text-secondary);">
                {{ $sMeaning }}
                @unless(Workspace::signingOpen($fin)) You are not booked yet, and either side can still step away. @endunless
            </p>
            @if($fin->change_request && $state === Workspace::NEGOTIATING)
                <p style="margin:10px 0 0;padding:10px 13px;border-radius:10px;background:rgba(217,119,6,.1);border:1px solid rgba(217,119,6,.3);font-size:12.5px;line-height:1.55;">
                    <b>Changes asked for{{ $fin->change_requested_by === auth()->id() ? ' by you' : '' }}:</b> {{ $fin->change_request }}
                </p>
            @endif
        </div>

        <p class="ws-phase ws-p1">Phase 1 &middot; Event scope and booking fundamentals</p>

        <div class="ws-sec">
            <span class="ws-n">1</span>
            <div class="ws-sec-b">
                <div class="ws-sec-t">Event details and requirements</div>
                <div class="ws-rows">
                    <div class="ws-row"><span>Start</span><b>{{ $event?->starts_at?->format('M j, Y · g:i A') ?? 'Not set' }}</b></div>
                    <div class="ws-row"><span>End</span><b>{{ $event?->ends_at?->format('M j, Y · g:i A') ?? 'Not specified' }}</b></div>
                    <div class="ws-row"><span>Location</span><b>{{ \App\Domain\Requests\VenueRule::place($event?->location) ?: trim(($event?->city ? $event->city . ', ' : '') . ($event?->state ?? '')) ?: 'Not set' }}</b></div>
                    <div class="ws-row"><span>Guests</span><b>{{ $event?->guest_count ?: 'Not set' }}</b></div>
                </div>
            </div>
        </div>

        <div class="ws-sec">
            <span class="ws-n">2</span>
            <div class="ws-sec-b">
                <div class="ws-sec-t">Service covered by this agreement</div>
                {{-- The document is explicit: "The agreement must not
                     automatically list every service in the event. It should
                     show only the service(s) covered by this specific
                     Client-provider agreement." One agreement per service is
                     how they are already made, so this is that one. --}}
                <div class="ws-sec-n">Only what this professional bid on. Your other services have agreements of their own.</div>
                <table class="ws-svc">
                    <thead><tr><th>Service</th><th>Your budget</th><th>Agreed price</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>{{ $svc ?? 'This service' }}</td>
                            <td>{{ $event?->budgetLabel() ?? 'Not set' }}</td>
                            <td>{!! $fin->agreed_price ? '<b>$' . number_format((float) $fin->agreed_price, 2) . '</b>' : $unset . ' ' . $fix('price') !!}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ws-sec">
            <span class="ws-n">3</span>
            <div class="ws-sec-b">
                <div class="ws-sec-t">Scope and deliverables</div>
                @if($fin->scope)
                    <div class="ws-sec-n" style="white-space:pre-line;">{{ \Illuminate\Support\Str::limit($fin->scope, 320) }}</div>
                @else
                    <div class="ws-sec-n">{!! $unset . ' ' . $fix('scope', 'Confirm the scope') !!}</div>
                @endif
            </div>
        </div>

        <p class="ws-phase ws-p2">Phase 2 &middot; Schedule and money</p>

        <div class="ws-sec">
            <span class="ws-n">4</span>
            <div class="ws-sec-b">
                <div class="ws-sec-t">When the service runs</div>
                <div class="ws-rows">
                    <div class="ws-row"><span>Starts</span><b>{!! $fin->service_start?->format('M j, Y · g:i A') ?? $unset . ' ' . $fix('schedule') !!}</b></div>
                    @if($fin->service_end)
                        <div class="ws-row"><span>Ends</span><b>{{ $fin->service_end->format('M j, Y · g:i A') }}</b></div>
                    @endif
                    <div class="ws-row"><span>Availability</span><b>{{ $fin->bid?->available_confirmed ? 'Confirmed for your date' : 'Not confirmed' }}</b></div>
                </div>
            </div>
        </div>

        <div class="ws-sec">
            <span class="ws-n">5</span>
            <div class="ws-sec-b">
                <div class="ws-sec-t">Deposit and payment terms</div>
                <div class="ws-sec-n">The deposit is the professional's to decide, not yours.</div>
                <div class="ws-rows">
                    <div class="ws-row"><span>Deposit</span><b>{!! $fin->deposit_percent ? $fin->deposit_percent . '% · $' . number_format((float) $fin->deposit_amount, 2) : $unset . ' ' . $fix('terms') !!}</b></div>
                    <div class="ws-row"><span>Balance due</span><b>{{ $fin->balance_due_on?->format('M j, Y') ?? 'Not set' }}</b></div>
                </div>
            </div>
        </div>

        <p class="ws-phase ws-p3">Phase 3 &middot; Approval and signatures</p>

        <div class="ws-sec">
            <span class="ws-n">6</span>
            <div class="ws-sec-b">
                <div class="ws-sec-t">Who has approved these terms</div>
                <div class="ws-sec-n">Signing opens once you have both approved the same version. The agreement is on version {{ Workspace::version($fin) }}.</div>
                <div class="ws-rows">
                    <div class="ws-row"><span>You</span><b>{{ Workspace::clientSigned($fin) ? 'Signed' : (Workspace::clientApproved($fin) ? 'Approved' : 'Not yet') }}</b></div>
                    <div class="ws-row"><span>{{ $pro->name }}</span><b>{{ Workspace::supplierSigned($fin) ? 'Signed' : (Workspace::supplierApproved($fin) ? 'Approved' : 'Not yet') }}</b></div>
                </div>
            </div>
        </div>

        <div class="ws-foot">
            @if($state !== Workspace::DECLINED && ! $fin->funded_at)
                @unless(Workspace::clientApproved($fin))
                    <form method="POST" action="{{ route('client.finalize.approve', $fin) }}">
                        @csrf
                        <button type="submit" class="ws-btn">Accept current terms</button>
                    </form>
                @endunless
                {{-- One button, not two. Sir Peter: "Do not keep both buttons,
                     because they create overlapping actions and unnecessary
                     confusion." --}}
                <button type="button" class="ws-btn" data-ws-edit>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                    Edit Agreement
                    @if($draft)<span class="ws-dpill">Draft</span>@endif
                </button>
            @endif
            <a class="ws-btn go" href="{{ route('client.finalize.step', [$fin, 'bid']) }}">
                Continue to finalize
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </section>

    {{-- ── The professional ───────────────────────────────── --}}
    <aside class="ws-card ws-who ws-pro-col">
        {{-- Whether they are around to answer, where you are waiting on them. --}}
        <span class="has-pres">
            <img src="{{ $pro->avatar_url }}" alt="{{ $pro->name }}">
            <x-presence-dot :user="$pro" size="13" style="right:6px;bottom:9px;" />
        </span>
        <b>{{ $pro->name }}</b>
        @if(\App\Support\Presence::label($pro))
            <div class="ws-meta" style="margin-top:2px;">{{ \App\Support\Presence::label($pro) }}</div>
        @endif
        <span class="ws-role" style="background:{{ RoleColours::tintFor('professional') }};color:{{ RoleColours::strongFor('professional') }};">{{ $svc ?? 'Professional' }}</span>
        <div class="ws-meta">
            Member since {{ $pro->created_at?->format('Y') }}
            @if($pro->profile?->city)<br>{{ $pro->profile->city }}@endif
            @if($pro->reviews_count)<br>{{ number_format((float) $pro->reviews_avg, 1) }} from {{ $pro->reviews_count }} {{ \Illuminate\Support\Str::plural('review', $pro->reviews_count) }}@endif
        </div>
        <a class="ws-prof" href="{{ route('public.professional.show', $pro) }}">View profile</a>
    </aside>

    {{-- ── The conversation, beside the terms ─────────────── --}}
    @php
        // The newest message on the page. The fold remembers which one the
        // reader had in front of them, so "new" means new to them rather than
        // merely unread somewhere.
        $__newest = $messages->last()?->id;
    @endphp
    <aside class="ws-card ws-msgs">
        <div class="ws-msgs-h">
            <span class="ws-msgs-title">Messages</span>
            <span class="ws-rail">Messages</span>
            <span class="ws-newdot" data-ws-newdot title="New messages"></span>
            <button type="button" class="ws-fold" data-ws-fold
                    aria-expanded="true" aria-label="Hide the conversation">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/>
                </svg>
            </button>
        </div>
        <div class="ws-msgs-b">
            @forelse($messages as $m)
                <div class="ws-m {{ $m->sender_id === auth()->id() ? 'mine' : '' }}">
                    <div class="ws-m-who">{{ $m->sender_id === auth()->id() ? 'You' : $m->sender?->name }} &middot; {{ $m->created_at?->format('M j, g:i A') }}</div>
                    <div class="ws-m-body">{{ \Illuminate\Support\Str::limit($m->body, 400) }}</div>
                </div>
            @empty
                <p class="ws-none">Nothing said yet. Ask {{ $pro->name }} anything you need settled before you sign.</p>
            @endforelse
        </div>
        <div class="ws-msgs-f">
            <a class="ws-btn" style="width:100%;justify-content:center;" href="{{ $conversation ? route('client.chat.show', $conversation) : route('client.chat.index') }}">
                {{ $conversation ? 'Open the conversation' : 'Message ' . $pro->name }}
            </a>
        </div>
    </aside>
</div>

{{-- ── Edit Agreement ──────────────────────────────────────
     His panel slides in from the right. Saving keeps the edits to yourself
     and leaves every signature standing; sending is what moves the agreement,
     and therefore what resets them. That split is rule 7. --}}
<div class="eg-scrim" data-eg-scrim hidden></div>
<aside class="eg" id="edit" data-eg hidden aria-label="Edit agreement">
    <form method="POST" action="{{ route('client.finalize.edit', $fin) }}" class="eg-form">
        @csrf
        <div class="eg-head">
            <div>
                <h2>Edit Agreement</h2>
                <p>Make changes to the agreement details. Your changes will be sent to the other party for review and approval.</p>
            </div>
            <button type="button" class="eg-x" data-eg-close aria-label="Close">&times;</button>
        </div>

        @if($errors->has('agreement'))
            <div class="eg-note bad">{{ $errors->first('agreement') }}</div>
        @endif

        <div class="eg-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>
                @if($isAmendment)
                    Both of you have signed. Sending this creates an <b>amendment</b>: the signed version is kept
                    whole, and both of you sign the new one.
                @else
                    This creates a new version of the agreement. The other party must approve the changes
                    before the agreement is updated.
                @endif
            </span>
        </div>

        @if($draft)
            <div class="eg-note draft">
                <span>
                    You have unsent edits, saved {{ $fin->draft_at?->humanAgo() }}.
                    <b>Nobody's signature has changed</b>, because they have not been sent.
                </span>
                <button type="submit" name="intent" value="discard" class="eg-link">Discard them</button>
            </div>
        @endif

        <div class="eg-sections">
            @php $n = 0; @endphp
            @foreach($editGroups as $section => $fields)
                @php $n++; @endphp
                <details class="eg-sec" @if($n === 1) open @endif>
                    <summary>
                        <span class="eg-num">{{ $n }}</span>
                        <span class="eg-sec-t">
                            <b>{{ $section }}</b>
                            <span>{{ collect($fields)->map(fn ($f) => $editFields[$f])->implode(', ') }}</span>
                        </span>
                        <svg class="eg-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </summary>
                    <div class="eg-fields">
                        @foreach($fields as $field)
                            @php
                                $value = $draft[$field] ?? $terms[$field] ?? null;
                                $type  = match ($field) {
                                    'service_start', 'service_end'    => 'datetime-local',
                                    'balance_due_on'                  => 'date',
                                    'agreed_price', 'deposit_percent' => 'number',
                                    default                           => 'text',
                                };
                                $long = in_array($field, ['scope', 'payment_terms', 'schedule_notes'], true);
                                // A term that is one of a fixed set of answers
                                // rather than a value, drawn as a choice.
                                $options = \App\Domain\Agreements\EditAgreement::optionsFor($field);
                                $moved = $draft && array_key_exists($field, $draft)
                                    && trim((string) ($draft[$field] ?? '')) !== trim((string) ($terms[$field] ?? ''));
                            @endphp
                            <label class="eg-field {{ $moved ? 'is-moved' : '' }}">
                                <span class="eg-label">
                                    {{ $editFields[$field] }}
                                    @if($moved)<span class="eg-moved">edited</span>@endif
                                </span>
                                @if($options)
                                    @php $chosen = old('terms.' . $field, $value); @endphp
                                    @foreach($options as $option => $label)
                                        <span style="display:flex;gap:9px;align-items:flex-start;font-size:13px;line-height:1.55;margin-bottom:6px;">
                                            <input type="radio" name="terms[{{ $field }}]" value="{{ $option }}"
                                                   style="margin-top:3px;flex-shrink:0;"
                                                   {{ $chosen === $option ? 'checked' : '' }}>
                                            <span>{{ $label }}</span>
                                        </span>
                                    @endforeach
                                    <span style="display:block;font-size:12px;color:var(--text-muted);line-height:1.55;">
                                        {{ $fin->supplier?->name ?? 'The professional' }} stays responsible for the work
                                        whoever attends.
                                    </span>
                                @elseif($long)
                                    <textarea name="terms[{{ $field }}]" rows="3" maxlength="5000">{{ old('terms.' . $field, $value) }}</textarea>
                                @elseif($type === 'datetime-local')
                                    <input type="datetime-local" name="terms[{{ $field }}]"
                                           value="{{ old('terms.' . $field, $value ? \App\Support\DisplayTimezone::forInput($value) : '') }}">
                                @else
                                    <input type="{{ $type }}" name="terms[{{ $field }}]"
                                           @if($field === 'agreed_price') step="0.01" min="0" @endif
                                           @if($field === 'deposit_percent') min="0" max="100" @endif
                                           value="{{ old('terms.' . $field, $value) }}">
                                @endif
                                @error('terms.' . $field)<span class="eg-err">{{ $message }}</span>@enderror
                            </label>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>

        <label class="eg-field">
            <span class="eg-label">What changed, and why</span>
            <textarea name="summary" rows="2" maxlength="2000"
                      placeholder="A line for the other party, and for the record.">{{ old('summary') }}</textarea>
        </label>

        @if($history->isNotEmpty())
            <div class="eg-hist">
                <h3>Version history</h3>
                @foreach($history as $rev)
                    <div class="eg-rev">
                        <div class="eg-rev-h">
                            <b>Version {{ $rev->version }}</b>
                            <span>{{ $rev->author?->name ?? 'Someone' }} &middot; {{ $rev->created_at?->format('M j, Y g:i A') }}</span>
                        </div>
                        @if($rev->changed)
                            <ul class="eg-rev-l">
                                @foreach($rev->changed as $field => $c)
                                    <li>
                                        <b>{{ $c['label'] ?? $field }}</b>
                                        {{-- Formatted at the point of reading: the stored value
                                             is whatever the revision was given, so a service time
                                             sits in there as a UTC instant beside a wall clock. --}}
                                        <span class="eg-was">{{ \App\Domain\Agreements\EditAgreement::readable($field, $c['from'] ?? null) }}</span>
                                        &rarr;
                                        <span class="eg-now">{{ \App\Domain\Agreements\EditAgreement::readable($field, $c['to'] ?? null) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if($rev->client_signed_at || $rev->supplier_signed_at)
                            <p class="eg-rev-s">
                                Signed on this version:
                                {{ collect([
                                    $rev->client_signed_at ? 'you' : null,
                                    $rev->supplier_signed_at ? $pro->name : null,
                                ])->filter()->implode(' and ') }}.
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="eg-foot">
            <button type="button" class="eg-btn" data-eg-close>Cancel</button>
            <button type="submit" name="intent" value="draft" class="eg-btn">Save as draft</button>
            <button type="submit" name="intent" value="send" class="eg-btn go">
                {{ $isAmendment ? 'Send amendment' : 'Send changes for review' }}
            </button>
        </div>
    </form>
</aside>


@push('scripts')
<script>
(function () {
    var grid = document.querySelector('.ws');
    var btn  = grid && grid.querySelector('[data-ws-fold]');
    if (!grid || !btn) return;

    var dot    = grid.querySelector('[data-ws-newdot]');
    var newest = {{ $__newest ? (int) $__newest : 'null' }};

    var FOLD = 'gr.agreement.msgs.folded';
    var SEEN = 'gr.agreement.msgs.seen';

    // Private-window and blocked-storage both throw. The panel has to work
    // either way; all storage buys here is not asking twice.
    function get(k)    { try { return window.localStorage.getItem(k); } catch (e) { return null; } }
    function set(k, v) { try { window.localStorage.setItem(k, v); } catch (e) {} }

    function markSeen() {
        if (newest !== null) set(SEEN, String(newest));
        if (dot) dot.classList.remove('is-on');
    }

    function showNewIfAny() {
        if (!dot || newest === null) return;
        var seen = parseInt(get(SEEN) || '0', 10);
        dot.classList.toggle('is-on', newest > seen);
    }

    function apply(folded, remember) {
        grid.classList.toggle('is-folded', folded);
        btn.setAttribute('aria-expanded', folded ? 'false' : 'true');
        btn.setAttribute('aria-label', folded ? 'Show the conversation' : 'Hide the conversation');
        btn.querySelector('svg').style.transform = folded ? 'rotate(180deg)' : '';

        if (folded) { showNewIfAny(); } else { markSeen(); }
        if (remember) set(FOLD, folded ? '1' : '0');
    }

    apply(get(FOLD) === '1', false);

    btn.addEventListener('click', function () {
        apply(!grid.classList.contains('is-folded'), true);
    });
})();

// ── Edit Agreement ───────────────────────────────────────
(function () {
    var panel = document.querySelector('[data-eg]');
    var scrim = document.querySelector('[data-eg-scrim]');
    if (!panel) return;

    function open()  { panel.hidden = false; scrim.hidden = false; document.body.style.overflow = 'hidden'; }
    function close() { panel.hidden = true;  scrim.hidden = true;  document.body.style.overflow = ''; }

    document.querySelectorAll('[data-ws-edit]').forEach(function (b) { b.addEventListener('click', open); });
    document.querySelectorAll('[data-eg-close]').forEach(function (b) { b.addEventListener('click', close); });
    scrim.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) close(); });

    // Arriving on #edit, or coming back from a failed save, opens it rather
    // than leaving the reader looking at the page they just left.
    if (location.hash === '#edit' || panel.querySelector('.eg-err, .eg-note.bad')) open();

    // Sending is the thing that resets signatures, so it asks once.
    panel.querySelector('[value="send"]').addEventListener('click', function (e) {
        if (!confirm('Send these changes? Any approvals and signatures on the current terms will stop counting, and both of you will need to agree the new version.')) {
            e.preventDefault();
        }
    });
})();
</script>
@endpush
@endsection
