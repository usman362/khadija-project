@extends($layout)

@section('title', 'Disputes & Resolution')
@section('page-title', 'Disputes & Resolution')
@section('page-subtitle', 'Resolve booking issues, track cases, review evidence, and see decisions.')

@php
    use App\Domain\Disputes\DisputeStates;

    /*
     * Rule R34 Phase 2 — a party's own cases.
     *
     * Both parties get the same screen. Nothing here says who was at fault,
     * because §7 is clear that filing is not a finding, and a list that put
     * "disputed" next to a professional's name would be a public score moving
     * on an unproven allegation.
     */
    $badge = fn ($state) => match ($state) {
        DisputeStates::DECIDED, DisputeStates::CLOSED       => 'dsp-done',
        DisputeStates::FORMAL_INVESTIGATION,
        DisputeStates::OUTSIDE_ESCALATION                   => 'dsp-review',
        DisputeStates::WITHDRAWN, DisputeStates::EXPIRED    => 'dsp-shut',
        default                                             => 'dsp-open',
    };

    $f = $filters;
    $carry = array_filter([
        'tab'      => $f['tab'] !== 'all' ? $f['tab'] : null,
        'range'    => $f['range'] !== 'all' ? $f['range'] : null,
        'taxonomy' => $f['taxonomy'] ?: null,
    ]);
    $link = fn (array $over = []) => route('disputes.index', array_filter(
        array_merge($carry, $over), fn ($v) => $v !== null && $v !== ''
    ));

    // The other side of a case is a client when you are the professional and
    // the other way round, so the one button on this page is named for whoever
    // the reader would actually be messaging.
    $otherSide = $viewer === 'professional' ? 'Client' : 'Professional';
@endphp

@push('styles')
    @include('disputes._styles')
<style>
    .dr-layout { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 20px; align-items: start; }

    .dr-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
    .dr-head h1 { font-size: 23px; font-weight: 800; margin: 0 0 4px; color: var(--text-primary); }
    .dr-head p { font-size: 13px; color: var(--text-muted); margin: 0; }
    .dr-cta { display: inline-flex; gap: 9px; flex-wrap: wrap; }
    .dr-btn { display: block; border-radius: 10px; padding: 9px 16px; text-decoration: none; font-size: 13px; text-align: center; border: 1px solid var(--border-color); color: var(--text-primary); background: var(--bg-card); }
    .dr-btn b { display: block; font-weight: 800; line-height: 1.25; }
    .dr-btn span { display: block; font-size: 11px; color: var(--text-muted); }
    .dr-btn.primary { background: var(--accent-blue); border-color: var(--accent-blue); color: #fff; }
    .dr-btn.primary span { color: rgba(255,255,255,.82); }

    /* Tiles */
    .dr-stats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .dr-stat { display: flex; gap: 12px; align-items: flex-start; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 13px; padding: 14px 15px; text-decoration: none; }
    .dr-stat:hover { border-color: var(--accent-blue); }
    .dr-stat.on { border-color: var(--accent-blue); box-shadow: 0 0 0 1px var(--accent-blue) inset; }
    .dr-stat .ic { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
    .dr-stat .ic.a { background: rgba(16,185,129,.13); }
    .dr-stat .ic.b { background: rgba(245,158,11,.15); }
    .dr-stat .ic.c { background: rgba(59,130,246,.13); }
    .dr-stat .ic.d { background: rgba(16,185,129,.13); }
    .dr-stat > span:last-child { display: block; min-width: 0; }
    .dr-stat .lbl { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .dr-stat .n { display: block; font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1.15; margin: 1px 0 2px; }
    .dr-stat .note { display: block; font-size: 11px; color: var(--text-muted); line-height: 1.35; }

    /* Before you file */
    .dr-steps { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 10px; align-items: start; margin: 16px 0 4px; }
    .dr-step { text-align: center; padding: 0 4px; }
    .dr-step .row { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 10px; }
    .dr-step .num, .dr-step .ic { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; }
    .dr-step .num { background: var(--bg-hover, rgba(120,120,120,.09)); color: var(--text-primary); border: 1px solid var(--border-color); }
    .dr-step .ic { background: rgba(59,130,246,.12); }
    .dr-step b { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .dr-step span { font-size: 11.5px; color: var(--text-muted); line-height: 1.45; }
    .dr-mid { text-align: center; margin-top: 16px; }
    .dr-ghost { display: inline-flex; border: 1px solid var(--accent-blue); color: var(--accent-blue); border-radius: 10px; padding: 9px 18px; font-size: 12.5px; font-weight: 800; text-decoration: none; }

    /* Tabs + list */
    .dr-tabsbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; border-bottom: 1px solid var(--border-color); padding-bottom: 0; margin-bottom: 14px; }
    .dr-tab { padding: 9px 4px; margin-right: 12px; font-size: 13px; font-weight: 700; color: var(--text-muted); text-decoration: none; border-bottom: 2px solid transparent; white-space: nowrap; }
    .dr-tab.on { color: var(--accent-blue); border-bottom-color: var(--accent-blue); }
    .dr-tabs-right { margin-left: auto; display: inline-flex; gap: 8px; padding-bottom: 7px; }
    .dr-sel { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 9px; padding: 7px 10px; font-size: 12.5px; font-weight: 700; color: var(--text-primary); font-family: inherit; cursor: pointer; }

    .dr-none { padding: 44px 20px; text-align: center; }
    .dr-none .art { font-size: 40px; opacity: .5; }
    .dr-none h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 10px 0 6px; }
    .dr-none p { font-size: 13px; color: var(--text-muted); margin: 0 0 16px; line-height: 1.55; }

    /* Common issues */
    .dr-issues { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 11px; }
    .dr-issue { display: flex; gap: 11px; align-items: center; border: 1px solid var(--border-color); border-radius: 12px; padding: 13px 14px; text-decoration: none; background: var(--bg-card); }
    .dr-issue:hover { border-color: var(--accent-blue); }
    .dr-issue .ic { width: 32px; height: 32px; border-radius: 9px; background: rgba(59,130,246,.11); display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
    .dr-issue > span:nth-child(2) { display: block; min-width: 0; }
    .dr-issue b { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .dr-issue span span { display: block; font-size: 11.5px; color: var(--text-muted); line-height: 1.4; }
    .dr-issue .arw { margin-left: auto; color: var(--text-muted); font-size: 15px; }
    .dr-note { display: flex; gap: 10px; align-items: flex-start; background: var(--bg-hover, rgba(59,130,246,.05)); border: 1px solid var(--border-color); border-radius: 11px; padding: 12px 15px; font-size: 12.5px; color: var(--text-muted); line-height: 1.5; margin-top: 14px; }

    /* Rail */
    .dr-rail { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 20px; }
    .dr-rc { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 13px; padding: 15px; }
    .dr-rc h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin: 0 0 12px; display: flex; align-items: center; gap: 7px; }
    .dr-rc h4 .pill { margin-left: auto; font-size: 10px; font-weight: 800; padding: 3px 9px; border-radius: 999px; background: rgba(16,185,129,.14); color: #047857; }
    .dr-flow { list-style: none; margin: 0; padding: 0; counter-reset: f; }
    .dr-flow li { counter-increment: f; display: flex; gap: 10px; padding: 7px 0; }
    .dr-flow li::before { content: counter(f); flex-shrink: 0; width: 20px; height: 20px; border-radius: 50%; background: rgba(59,130,246,.12); color: var(--accent-blue); font-size: 10.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
    .dr-flow li > span { display: block; min-width: 0; }
    .dr-flow b { display: block; font-size: 12px; font-weight: 800; color: var(--text-primary); }
    .dr-flow span span { display: block; font-size: 11.5px; color: var(--text-muted); line-height: 1.45; }
    .dr-ticks { list-style: none; margin: 10px 0 0; padding: 0; }
    .dr-ticks li { display: flex; gap: 8px; font-size: 12px; color: var(--text-muted); line-height: 1.45; padding: 4px 0; }
    .dr-ticks li::before { content: "✓"; color: #047857; font-weight: 800; flex-shrink: 0; }
    .dr-rc p { font-size: 12px; color: var(--text-muted); line-height: 1.55; margin: 0 0 10px; }
    .dr-raillink { display: block; font-size: 12.5px; font-weight: 800; color: var(--accent-blue); text-decoration: none; margin-top: 8px; }
    .dr-railbtn { display: block; text-align: center; border: 1px solid var(--accent-blue); color: var(--accent-blue); border-radius: 9px; padding: 8px; font-size: 12.5px; font-weight: 800; text-decoration: none; }

    @media (max-width: 1200px) { .dr-layout { grid-template-columns: 1fr; } .dr-rail { position: static; } }
    @media (max-width: 900px) {
        .dr-stats, .dr-steps, .dr-issues { grid-template-columns: repeat(2, minmax(0,1fr)); }
    }
    @media (max-width: 600px) {
        .dr-stats, .dr-steps, .dr-issues { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="dr-head">
    <div>
        {{-- Title and subtitle are in the banner at the top of the page, in both portals. --}}
    </div>
    <div class="dr-cta">
        {{-- Named first and on the left on purpose: §2 puts direct resolution
             before a formal case, and most problems never need one. --}}
        {{-- `messages.index` is the JSON API, not the inbox page — clicking
             this dropped the client onto a screen of raw JSON. --}}
        <a class="dr-btn" href="{{ \App\Support\Inbox::urlFor() }}">
            <b>Resolve Without Filing</b><span>Try direct resolution first</span>
        </a>
        <a class="dr-btn primary" href="{{ route('disputes.create') }}">
            <b>File a Dispute</b><span>Create a new case</span>
        </a>
    </div>
</div>

@if(session('status'))<div class="dsp-flash">{{ session('status') }}</div>@endif

{{-- The tiles are the tabs. Two controls filtering one list, one above the
     other, is one control drawn twice. --}}
<div class="dr-stats">
    <a class="dr-stat {{ $f['tab'] === 'all' ? 'on' : '' }}" href="{{ $link(['tab' => null, 'page' => null]) }}">
        <span class="ic a"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></span>
        <span>
            <span class="lbl">Open Cases</span>
            <span class="n">{{ $counts['open'] }}</span>
            <span class="note">{{ $counts['open'] ? 'Cases still running' : 'No active disputes' }}</span>
        </span>
    </a>
    <a class="dr-stat {{ $f['tab'] === 'action' ? 'on' : '' }}" href="{{ $link(['tab' => 'action', 'page' => null]) }}">
        <span class="ic b"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
        <span>
            <span class="lbl">Waiting on You</span>
            <span class="n">{{ $counts['action'] }}</span>
            <span class="note">{{ $counts['action'] ? 'Needs your response' : 'No actions needed' }}</span>
        </span>
    </a>
    <a class="dr-stat {{ $f['tab'] === 'review' ? 'on' : '' }}" href="{{ $link(['tab' => 'review', 'page' => null]) }}">
        <span class="ic c"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
        <span>
            <span class="lbl">Under Review</span>
            <span class="n">{{ $counts['review'] }}</span>
            <span class="note">{{ $counts['review'] ? 'With our team' : 'No cases under review' }}</span>
        </span>
    </a>
    <a class="dr-stat {{ $f['tab'] === 'resolved' ? 'on' : '' }}" href="{{ $link(['tab' => 'resolved', 'page' => null]) }}">
        <span class="ic d"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
        <span>
            <span class="lbl">Resolved</span>
            <span class="n">{{ $counts['resolved'] }}</span>
            <span class="note">Last 30 days</span>
        </span>
    </a>
</div>

<div class="dr-layout">
    <div>
        <div class="dsp-card">
            <div>
                <div style="font-size:14.5px;font-weight:800;color:var(--text-primary);">Before You File a Dispute</div>
                <p class="dsp-sub" style="margin-top:3px;">Many issues are settled quickly by talking to the other side. These steps come first.</p>
            </div>
            <div class="dr-steps">
                <div class="dr-step">
                    <div class="row"><span class="num">1</span><span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span></div>
                    <b>Message the Other Party</b>
                    <span>Keep the conversation on GigResource so it is documented.</span>
                </div>
                <div class="dr-step">
                    <div class="row"><span class="num">2</span><span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span></div>
                    <b>Request a Resolution</b>
                    <span>Ask for a correction, refund, replacement or other remedy.</span>
                </div>
                <div class="dr-step">
                    <div class="row"><span class="num">3</span><span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span></div>
                    <b>Allow Response Time</b>
                    {{-- Not "a fair chance", and no "24–48 hours": "fair" is on
                         DecisionGuide::BANNED_WORDING, and §12 holds every
                         window for attorney review, so a number here would be
                         one nobody has agreed to. --}}
                    <span>Give the other party time to reply before escalating.</span>
                </div>
                <div class="dr-step">
                    <div class="row"><span class="num">4</span><span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg></span></div>
                    <b>Escalate if Unresolved</b>
                    <span>If it is still not settled, file a formal dispute.</span>
                </div>
            </div>
            <div class="dr-mid">
                <a class="dr-ghost" href="{{ \App\Support\Inbox::urlFor() }}">Message a {{ $otherSide }}</a>
            </div>
        </div>

        <div class="dsp-card">
            <div class="dr-tabsbar">
                @foreach($tabs as $key => $label)
                    <a class="dr-tab {{ $f['tab'] === $key ? 'on' : '' }}" href="{{ $link(['tab' => $key === 'all' ? null : $key, 'page' => null]) }}">{{ $label }}</a>
                @endforeach

                <div class="dr-tabs-right">
                    <form method="GET" action="{{ route('disputes.index') }}">
                        @if($f['tab'] !== 'all')<input type="hidden" name="tab" value="{{ $f['tab'] }}">@endif
                        @if($f['taxonomy'])<input type="hidden" name="taxonomy" value="{{ $f['taxonomy'] }}">@endif
                        <select name="range" class="dr-sel" aria-label="Time range" onchange="this.form.submit()">
                            @foreach($ranges as $key => [$label, $days])
                                <option value="{{ $key }}" @selected($f['range'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                    {{-- The mockup's "Filters" button is this: the only thing
                         there is to filter a case list by, besides state and
                         date, is what the case is about. A button opening a
                         panel with nothing in it would be a control that does
                         not control anything. --}}
                    <form method="GET" action="{{ route('disputes.index') }}">
                        @if($f['tab'] !== 'all')<input type="hidden" name="tab" value="{{ $f['tab'] }}">@endif
                        @if($f['range'] !== 'all')<input type="hidden" name="range" value="{{ $f['range'] }}">@endif
                        <select name="taxonomy" class="dr-sel" aria-label="Filter by issue" onchange="this.form.submit()">
                            <option value="">All issues</option>
                            @foreach($taxonomy as $key => $label)
                                <option value="{{ $key }}" @selected($f['taxonomy'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            @if($cases->isEmpty())
                <div class="dr-none">
                    <div class="art"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg></div>
                    @if($f['tab'] !== 'all' || $f['taxonomy'] || $f['range'] !== 'all')
                        <h3>Nothing in this view</h3>
                        <p>No case matches these filters.</p>
                        <a class="dr-ghost" href="{{ route('disputes.index') }}">Show all cases</a>
                    @else
                        <h3>No Active Disputes</h3>
                        <p>
                            You do not have any disputes at the moment.<br>
                            If something goes wrong with a booking you can file a dispute and we will help resolve it.
                        </p>
                        <a class="dr-ghost" style="background:var(--accent-blue);border-color:var(--accent-blue);color:#fff;" href="{{ route('disputes.create') }}">File Your First Dispute</a>
                    @endif
                </div>
            @else
                <table class="dsp-table">
                    <thead>
                        <tr>
                            <th>Case</th>
                            <th>Booking</th>
                            <th>Other party</th>
                            <th>What it is about</th>
                            <th>Status</th>
                            <th>Opened</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cases as $case)
                            @php $other = $case->client_id === auth()->id() ? $case->professional : $case->client; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('disputes.show', $case) }}" class="dsp-ref">{{ $case->reference }}</a>
                                    @if($needsAction($case))
                                        <div style="font-size:11px;font-weight:800;color:#b45309;margin-top:3px;">Waiting on you</div>
                                    @endif
                                </td>
                                <td>{{ $case->booking?->event?->title ?? 'Booking #' . $case->booking_id }}</td>
                                <td>{{ $other?->name ?? '—' }}</td>
                                <td>{{ $case->taxonomyLabel() }}</td>
                                <td><span class="dsp-badge {{ $badge($case->state) }}">{{ $case->stateLabel() }}</span></td>
                                <td class="dsp-when">{{ $case->created_at?->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px;">
                    <span class="dsp-when">Showing {{ $cases->firstItem() }} to {{ $cases->lastItem() }} of {{ $cases->total() }} case{{ $cases->total() === 1 ? '' : 's' }}</span>
                    <div>{{ $cases->onEachSide(1)->links() }}</div>
                </div>
            @endif
        </div>

        <div class="dsp-card">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                <div>
                    <div style="font-size:14.5px;font-weight:800;color:var(--text-primary);">Common Issues &amp; Quick Actions</div>
                    <p class="dsp-sub" style="margin-top:3px;">Choose the issue that matches your situation to get started.</p>
                </div>
                <a class="dr-raillink" style="margin:0;" href="{{ route('disputes.create') }}">View all issue types →</a>
            </div>
            <div class="dr-issues">
                @php $issueIcon = ['payment_dispute' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>', 'cancellation' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>', 'no_show' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>', 'incomplete_service' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>', 'damage_claim' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>', null => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>']; @endphp
                @foreach($issues as [$key, $label, $note])
                    {{-- Each tile lands on the filing form with the
                         classification already chosen, so the form does not ask
                         a question the tile just answered. --}}
                    <a class="dr-issue" href="{{ $key ? route('disputes.create', ['taxonomy' => $key]) : route('disputes.create') }}">
                        <span class="ic">{!! $issueIcon[$key] ?? $issueIcon[null] !!}</span>
                        <span><b>{{ $label }}</b><span>{{ $note }}</span></span>
                        <span class="arw">›</span>
                    </a>
                @endforeach
            </div>
            <div class="dr-note">
                <span style="display:inline-flex;flex-shrink:0;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg></span>
                <span>Keep all communication on GigResource. It helps us understand what happened and resolve issues faster.</span>
            </div>
        </div>
    </div>

    {{-- ── Right rail ────────────────────────────────────────────── --}}
    <aside class="dr-rail">
        <div class="dr-rc">
            <h4>How Disputes Work</h4>
            {{-- The five steps are the state machine's own path, in its own
                 words, so this panel cannot describe a process the case pages
                 do not follow. Deliberately no timeframes: §12 holds every
                 deadline for attorney review, and a number here would be one
                 nobody has agreed to. --}}
            <ol class="dr-flow">
                <li><span><b>Submitted</b><span>You file a dispute with details and evidence.</span></span></li>
                <li><span><b>Awaiting Other Party</b><span>The other party is notified and asked to respond.</span></span></li>
                <li><span><b>Under Review</b><span>Both sides' evidence is reviewed by our team.</span></span></li>
                <li><span><b>Decision Pending</b><span>A decision is prepared based on that review.</span></span></li>
                <li><span><b>Resolved</b><span>The decision is issued and the case is closed.</span></span></li>
            </ol>

            @include('disputes._platform_resolution')
        </div>

        {{-- The mockup's "Booking Protection" card claims cancellation
             coverage, refund support and replacement assistance, with an
             "Eligible" badge, on a page that is not about any one booking.
             Only the first of those is a thing the platform actually does —
             payment is held until the work is confirmed — and the other three
             are promises nobody has signed off. Peter's compliance rule bans
             stated guarantees. So this card links to the two policies that do
             exist and says only what they say. --}}
        <div class="dr-rc">
            <h4>Your Protections</h4>
            <p>Money for a booking made and paid on GigResource is held by the payment processor until the work is confirmed, so a dispute is raised before anyone is paid out.</p>
            <a class="dr-raillink" href="{{ url('/payment-policy') }}">Payment policy →</a>
            <a class="dr-raillink" href="{{ url('/cancellation-policy') }}">Cancellation policy →</a>
        </div>

        <div class="dr-rc">
            <h4>Need Help?</h4>
            <p>Read the answers to common questions, or ask our support team.</p>
            <a class="dr-railbtn" href="{{ route('public.faq') }}">Visit the FAQ</a>
            <a class="dr-raillink" href="{{ \App\Domain\Forms\FormRegistry::url('support_request') }}">Contact Support →</a>
        </div>
    </aside>
</div>
@endsection
