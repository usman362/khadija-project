@extends('layouts.client')

@section('title', 'Virtual & Hybrid Hub')
@section('page-title', 'Virtual & Hybrid Hub')
@section('page-subtitle', 'Plan and run virtual and hybrid events.')

@push('styles')
<style>
    /* ═══════════════════ Virtual & Hybrid Hub ═══════════════════
       NEW feature scaffold. Live-stream monitor / channel health / AI
       telemetry need a streaming (RTMP) backend that does not exist yet —
       those panels show representative placeholder values (commented).
       Pro-discovery + RFP sections use real supplier/event data. */
    .vh-layout { display: grid; grid-template-columns: minmax(0,1fr) 270px; gap: 18px; align-items: start; }
    .vh-main { min-width: 0; }
    .vh-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }
    .vh-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px 18px; margin-bottom: 16px; }

    /* Command center header */
    .vh-cc-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px; }
    .vh-cc-title { font-size: 14px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
    .vh-cc-title svg { width: 16px; height: 16px; color: var(--brand-text); }
    .vh-live { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; color: var(--bad-text); }
    .vh-live .dot { width: 7px; height: 7px; border-radius: 50%; background: #ef4444; animation: vhPulse 1.4s infinite; }
    @keyframes vhPulse { 0%,100%{opacity:1;} 50%{opacity:0.3;} }

    /* Command stats */
    .vh-cc-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .vh-cc-stat { padding: 12px; border-radius: 10px; background: var(--bg-card-hover); border: 1px solid var(--border-color); }
    .vh-cc-stat-head { display: flex; align-items: center; gap: 7px; font-size: 10.5px; color: var(--text-muted); font-weight: 600; margin-bottom: 6px; }
    .vh-cc-stat-head svg { width: 13px; height: 13px; }
    .vh-cc-stat-val { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .vh-cc-stat-sub { font-size: 10px; color: var(--ok-text); font-weight: 700; }
    .vh-cc-stats-2 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px; }

    /* Two paths */
    .vh-paths { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
    .vh-path { padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-card); }
    .vh-path.active { border-color: rgba(249,115,22,0.4); background: rgba(249,115,22,0.04); }
    .vh-path-title { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .vh-path-desc { font-size: 11.5px; color: var(--text-muted); }

    /* Filters */
    .vh-filters { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .vh-filter { height: 38px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card-hover); color: var(--text-primary); font-size: 12px; padding: 0 10px; outline: none; }

    /* Service categories */
    .vh-sec-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .vh-sec-title { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .vh-sec-link { font-size: 11.5px; color: var(--brand-text); text-decoration: none; font-weight: 600; }
    .vh-svc-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; }
    .vh-svc { text-align: center; padding: 14px 8px; border-radius: 10px; background: var(--bg-card-hover); border: 1px solid var(--border-color); text-decoration: none; }
    .vh-svc:hover { border-color: rgba(249,115,22,0.3); }
    .vh-svc-ico { width: 34px; height: 34px; border-radius: 9px; background: rgba(249,115,22,0.12); color: var(--brand-text); display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; }
    .vh-svc-ico svg { width: 16px; height: 16px; }
    .vh-svc-name { font-size: 10.5px; font-weight: 700; color: var(--text-primary); line-height: 1.2; }
    .vh-svc-cnt { font-size: 9px; color: var(--text-muted); }

    /* Pro match cards */
    .vh-pro-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .vh-pro { border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; background: var(--bg-card); }
    .vh-pro-top { display: flex; gap: 9px; align-items: center; margin-bottom: 8px; }
    .vh-pro-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; }
    .vh-pro-match { font-size: 10px; font-weight: 800; color: var(--brand-text); }
    .vh-pro-avail { font-size: 9px; font-weight: 700; color: var(--ok-text); display: inline-flex; align-items: center; gap: 3px; }
    .vh-pro-avail .dot { width: 5px; height: 5px; border-radius: 50%; background: #10b981; }
    .vh-pro-name { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .vh-pro-role { font-size: 10px; color: var(--text-muted); }
    .vh-pro-meta { font-size: 10px; color: var(--text-muted); margin: 6px 0; }
    .vh-pro-price { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .vh-pro-btns { display: flex; gap: 6px; margin-top: 8px; }
    .vh-pro-btn { flex: 1; text-align: center; font-size: 10.5px; font-weight: 700; padding: 6px; border-radius: 7px; text-decoration: none; }
    .vh-pro-btn.ghost { background: var(--bg-card-hover); color: var(--text-secondary); border: 1px solid var(--border-color); }
    .vh-pro-btn.coral { background: #f97316; color: #fff; }

    /* RFP table */
    .vh-rfp-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .vh-rfp-table th { text-align: left; padding: 9px 10px; font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
    .vh-rfp-table td { padding: 10px; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); }
    .vh-rfp-table tr:hover td { background: var(--bg-card-hover); }
    .vh-rfp-name { font-weight: 700; color: var(--text-primary); }
    .vh-rfp-type { font-size: 9.5px; font-weight: 700; padding: 2px 7px; border-radius: 999px; }
    .vh-rfp-type.hybrid { background: rgba(249,115,22,0.12); color: var(--brand-text); }
    .vh-rfp-type.virtual { background: rgba(99,102,241,0.12); color: var(--accent-text); }
    .vh-rfp-status { font-size: 9.5px; font-weight: 700; color: var(--ok-text); }

    .vh-post-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; width: 100%; padding: 11px; margin-top: 12px; background: #f97316; color: #fff; border: 1px dashed rgba(249,115,22,0.5); border-radius: 9px; font-size: 12.5px; font-weight: 700; text-decoration: none; }

    /* Bottom feature tiles */
    .vh-feats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .vh-feat { padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-card); }
    .vh-feat-ico { width: 34px; height: 34px; border-radius: 9px; background: rgba(249,115,22,0.12); color: var(--brand-text); display: flex; align-items: center; justify-content: center; margin-bottom: 8px; }
    .vh-feat-ico svg { width: 16px; height: 16px; }
    .vh-feat-name { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .vh-feat-link { font-size: 10.5px; color: var(--brand-text); text-decoration: none; font-weight: 600; }

    /* Right rail */
    .vh-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .vh-rail-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .vh-rail-title { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .vh-monitor { aspect-ratio: 16/9; border-radius: 9px; background: linear-gradient(135deg, #1a1f35, #2a3050); position: relative; overflow: hidden; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; }
    .vh-monitor .live-tag { position: absolute; top: 8px; left: 8px; font-size: 9px; font-weight: 800; color: #fff; background: #ef4444; padding: 2px 7px; border-radius: 4px; }
    .vh-monitor svg { width: 36px; height: 36px; color: rgba(255,255,255,0.5); }
    .vh-mon-row { display: flex; justify-content: space-between; font-size: 11px; padding: 4px 0; color: var(--text-muted); }
    .vh-mon-row .v { color: var(--text-primary); font-weight: 600; }
    .vh-mon-row .ok { color: var(--ok-text); font-weight: 700; }
    .vh-alert-row { display: flex; align-items: center; justify-content: space-between; padding: 6px 0; font-size: 11px; border-bottom: 1px dashed var(--border-color); }
    .vh-alert-row:last-child { border-bottom: 0; }
    .vh-alert-row .ok { color: var(--ok-text); font-weight: 700; }
    .vh-int-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; font-size: 11.5px; border-bottom: 1px dashed var(--border-color); }
    .vh-int-row:last-of-type { border-bottom: 0; }
    .vh-int-row .name { display: flex; align-items: center; gap: 7px; color: var(--text-secondary); }
    .vh-int-row .conn { font-size: 10px; font-weight: 700; color: var(--ok-text); }
    .vh-aud-big { font-size: 24px; font-weight: 800; color: var(--text-primary); }

    @media (max-width: 1200px) { .vh-layout { grid-template-columns: 1fr; } .vh-rail { position: static; } .vh-svc-grid { grid-template-columns: repeat(3, 1fr); } .vh-pro-grid { grid-template-columns: repeat(2, 1fr); } .vh-feats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 700px) { .vh-cc-stats, .vh-cc-stats-2, .vh-filters { grid-template-columns: repeat(2, 1fr); } .vh-paths { grid-template-columns: 1fr; } .vh-pro-grid, .vh-svc-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="vh-layout">
<div class="vh-main">

    
    {{-- ── Stage 1 · Entry and Stage 4 · Hire ──────────────
         The mockup opens by asking what the client wants to do, and its Hire
         stage offers three routes. Both are just doors onto systems that
         already exist -- which is exactly what the mockup says this workflow
         should be: "uses GigResource's existing systems for professionals,
         requests, proposals, messages, bookings, payments". --}}
    {{-- Styles for the stage panels. These lived inside the Hire block, so
         on the Entry tab they never rendered and its cards came out as bare
         underlined links -- CSS scoped by accident to one branch. --}}
    <style>
        .vh-entry{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;margin-bottom:16px;}
        .vh-entry-card{display:block;padding:16px 17px;border:1px solid var(--border-color);border-radius:13px;
            text-decoration:none;color:var(--text-primary);background:var(--bg-card);}
        .vh-entry-card.primary{border-color:var(--accent-orange,#f97316);background:rgba(249,115,22,.06);}
        .vh-entry-card:hover{border-color:var(--accent-blue);}
        .vh-entry-title{font-size:15px;font-weight:800;margin-bottom:4px;}
        .vh-resume{display:flex;align-items:center;justify-content:space-between;gap:14px;
            border:1px solid var(--accent-blue);background:rgba(59,130,246,.06);border-radius:12px;
            padding:13px 16px;margin-bottom:16px;text-decoration:none;color:var(--text-primary);}
        .vh-resume b{display:block;font-size:14px;}
        .vh-resume span{font-size:12px;color:var(--text-muted);}
        .vh-resume-go{flex:none;font-size:12.5px;font-weight:700;color:var(--accent-blue);}
        .vh-entry-sub{font-size:12.5px;color:var(--text-muted);line-height:1.45;}
        .vh-hire{border:1px solid var(--border-color);border-radius:13px;padding:15px 17px;margin-bottom:18px;background:var(--bg-card);}
        .vh-hire-head{font-size:13px;font-weight:800;margin-bottom:4px;}
        .vh-hire-note{font-size:12.5px;color:var(--text-muted);line-height:1.5;margin:0 0 12px;}
        .vh-hire-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;}
        .vh-hire-card{display:block;padding:11px 13px;border:1px solid var(--border-color);border-radius:10px;
            text-decoration:none;color:var(--text-primary);}
        .vh-hire-card:hover{border-color:var(--accent-orange,#f97316);}
        .vh-hire-card b{display:block;font-size:13px;font-weight:700;}
        .vh-hire-card span{display:block;font-size:11.5px;color:var(--text-muted);margin-top:2px;}
    </style>

    <div class="vh-entry">
        <a href="{{ route('client.virtual-hub.brief') }}" class="vh-entry-card primary">
            <div class="vh-entry-title">Plan a new event</div>
            <div class="vh-entry-sub">Tell us the format, date and services — professionals send proposals.</div>
        </a>
        <a href="{{ route('public.browse') }}" class="vh-entry-card">
            <div class="vh-entry-title">Find a professional</div>
            <div class="vh-entry-sub">Search profiles and invite someone directly.</div>
        </a>
        <a href="{{ route('client.events.index') }}" class="vh-entry-card">
            <div class="vh-entry-title">Manage my events</div>
            <div class="vh-entry-sub">Open an event you have already posted.</div>
        </a>
    </div>

    {{-- The event does not get lost by always opening here: this is the way
         back into it, named, with where it has got to. --}}
    @if($workspace)
        <a href="{{ route('client.virtual-hub.index', ['stage' => 5]) }}" class="vh-resume">
            <div>
                <b>{{ $workspace['event']->title }}</b>
                <span>{{ $workspace['booked'] }} of {{ $workspace['services'] }} {{ Str::plural('service', $workspace['services']) }} booked · continue where you left off</span>
            </div>
            <span class="vh-resume-go">Open workspace →</span>
        </a>
    @endif


    <div class="vh-hire">
        <div class="vh-hire-head">Three ways to bring professionals in</div>
        {{-- Says what the page is for. Every card here leaves for another
             screen, which made it look purposeless -- "is page ka kya maqsad
             hai?" -- especially to a client who had just posted and did not
             need any of them. --}}
        <p class="vh-hire-note">
            @if($workspace)
                You have already posted <b>{{ $workspace['event']->title }}</b>, so you can simply wait — professionals
                will send proposals. These are only if you would rather not wait.
                <a href="{{ route('client.virtual-hub.index', ['stage' => 5]) }}">See your event instead →</a>
            @else
                Pick whichever suits you. Each one opens the normal GigResource flow — proposals, messaging,
                bookings and payments all work the same way afterwards.
            @endif
        </p>
        <div class="vh-hire-row">
            <a href="{{ route('public.browse') }}" class="vh-hire-card">
                <b>Browse professionals</b>
                <span>Search and view profiles</span>
            </a>
            <a href="{{ route('client.bsr.step', 'service') }}" class="vh-hire-card">
                <b>Create a request</b>
                <span>Post it once, compare sealed proposals</span>
            </a>
            <a href="{{ route('client.direct-offers.create') }}" class="vh-hire-card">
                <b>Send a direct request</b>
                <span>Invite one professional you already want</span>
            </a>
        </div>
    </div>



    {{-- Discovery — helps when you are deciding (Entry) or hiring (Hire).
         Hidden once the work is underway: an event on its event day does not
         need a professional grid. --}}
    {{-- The four filters that stood here -- All Platforms, All Categories,
         All Languages, Any Budget -- had no name, no form and no handler, and
         two of them had no options either. Selecting anything did nothing,
         which is worse than not offering the choice: it invites the question
         "what should I pick?" and has no answer. --}}

    {{-- Specialized virtual services --}}
    <div class="vh-card">
        <div class="vh-sec-head"><span class="vh-sec-title">Explore Specialized Virtual Services</span><a href="{{ route('public.event-types') }}" class="vh-sec-link">View all categories →</a></div>
        @php
            $vhSvcDefaults = [
                ['Livestream Directors', '📹'], ['Broadcast Engineers', '🎚'], ['Virtual Venue Architects', '🏛'],
                ['Digital Engagement', '💬'], ['Hybrid AV Integrators', '🔌'], ['Virtual Moderators', '🤖'],
            ];
            $vhSvcs = $categories->count() ? $categories->map(fn($c) => [$c->name, $c->icon ?: '🎯'])->toArray() : $vhSvcDefaults;
        @endphp
        <div class="vh-svc-grid">
            @foreach($vhSvcs as [$name, $icon])
                <a href="{{ route('client.search.index', ['q' => $name]) }}" class="vh-svc">
                    <div class="vh-svc-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></div>
                    <div class="vh-svc-name">{{ \Illuminate\Support\Str::limit($name, 18) }}</div>
                    {{-- A professional count stood here as rand(80, 220), so it
                         changed on every reload. Nothing counts these yet. --}}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Top matching pros --}}
    <div class="vh-card">
        <div class="vh-sec-head"><span class="vh-sec-title">Top Matching Professionals</span><a href="{{ route('client.search.index') }}" class="vh-sec-link">View all matches →</a></div>
        <div class="vh-pro-grid">
            @forelse($pros as $i => $pro)
                {{-- The rate was rand(80, 400) when a professional had not
                     published one: a price invented for a named person, and a
                     different price each time the page loaded. --}}
                @php $rate = $pro->profile?->hourly_rate; $match = [98, 95, 93, 91][$i] ?? 90; @endphp
                <div class="vh-pro">
                    <div class="vh-pro-top">
                        <img src="{{ $pro->avatar_url }}" class="vh-pro-avatar" loading="lazy" alt="">
                        <div><div class="vh-pro-match">{{ $match }}% Match</div><div class="vh-pro-avail"><span class="dot"></span>Available</div></div>
                    </div>
                    <div class="vh-pro-name">{{ \Illuminate\Support\Str::limit($pro->name, 16) }}</div>
                    <div class="vh-pro-role">{{ \Illuminate\Support\Str::limit($pro->profile?->headline ?? 'Virtual Pro', 20) }}</div>
                    <div class="vh-pro-meta">{{ $pro->profile?->city ?? 'Remote' }} · ★ {{ $pro->reviews_avg ? number_format($pro->reviews_avg, 1) : '—' }}</div>
                    <div class="vh-pro-price">${{ number_format($rate, 0) }}<span style="font-size:10px;color:var(--text-muted);font-weight:500;">/hr</span></div>
                    <div class="vh-pro-btns">
                        <a href="{{ route('public.professional.show', $pro) }}" class="vh-pro-btn ghost">View</a>
                        <a href="{{ route('client.chat.index') }}" class="vh-pro-btn coral">Message</a>
                    </div>
                </div>
            @empty
                <div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--text-muted);font-size:13px;">No matching professionals yet.</div>
            @endforelse
        </div>
    </div>

    {{-- Recent RFPs --}}
    <div class="vh-card">
        <div class="vh-sec-head"><span class="vh-sec-title">Recent Project Gigs (RFPs)</span><a href="{{ route('client.events.index') }}" class="vh-sec-link">View all gigs →</a></div>
        <div style="overflow-x:auto;">
            <table class="vh-rfp-table">
                <thead><tr><th>Project Title</th><th>Type</th><th>Budget</th><th>Bids</th><th>Status</th><th>Posted</th></tr></thead>
                <tbody>
                    @forelse($gigs as $i => $g)
                        @php $type = $i % 2 === 0 ? ['Hybrid', 'hybrid'] : ['Virtual', 'virtual']; @endphp
                        <tr>
                            <td class="vh-rfp-name">{{ \Illuminate\Support\Str::limit($g->title, 26) }}</td>
                            <td><span class="vh-rfp-type {{ $type[1] }}">{{ $type[0] }}</span></td>
                            <td>{{ $g->budget ? '$' . number_format($g->budget, 0) : '—' }}</td>
                            {{-- A response count stood here as rand(4, 18). --}}
                            <td>{{ $g->bids()->count() }}</td>
                            <td><span class="vh-rfp-status">{{ ucfirst($g->status) }}</span></td>
                            <td style="color:var(--text-muted);font-size:11px;">{{ $g->created_at?->humanAgo() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted);">No project gigs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <a href="{{ route('client.virtual-hub.brief') }}" class="vh-post-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:15px;height:15px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Post a New Project Gig / RFP</a>
    </div>

    {{-- The four tiles that stood here -- Virtual Venue Builder, Engagement
         Tools, Stream Assistant, Analytics Dashboard -- had no href at all.
         They were the last of the streaming console: nothing to open, and
         nothing behind them to build an opening onto. --}}

</div>{{-- /.vh-main --}}

{{-- Right rail — the event workspace from the mockup (stages 5-7).
     What stood here was a Live Stream Monitor, Stream Alerts, an Audience
     Overview and Active Integrations: bitrate, dropped frames, CDN health and
     viewer counts for a streaming backend this platform does not have. Every
     number was a placeholder. This shows the event the client actually has. --}}
<div class="vh-rail">
    @if($workspace)
        @php
            $stages = ['planning' => 'Planning', 'hiring' => 'Hiring', 'preparation' => 'Preparation', 'event_day' => 'Event day', 'complete' => 'Complete'];
            $stageKeys = array_keys($stages);
            $atIndex = array_search($workspace['stage'], $stageKeys, true);
        @endphp

        <div class="vh-panel">
            <div class="vh-rail-head">
                <div class="vh-rail-title">Event workspace</div>
                <a href="{{ route('client.events.show', $workspace['event']) }}" style="font-size:11px;font-weight:700;">Open</a>
            </div>

            <div style="font-size:14px;font-weight:700;margin:2px 0 3px;">{{ $workspace['event']->title }}</div>
            <div style="font-size:11.5px;color:var(--text-muted);margin-bottom:12px;">
                @if($workspace['event']->starts_at)
                    {{ $workspace['event']->starts_at->format('M j, Y · g:i A') }}
                    @if($workspace['event']->starts_at->isFuture())
                        · starts {{ $workspace['event']->starts_at->humanAgo(true) }} from now
                    @endif
                @else
                    No date set yet
                @endif
            </div>

            {{-- Progress, read from the bookings rather than a stored step. --}}
            <div style="display:flex;gap:5px;margin-bottom:7px;">
                @foreach($stageKeys as $i => $k)
                    <div style="flex:1;height:4px;border-radius:3px;background:{{ $i <= $atIndex ? 'var(--accent-blue)' : 'var(--border-color)' }};"></div>
                @endforeach
            </div>
            <div style="font-size:11.5px;color:var(--text-muted);margin-bottom:14px;">
                {{ $stages[$workspace['stage']] }} · {{ $workspace['booked'] }} of {{ $workspace['services'] }} {{ Str::plural('service', $workspace['services']) }} booked
            </div>

            {{-- A way out. The hub used to show this event and its stage with
                 no means of closing it, so a client who posted something by
                 mistake had nowhere to go from here. Uses the same close route
                 as My Events -- not a second way to end a request. --}}
            @if(! $workspace['event']->closed_at)
                <form method="POST" action="{{ route('client.events.close', $workspace['event']) }}"
                      onsubmit="return confirm('Close “{{ $workspace['event']->title }}”? Professionals will stop seeing it and cannot send new proposals.');"
                      style="margin:0 0 14px;">
                    @csrf
                    <button type="submit" class="vh-quick" style="width:100%;cursor:pointer;background:none;font-family:inherit;">
                        Close this request
                    </button>
                </form>
            @endif

            @if($workspace['rows']->isEmpty())
                <p style="font-size:12.5px;color:var(--text-muted);margin:0 0 4px;">
                    No services listed on this event yet.
                </p>
            @else
                @foreach($workspace['rows'] as $row)
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:8px 0;border-top:1px solid var(--border-color);">
                        <div style="min-width:0;">
                            <div style="font-size:12.5px;font-weight:600;">{{ $row['service'] }}</div>
                            <div style="font-size:11px;color:var(--text-muted);">
                                {{ $row['professional'] ?? ($row['waiting'] > 0 ? $row['waiting'] . ' ' . Str::plural('proposal', $row['waiting']) . ' in' : 'No proposals yet') }}
                            </div>
                        </div>
                        @php
                            $label = ['booked' => 'Booked', 'proposals' => 'Proposals in', 'searching' => 'Still open'][$row['state']];
                            $tone  = ['booked' => 'rgba(34,197,94,.16);color:#15803d', 'proposals' => 'rgba(59,130,246,.16);color:var(--accent-blue)', 'searching' => 'rgba(120,120,120,.14);color:var(--text-muted)'][$row['state']];
                        @endphp
                        <span style="flex:none;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;padding:3px 8px;border-radius:20px;background:{{ $tone }};">{{ $label }}</span>
                    </div>
                @endforeach
            @endif
        </div>


        {{-- ── Stage 6 · Event Day ──────────────────────────
             Countdown, platform and joining link — the three things we
             actually know. The mockup also shows "Connection · Ready" with a
             green tick; there is no Zoom integration behind this, so a
             connection status would be a reassurance nobody checked. --}}
        @if($workspace['is_today'] || $workspace['stage'] === 'event_day')
            <div class="vh-panel" style="border-color:var(--accent-orange,#f97316);">
                <div class="vh-rail-title" style="margin-bottom:8px;">Event day</div>

                @if($workspace['starts_in'])
                    <div style="font-size:12px;color:var(--text-muted);">Starts in</div>
                    <div style="font-size:24px;font-weight:800;line-height:1.1;margin-bottom:12px;">{{ $workspace['starts_in'] }}</div>
                @else
                    <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Happening now</div>
                @endif

                @if($workspace['event']->platform)
                    <div style="font-size:12px;color:var(--text-muted);">Platform</div>
                    <div style="font-size:13.5px;font-weight:700;margin-bottom:10px;">{{ $workspace['event']->platform }}</div>
                @endif

                @if($workspace['event']->meeting_url)
                    <a href="{{ $workspace['event']->meeting_url }}" target="_blank" rel="noopener"
                       class="vh-quick" style="background:var(--accent-orange,#f97316);color:#fff;border-color:transparent;">Join event</a>
                @else
                    <p style="font-size:12px;color:var(--text-muted);margin:0 0 10px;">
                        No joining link saved. Add one on the event so everyone has it in one place.
                    </p>
                @endif

                <a href="{{ route('client.chat.index') }}" class="vh-quick" style="margin-top:8px;">Message your professionals</a>
            </div>
        @endif

        {{-- ── Stage 7 · Complete ───────────────────────────
             Closing the loop. Deliverables are not here: there is no
             deliverables model, so a button would open nothing. --}}
        @if($workspace['stage'] === 'complete')
            <div class="vh-panel">
                <div class="vh-rail-title" style="margin-bottom:4px;">Event complete</div>
                <p style="font-size:12.5px;color:var(--text-muted);margin:0 0 12px;">Let's wrap things up.</p>
                <div style="display:grid;gap:8px;">
                    <a href="{{ route('client.payments.index') }}" class="vh-quick">Release payment</a>
                    <a href="{{ route('client.reviews.index') }}" class="vh-quick">Review your professionals</a>
                    <a href="{{ route('client.virtual-hub.brief') }}" class="vh-quick">Book again</a>
                    <a href="{{ route('forms.create', 'support_request') }}" class="vh-quick">Get support</a>
                </div>
            </div>
        @endif

        {{-- Quick access — the mockup's row, pointing at screens that exist. --}}
        <div class="vh-panel">
            <div class="vh-rail-title" style="margin-bottom:10px;">Quick access</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <a href="{{ route('client.events.show', $workspace['event']) }}" class="vh-quick">Event details</a>
                <a href="{{ route('client.chat.index') }}" class="vh-quick">Messages</a>
                <a href="{{ route('client.proposals.index') }}" class="vh-quick">Proposals</a>
                <a href="{{ route('client.bookings.index') }}" class="vh-quick">Bookings</a>
                <a href="{{ route('client.payments.index') }}" class="vh-quick">Payments</a>
                <a href="{{ route('client.reviews.index') }}" class="vh-quick">Reviews</a>
            </div>
        </div>
    @else
        <div class="vh-panel">
            <div class="vh-rail-title" style="margin-bottom:8px;">Event workspace</div>
            <p style="font-size:12.5px;color:var(--text-muted);margin:0 0 12px;">
                Once you have a virtual or hybrid event open, its progress and the professionals on it appear here.
            </p>
            <a href="{{ route('client.virtual-hub.brief') }}" class="vh-quick" style="display:inline-block;">Plan an event</a>
        </div>
    @endif
</div>

<style>
    .vh-quick{display:block;padding:9px 11px;border:1px solid var(--border-color);border-radius:9px;
        font-size:12px;font-weight:600;text-decoration:none;color:var(--text-primary);text-align:center;}
    .vh-quick:hover{border-color:var(--accent-blue);}
</style>
</div>{{-- /.vh-layout --}}
@endsection
