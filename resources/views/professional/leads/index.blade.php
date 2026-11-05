@extends('layouts.professional')

@section('title', 'Lead Pipeline')

{{-- Lead Pipeline (Leads CRM) — explainer + a live snapshot of the pro's
     sales funnel (real: open opportunities + requested bookings, split by
     stage). "View All Leads" opens the full Proposals/Bids list.
     Everything drawn here is measured: the illustrated follow-up flow and
     the four always-ticked lead marks were removed on 2026-11-01. --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
    // Row 137 — one colour per stage, distinct from the priority palette.
    // Theme-aware tokens, same reason as $pipeColors below: the raw mid-tones
    // read as dark-on-dark or light-on-light depending on the active theme.
    $stageChip = [
        'New Lead'      => 'var(--info-text)',
        'Proposal Sent' => 'var(--accent-text)',
        'Negotiation'   => 'var(--warn-text)',
        'Booked'        => 'var(--ok-text)',
    ];
    $prioMap = [
        'High'   => ['var(--bad-text)',  'rgba(220,38,38,0.10)'],
        'Medium' => ['var(--warn-text)', 'rgba(217,119,6,0.10)'],
        'Low'    => ['var(--info-text)', 'rgba(37,99,235,0.10)'],
    ];
    // These carry white initials, so the LIGHT stop of each gradient is what
    // the letters have to survive: #10b981 gave 2.54 and #8b5cf6 4.23 against
    // a 4.5 requirement. Each light stop moved to its darker sibling.
    $avatars = ['linear-gradient(135deg,#6d28d9,#5b21b6)','linear-gradient(135deg,#2563eb,#1d4ed8)','linear-gradient(135deg,#047857,#065f46)','linear-gradient(135deg,#be185d,#9d174d)','linear-gradient(135deg,#b45309,#92400e)'];
    // Stage display config for the progress pipeline.
    // Text colours, so they use the theme-aware tokens — the raw mid-tones read
    // as dark-on-dark or light-on-light depending on which theme is active.
    $pipeColors = ['new'=>'var(--info-text)','proposal'=>'var(--warn-text)','negotiation'=>'var(--accent-text)','booked'=>'var(--ok-text)'];
@endphp

@push('styles')
<style>
    /* Text-only accent, so it follows the theme rather than sitting at one
       mid-tone that is weak on the dark shell. */
    .pl { --pl-blue: var(--info-text); }
    .pl-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px 22px; }

    /* ── Hero (funnel + title + feature row) ── */
    .pl-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
    .pl-head h1 { font-size: 24px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; }
    .pl-head p { font-size: 13px; color: var(--text-muted); margin: 0; }
    .pl-head-btn { font-size: 13px; font-weight: 700; padding: 10px 18px; border-radius: 10px;
        background: var(--accent-blue, #2563eb); color: #fff; text-decoration: none; }
    .pl-hero { display: grid; grid-template-columns: minmax(0,300px) minmax(0,1fr); gap: 22px; align-items: center; background: linear-gradient(135deg, rgba(37,99,235,0.05), rgba(139,92,246,0.04)); border: 1px solid var(--border-color); border-radius: 18px; padding: 26px 28px; margin-bottom: 20px; }
    .pl-funnel { display: flex; align-items: center; justify-content: center; }
    .pl-funnel svg { width: 100%; max-width: 280px; height: auto; }
    .pl-hero-r h1 { font-size: 32px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .pl-hero-r .sub { font-size: 16px; color: var(--text-secondary); margin: 4px 0 18px; }
    .pl-feats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; }
    .pl-feat-ico { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; }
    .pl-feat-ico svg { width: 18px; height: 18px; }
    .pl-feat b { font-size: 12.5px; font-weight: 800; color: var(--text-primary); display: block; line-height: 1.3; }
    .pl-feat p { font-size: 11px; color: var(--text-muted); margin: 4px 0 0; line-height: 1.45; }

    /* ── Progress pipeline bar ── */
    .pl-pipe { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 20px; }
    .pl-stage { flex: 1; display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 6px 14px; }
    .pl-stage-l .k { font-size: 13px; font-weight: 700; }
    .pl-stage-l .v { font-size: 34px; font-weight: 800; color: var(--text-primary); line-height: 1; margin-top: 2px; }
    .pl-stage-ico { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pl-stage-ico svg { width: 24px; height: 24px; }
    .pl-arrow { flex-shrink: 0; color: var(--text-muted); opacity: 0.6; }
    .pl-arrow svg { width: 22px; height: 22px; }

    /* ── Active leads list ── */
    .pl-lead { display: flex; align-items: center; gap: 14px; padding: 15px 6px; border-top: 1px solid var(--border-color); }
    .pl-lead:first-child { border-top: none; }
    .pl-lead-av { width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 15px; font-weight: 800; }
    .pl-lead-mid { flex: 1; min-width: 0; }
    .pl-lead-name { font-size: 15px; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pl-lead-meta { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }
    .pl-lead-val { font-size: 15px; font-weight: 800; color: var(--text-primary); white-space: nowrap; }
    /* Row 137 — the stage is where a lead sits in the pipeline; the priority
       is how hard to chase it. They were two pills of the same shape, so a
       glance could not tell which was which. The stage is now a square-cornered
       outline chip, the priority stays the filled round pill. */
    .pl-prio { font-size: 11.5px; font-weight: 800; padding: 6px 14px; border-radius: 999px; white-space: nowrap; min-width: 118px; text-align: center; }
    .pl-stage-chip { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 5px; white-space: nowrap;
        border: 1px solid currentColor; background: transparent; letter-spacing: .01em; }
    .pl-lead-age { font-size: 11px; color: var(--text-muted); white-space: nowrap; }
    .pl-viewall { display: block; text-align: center; padding: 14px; margin-top: 8px; font-size: 14px; font-weight: 800; color: var(--pl-blue); text-decoration: none; }
    .pl-viewall svg { width: 16px; height: 16px; vertical-align: -3px; margin-left: 4px; }
    .pl-empty { padding: 30px 10px; text-align: center; color: var(--text-muted); font-size: 13px; }

    /* ── Section heading ── */
    .pl-sec-h { display: flex; align-items: center; gap: 9px; margin-bottom: 4px; }
    .pl-sec-h svg { width: 20px; height: 20px; color: var(--info-text); }
    .pl-sec-h b { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .pl-sec-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 16px; }

    .pl-mock { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 9px; overflow: hidden; margin-bottom: 12px; }
    .pl-mock-bar { display: flex; gap: 4px; padding: 7px 9px; background: #1e293b; }
    .pl-mock-bar i { width: 7px; height: 7px; border-radius: 50%; background: #475569; }
    .pl-mock-body { display: grid; grid-template-columns: 30% 1fr; min-height: 78px; }
    .pl-mock-side { background: #1e293b; padding: 8px; }
    .pl-mock-side .ln { height: 6px; border-radius: 3px; background: #475569; margin-bottom: 6px; }
    .pl-mock-main { padding: 9px; }
    .pl-mock-row { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
    .pl-mock-row .dot { width: 14px; height: 14px; border-radius: 50%; background: #cbd5e1; flex-shrink: 0; }
    .pl-mock-row .ln { flex: 1; height: 6px; border-radius: 3px; background: var(--border-color); }

    /* ── What happens if you click (4 cols) ── */
    .pl-cc { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 16px; }
    .pl-cc-card { border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; background: var(--bg-card); }
    .pl-cc-h { display: flex; align-items: flex-start; gap: 9px; margin-bottom: 4px; }
    .pl-cc-h .ic { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pl-cc-h .ic svg { width: 16px; height: 16px; }
    .pl-cc-h b { font-size: 13px; font-weight: 800; color: var(--text-primary); line-height: 1.3; }
    .pl-cc-card > p { font-size: 11px; color: var(--text-muted); line-height: 1.45; margin: 0 0 12px; }
    .pl-cc-prev { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px; }
    /* chat */
    .pl-chat-hd { display: flex; align-items: center; gap: 8px; margin-bottom: 9px; }
    .pl-chat-av { width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; font-weight: 800; }
    .pl-chat-hd b { font-size: 11px; color: var(--text-primary); }
    .pl-chat-hd p { font-size: 9.5px; color: var(--text-muted); margin: 0; }
    .pl-bub { border-radius: 9px; padding: 6px 9px; font-size: 10px; line-height: 1.35; margin-bottom: 6px; max-width: 85%; }
    .pl-bub.them { background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-secondary); }
    .pl-bub.me { background: rgba(37,99,235,0.12); color: var(--text-primary); margin-left: auto; }
    .pl-bub .t { display: block; font-size: 8px; color: var(--text-muted); margin-top: 2px; }
    .pl-chat-icons { display: flex; gap: 14px; padding-top: 8px; border-top: 1px solid var(--border-color); margin-top: 8px; }
    .pl-chat-icons svg { width: 15px; height: 15px; color: var(--text-muted); }
    /* proposal */
    .pl-prop-amt { font-size: 22px; font-weight: 800; color: var(--ok-text); }
    .pl-prop-k { font-size: 9.5px; color: var(--text-muted); }
    .pl-chk { display: flex; align-items: center; gap: 7px; font-size: 10.5px; color: var(--text-secondary); padding: 3px 0; }
    .pl-chk svg { width: 13px; height: 13px; color: var(--ok-text); flex-shrink: 0; }
    /* follow-up flow */
    .pl-flow-step { display: flex; align-items: center; gap: 9px; padding: 7px 9px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); }
    .pl-flow-step .ic { width: 24px; height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pl-flow-step .ic svg { width: 13px; height: 13px; }
    .pl-flow-step span { font-size: 10px; color: var(--text-secondary); line-height: 1.3; }
    .pl-flow-arrow { display: flex; justify-content: center; padding: 3px 0; }
    .pl-flow-arrow svg { width: 13px; height: 13px; color: var(--text-muted); }
    /* predictor gauge */
    .pl-gauge { width: 120px; height: 64px; margin: 4px auto 8px; position: relative; }
    .pl-gauge .pct { position: absolute; bottom: 0; left: 0; right: 0; text-align: center; }
    .pl-gauge .pct b { font-size: 22px; font-weight: 800; color: var(--ok-text); display: block; line-height: 1; }
    .pl-gauge .pct span { font-size: 8.5px; color: var(--text-muted); }

    .pl-cc-btn { display: flex; align-items: center; justify-content: center; gap: 6px; text-align: center; padding: 9px; border-radius: 8px; color: #fff; font-size: 11px; font-weight: 800; text-decoration: none; margin-top: 11px; }
    .pl-cc-btn svg { width: 13px; height: 13px; }

    /* ── Bottom banner ── */
    .pl-banner { display: flex; align-items: center; gap: 16px; background: linear-gradient(135deg, rgba(37,99,235,0.07), rgba(139,92,246,0.05)); border: 1px solid rgba(37,99,235,0.18); border-radius: 16px; padding: 18px 22px; margin-top: 20px; }
    .pl-banner .badge { width: 46px; height: 46px; border-radius: 12px; background: rgba(37,99,235,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pl-banner .badge svg { width: 24px; height: 24px; color: var(--info-text); }
    .pl-banner-txt { flex: 1; }
    .pl-banner-txt b { font-size: 16px; color: var(--text-primary); }
    .pl-banner-txt p { font-size: 12.5px; color: var(--text-muted); margin: 2px 0 0; }
    .pl-banner a { display: inline-flex; align-items: center; gap: 8px; background: #2563eb; color: #fff; font-size: 14px; font-weight: 800; padding: 13px 22px; border-radius: 11px; text-decoration: none; white-space: nowrap; }
    .pl-banner a svg { width: 16px; height: 16px; }

    @media (max-width: 1200px) { .pl-hero { grid-template-columns: 1fr; } .pl-cc { grid-template-columns: repeat(2, minmax(0,1fr)); } .pl-feats { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 760px) { .pl-pipe { flex-wrap: wrap; } .pl-arrow { display: none; } .pl-cc, .pl-feats { grid-template-columns: 1fr; } }
</style>
@endpush

@push('styles')
<style>
    .pl-more { grid-column: 1 / -1; margin-top: 10px; border-top: 1px solid var(--border-color); padding-top: 9px; }
    .pl-more > summary { list-style: none; cursor: pointer; font-size: 12px; font-weight: 700; color: var(--accent-blue, #2563eb); }
    .pl-more > summary::-webkit-details-marker { display: none; }
    .pl-more-body { margin-top: 10px; display: flex; flex-direction: column; gap: 10px; }
    .pl-why { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 7px 16px; }
    .pl-why li { font-size: 12px; }
    .pl-why li.yes { color: var(--ok-text, #047857); font-weight: 700; }
    .pl-why li.no { color: var(--text-muted); }
    .pl-facts { display: flex; flex-wrap: wrap; gap: 8px 22px; }
    .pl-facts span { font-size: 12px; color: var(--text-primary); }
    .pl-facts b { display: block; font-size: 10px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .3px; color: var(--text-muted); }
    .pl-summary { font-size: 12.5px; color: var(--text-secondary, var(--text-muted)); margin: 0; line-height: 1.55; }
    .pl-more-go { align-self: flex-start; font-size: 12.5px; font-weight: 700; padding: 8px 15px;
        border-radius: 9px; background: var(--accent-blue, #2563eb); color: #fff; text-decoration: none; }
</style>
@endpush

@section('content')
<div class="pl">


    {{-- Khadijah's 28 October screen opens on the pipeline. The funnel and
         the four "what this does" tiles are an explanation, and an explanation
         is not what somebody came to this page for, so they moved below the
         leads rather than above them. --}}
    <div class="pl-head">
        <div>
            <h1>Leads CRM</h1>
            <p>Track leads from first enquiry through to a booking.</p>
        </div>
        <a class="pl-head-btn" href="{{ route('professional.bidding-board.index') }}">Browse open gigs</a>
    </div>

    {{-- ════════ Progress pipeline bar ════════ --}}
    <div class="pl-card" style="margin-bottom:20px;">
        <div class="pl-pipe">
            @php
                $stageIcons = [
                    'new'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>',
                    'proposal'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="14" x2="15" y2="14"/></svg>',
                    'negotiation' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
                    'booked'      => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-1.2 14.2l-4-4 1.4-1.4 2.6 2.6 5.6-5.6 1.4 1.4z"/></svg>',
                ];
            @endphp
            @foreach($pipeline as $i => $stage)
                @php $col = $pipeColors[$stage['key']]; @endphp
                <div class="pl-stage">
                    <div class="pl-stage-l">
                        <div class="k" style="color:{{ $col }};">{{ $stage['label'] }}</div>
                        <div class="v">{{ $stage['count'] }}</div>
                    </div>
                    <span class="pl-stage-ico" style="background:{{ $stage['key']==='booked' ? '#2563eb' : 'rgba(37,99,235,0.07)' }};color:{{ $stage['key']==='booked' ? '#fff' : $col }};">{!! $stageIcons[$stage['key']] !!}</span>
                </div>
                @if(!$loop->last)
                    <span class="pl-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></span>
                @endif
            @endforeach
        </div>

        {{-- Active leads list --}}
        @forelse($leads as $i => $lead)
            @php [$pc, $pbg] = $prioMap[$lead['priority']] ?? $prioMap['Medium']; $words = preg_split('/\s+/', trim($lead['name'])); $initials = strtoupper(substr($words[0] ?? 'L',0,1) . (count($words) > 1 ? substr(end($words),0,1) : '')); @endphp
            <div class="pl-lead">
                <span class="pl-lead-av" style="background:{{ $avatars[$i % count($avatars)] }};">{{ $initials ?: 'L' }}</span>
                <div class="pl-lead-mid">
                    <div class="pl-lead-name">{{ $lead['name'] }}</div>
                    <div class="pl-lead-meta">{{ \Illuminate\Support\Str::limit($lead['location'], 24) }} · {{ $lead['date'] ? $lead['date']->format('M d, Y') : 'Date TBD' }}</div>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                        {{-- Stage first, in its own shape — where the lead sits. --}}
                        <span class="pl-stage-chip" style="color:{{ $stageChip[$lead['stage']] ?? 'var(--text-muted)' }};">{{ $lead['stage'] }}</span>
                        {{-- Row 134 — age from the page's one shared today. --}}
                        @if($lead['ageDays'] !== null)
                            <span class="pl-lead-age">{{ $lead['ageDays'] === 0 ? 'Today' : $lead['ageDays'] . ($lead['ageDays'] === 1 ? ' day old' : ' days old') }}</span>
                        @endif
                    </div>
                </div>
                {{-- Row 138 — a range only where the client gave one. Every row
                     used to print a ±20% band around a single stated figure.

                     Built in PHP rather than inline directives: "stated@elseif"
                     puts a directive straight after a letter, which Blade does
                     not compile, so the page printed the directive itself where
                     the budget should have been. --}}
                @php
                    $budget = $lead['valueLow'] === null
                        ? 'Budget not stated'
                        : ($lead['isRange']
                            ? $money($lead['valueLow']) . ' – ' . $money($lead['valueHigh'])
                            : $money($lead['valueLow']));
                @endphp
                <div class="pl-lead-val">{{ $budget }}</div>
                {{-- Named, so the pill cannot be mistaken for another stage. --}}
                <span class="pl-prio" style="color:{{ $pc }};background:{{ $pbg }};">Priority: {{ $lead['priority'] }}</span>

                {{-- Khadijah's 28 October screen opens a lead into its own
                     detail with a priority score out of 100 under it.

                     There is no score here. Nothing weighs these tests against
                     each other, so a number out of 100 would be invented on the
                     screen somebody uses to decide who to chase. The tests
                     themselves are shown instead, because they are exactly what
                     set the priority and a professional can check every one. --}}
                <details class="pl-more">
                    <summary>Why this priority</summary>
                    <div class="pl-more-body">
                        <ul class="pl-why">
                            @foreach($lead['why'] as [$reason, $met])
                                <li class="{{ $met ? 'yes' : 'no' }}">{{ $met ? 'Yes' : 'No' }}, {{ \Illuminate\Support\Str::lcfirst($reason) }}</li>
                            @endforeach
                        </ul>

                        <div class="pl-facts">
                            @if($lead['eventType'])
                                <span><b>Event</b>{{ $lead['eventType'] }}</span>
                            @endif
                            @if($lead['guests'])
                                <span><b>Guests</b>{{ number_format($lead['guests']) }}</span>
                            @endif
                            @if($lead['venue'])
                                <span><b>Venue</b>{{ $lead['venue'] }}</span>
                            @endif
                            @if($lead['ageDays'] !== null)
                                <span><b>Waiting</b>{{ $lead['ageDays'] }} {{ \Illuminate\Support\Str::plural('day', $lead['ageDays']) }}</span>
                            @endif
                        </div>

                        @if($lead['services'])
                            <div class="pl-facts">
                                <span><b>Services asked for</b>{{ implode(', ', $lead['services']) }}</span>
                            </div>
                        @endif

                        @if($lead['summary'])
                            <p class="pl-summary">{{ $lead['summary'] }}</p>
                        @endif

                        @if($lead['eventId'])
                            <a class="pl-more-go" href="{{ route('professional.bid.step', ['event' => $lead['eventId']]) }}">Open this request</a>
                        @endif
                    </div>
                </details>
            </div>
        @empty
            <div class="pl-empty">No active leads yet. New marketplace opportunities and client requests will appear here automatically.</div>
        @endforelse

        <a href="{{ route('professional.proposals.index') }}" class="pl-viewall">View All Leads <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>

    {{-- A "Detailed Section Breakdown" stood here: four columns explaining
         this page, on this page. "The Mission: this area monitors your leads
         through every step of the sales journey", a second drawing of the
         funnel that is already at the top, a second listing of the leads that
         are already above it, and a mock-up of a screen with grey bars and
         green ticks promising "filters and advanced tools".

         The same block was on My Calendar and went for the same reasons. A
         tour of a page belongs somewhere other than the page, and a drawing
         of an interface shown inside the interface is the clearest sign there
         is of something drawn rather than built. --}}

    {{-- ════════ Hero: funnel + title + feature row ════════ --}}
    <div class="pl-hero">
        <div class="pl-funnel">
            <svg viewBox="0 0 240 210" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="plf" x1="40" y1="60" x2="200" y2="150"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient>
                    <linearGradient id="plp" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#2563eb"/></linearGradient>
                </defs>
                {{-- funnel body --}}
                <path d="M40 64 L200 64 L150 132 L150 184 Q150 192 142 190 L98 178 Q90 176 90 168 L90 132 Z" fill="url(#plf)"/>
                <ellipse cx="120" cy="64" rx="80" ry="18" fill="#2563eb"/>
                <ellipse cx="120" cy="62" rx="80" ry="16" fill="#60a5fa"/>
                <ellipse cx="120" cy="62" rx="62" ry="11" fill="#1d4ed8" opacity="0.55"/>
                {{-- people on the rim --}}
                @php $ppl = [[88,40],[120,32],[152,40]]; @endphp
                @foreach($ppl as [$cx,$cy])
                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="9" fill="url(#plp)"/>
                    <path d="M{{ $cx-13 }} {{ $cy+26 }} a13 14 0 0 1 26 0 z" fill="url(#plp)"/>
                @endforeach
                {{-- leads dropping out --}}
                <circle cx="120" cy="202" r="7" fill="#2563eb"/>
                <circle cx="48" cy="150" r="6" fill="#3b82f6"/><path d="M70 138 L52 148" stroke="#93c5fd" stroke-width="2" stroke-dasharray="3 3"/>
                <circle cx="196" cy="150" r="6" fill="#3b82f6"/><path d="M172 138 L190 148" stroke="#93c5fd" stroke-width="2" stroke-dasharray="3 3"/>
                <circle cx="30" cy="120" r="4" fill="#93c5fd"/>
                <circle cx="212" cy="120" r="4" fill="#93c5fd"/>
            </svg>
        </div>
        <div class="pl-hero-r">
            <h1>Lead Pipeline</h1>
            <div class="sub">Track leads from inquiry to booking.</div>
            <div class="pl-feats">
                <div class="pl-feat">
                    <span class="pl-feat-ico" style="background:rgba(37,99,235,0.12);color:var(--info-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <b>Remembers Every Customer</b>
                    <p>Never forget an important lead.</p>
                </div>
                <div class="pl-feat">
                    <span class="pl-feat-ico" style="background:rgba(16,185,129,0.12);color:var(--ok-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
                    <b>Tracks Deal Progress</b>
                    <p>See exactly where each lead stands.</p>
                </div>
                <div class="pl-feat">
                    <span class="pl-feat-ico" style="background:rgba(239,68,68,0.12);color:var(--bad-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg></span>
                    <b>Prioritizes Hot Leads</b>
                    <p>Focus on the leads most likely to close.</p>
                </div>
                <div class="pl-feat">
                    <span class="pl-feat-ico" style="background:rgba(139,92,246,0.12);color:var(--accent-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></span>
                    <b>Organizes Sales Flow</b>
                    <p>Visualize your path to monthly goals.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="pl-sec-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91 0z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg><b>What each of these does</b></div>
    {{-- Was "Unlock powerful tools to convert more leads into bookings",
         which promises an outcome and frames three working controls as
         something you have not got yet. --}}
    <p class="pl-sec-sub">Three things you can do with a lead from here.</p>
    <div class="pl-cc">
        {{-- Direct Message Center --}}
        <div class="pl-cc-card">
            <div class="pl-cc-h"><span class="ic" style="background:rgba(37,99,235,0.12);color:var(--info-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><b>Direct Message Center</b></div>
            <p>Every conversation with this lead, in one place, on-platform messaging.</p>
            <div class="pl-cc-prev">
                <div class="pl-chat-hd"><span class="pl-chat-av" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">{{ $leads->first()['name'] ?? false ? strtoupper(substr($leads->first()['name'],0,1)) : 'L' }}</span><div><b>{{ \Illuminate\Support\Str::limit($leads->first()['name'] ?? 'New Lead', 16) }}</b><p>{{ \Illuminate\Support\Str::limit($leads->first()['location'] ?? 'Location', 14) }}</p></div></div>
                {{-- Two messages used to sit here, in bubbles, timed at
                     11:30 and 11:32, under the real name and initials of this
                     professional's most recent lead. Nobody wrote them. A
                     professional could read their own screen and believe that
                     person had got in touch.

                     Four icons went with them: chat, mail, phone, file. None
                     did anything. --}}
                <p style="font-size:12.5px;color:var(--text-muted);line-height:1.55;margin:0;">
                    Messages with your leads are kept in the Message Center, under the event each one
                    is about.
                </p>
            </div>
            <a href="{{ route('professional.chat.index') }}" class="pl-cc-btn" style="background:#2563eb;">Open Message Center <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        {{-- Was "Instant Proposal Generator". Removed rather than renamed
             (R29, investigated for Sir Peter 2026-08-05): it generated nothing
             — no template, no pricing rule, no written language. It was a
             picture of a proposal with a button that opened the ordinary
             proposals page. A rename would have promised a template library
             that does not exist. --}}
        <div class="pl-cc-card">
            <div class="pl-cc-h"><span class="ic" style="background:rgba(16,185,129,0.12);color:var(--ok-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span><b>Send a Proposal</b></div>
            <p>Open the proposal form and write one.</p>
            <div class="pl-cc-prev">
                <div class="pl-prop-k">Proposal for</div>
                <div style="font-size:11px;font-weight:800;color:var(--text-primary);margin-bottom:6px;">{{ \Illuminate\Support\Str::limit($leads->first()['name'] ?? 'New Lead', 20) }}</div>
                <div class="pl-prop-amt">{{ ($v = $leads->first()['valueHigh'] ?? null) ? $money($v) : '—' }}</div>
                <div class="pl-prop-k" style="margin-bottom:8px;">Estimated Total</div>
                {{-- Event Details, Services Included, Pricing Breakdown,
                     Terms & Conditions: four green ticks, always ticked, on
                     a proposal nobody has written yet. They read as a list of
                     what is already in it. --}}
            </div>
            <a href="{{ route('professional.proposals.index') }}" class="pl-cc-btn" style="background:#047857;">Create Proposal <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        {{-- A "Follow-Up Reminders" card stood here.

             It drew a three-step flow: the lead gets your proposal, three
             days pass with no reply, you get a reminder. Nothing sets that
             reminder and nothing sends it. It was a picture of a feature,
             and the one thing a picture of a feature does is stop anybody
             asking for the feature.

             The honest version of it is small — a date on a lead and a
             notice when it passes — and it is worth building. Until then
             the card is gone rather than illustrated. --}}
        {{-- Probability Predictor --}}
        <div class="pl-cc-card">
            <div class="pl-cc-h"><span class="ic" style="background:rgba(249,115,22,0.12);color:var(--brand-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span><b>Your Conversion Rate</b></div>
            <p>The share of your leads that have gone on to book.</p>
            <div class="pl-cc-prev">
                {{-- This fell back to a typed-in eighty-two when the real
                     rate was zero, so a professional who had never had a
                     lead was shown a high score and the word High. The rate
                     is real and so is zero; what it needed was a way to say
                     "nothing has come in yet", which is a different sentence
                     from "nothing has booked". --}}
                @php $pct = max(0, min(100, (int) $conversion)); @endphp
                @if($totalFunnel < 1)
                    <p style="font-size:13px;color:var(--text-muted);line-height:1.6;margin:6px 0 0;">
                        No leads yet, so there is nothing to work this out from. It appears here once
                        clients start coming through.
                    </p>
                @else
                <div class="pl-gauge">
                    <svg viewBox="0 0 120 64" fill="none" style="width:100%;height:100%;">
                        <path d="M10 60 A50 50 0 0 1 110 60" stroke="var(--border-color)" stroke-width="11" fill="none" stroke-linecap="round"/>
                        <path d="M10 60 A50 50 0 0 1 110 60" stroke="url(#plg)" stroke-width="11" fill="none" stroke-linecap="round" stroke-dasharray="157" stroke-dashoffset="{{ 157 - (157 * $pct / 100) }}"/>
                        <defs><linearGradient id="plg" x1="10" y1="0" x2="110" y2="0"><stop stop-color="#ef4444"/><stop offset="0.5" stop-color="#f59e0b"/><stop offset="1" stop-color="#10b981"/></linearGradient></defs>
                    </svg>
                    {{-- The figure sits inside the arc; the sentence does not.
                         The caption is positioned over the gauge, so a line
                         longer than a couple of words runs across it. --}}
                    <div class="pct"><b>{{ $pct }}%</b></div>
                </div>
                <p style="font-size:12px;color:var(--text-muted);line-height:1.55;margin:4px 0 0;text-align:center;">
                    {{ $pct }} of every 100 leads booked,
                    from {{ $totalFunnel }} {{ $totalFunnel === 1 ? 'lead' : 'leads' }} so far.
                </p>
                @endif
                {{-- Four ticks used to sit here: Location Match, High Budget,
                     Fast Response, Engaged Lead. All four were ticked for
                     everybody, always. Nothing on the platform measures any
                     of them, so they were a row of green marks that said the
                     same thing to a professional doing well and one doing
                     badly. --}}
            </div>
        </div>
    </div>

    {{-- ════════ Bottom banner ════════ --}}
    <div class="pl-banner">
        <span class="badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></span>
        {{-- "More Leads. Better Deals. Bigger Business" and "close more
             bookings" are promises about what the platform will do for
             somebody's business, which is what Sir Peter's rules on claims
             forbid. This says what the page is instead. --}}
        <div class="pl-banner-txt"><b>Your leads, in one place.</b><p>Every enquiry and proposal, with where each one has got to.</p></div>
        <a href="{{ route('professional.proposals.index') }}">View All Leads <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
</div>
@endsection
