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

    @media (max-width: 1500px) { .ws { grid-template-columns: 180px minmax(0,1fr) 300px; } .ws-pro-col { grid-column: 1 / -1; } }
    @media (max-width: 1100px) { .ws { grid-template-columns: 1fr; } .ws-msgs { max-height: none; } }
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
                            <td>{{ $event?->budget_max ? '$' . number_format((float) $event->budget_max) : ($event?->budget_min ? 'From $' . number_format((float) $event->budget_min) : 'Not set') }}</td>
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
                <a class="ws-btn" href="{{ route('client.finalize.step', [$fin, 'bid']) }}">Request changes</a>
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
    <aside class="ws-card ws-msgs">
        <div class="ws-msgs-h">Messages</div>
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
@endsection
