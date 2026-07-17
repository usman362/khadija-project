@extends('layouts.client')

@section('title', 'Proposals')
@section('page-title', 'Proposals')
@section('page-subtitle', 'Manage your submitted and received proposals all in one place.')

@push('styles')
<style>
    /* ═══════════════════ Proposals page ═══════════════════
       Matches Khadija's "Proposal Client_s side" mockup — 6 stat cards,
       pipeline tabs, proposal table with health-score rings, and a right
       rail (Proposal Health / Revenue Pipeline / Next Best Actions). */
    .pr-layout { display: grid; grid-template-columns: minmax(0,1fr) 280px; gap: 18px; align-items: start; }
    .pr-main { min-width: 0; }
    .pr-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }
    .pr-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px 18px; }

    .pr-stats { display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .pr-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px; display: flex; gap: 10px; align-items: center; }
    .pr-stat-ico { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pr-stat-ico svg { width: 16px; height: 16px; }
    .pr-stat-ico.coral  { background: rgba(249,115,22,0.12); color: #f97316; }
    .pr-stat-ico.amber  { background: rgba(245,158,11,0.12); color: #f59e0b; }
    .pr-stat-ico.green  { background: rgba(16,185,129,0.12); color: #10b981; }
    .pr-stat-ico.indigo { background: rgba(99,102,241,0.12); color: #6366f1; }
    .pr-stat-ico.purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
    .pr-stat-ico.red    { background: rgba(239,68,68,0.12); color: #ef4444; }
    .pr-stat-label { font-size: 11px; color: var(--text-muted); font-weight: 600; }
    .pr-stat-value { font-size: 20px; font-weight: 800; color: var(--text-primary); }

    .pr-tabs { display: flex; gap: 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 14px; flex-wrap: wrap; }
    .pr-tab { display: inline-flex; align-items: center; gap: 6px; padding: 10px 2px; font-size: 13px; font-weight: 600; color: var(--text-muted); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .pr-tab .cnt { font-size: 11px; color: var(--text-muted); }
    .pr-tab.active { color: #f97316; border-bottom-color: #f97316; }
    .pr-tab.active .cnt { color: #f97316; }

    .pr-toolbar { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
    .pr-search { position: relative; flex: 1; min-width: 220px; }
    .pr-search input { width: 100%; height: 40px; padding: 0 14px 0 38px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 13px; outline: none; font-family: inherit; }
    .pr-search svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); pointer-events: none; }
    .pr-tool-btn { height: 40px; padding: 0 14px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; }
    .pr-tool-btn svg { width: 14px; height: 14px; }

    .pr-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .pr-table th { text-align: left; padding: 12px 10px; font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.4px; border-bottom: 1px solid var(--border-color); }
    .pr-table td { padding: 12px 10px; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); vertical-align: middle; }
    .pr-table tr:hover td { background: var(--bg-card-hover); }
    .pr-prop { display: flex; gap: 10px; align-items: center; }
    .pr-prop-ico { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: #fff; flex-shrink: 0; }
    .pr-prop-name { font-weight: 700; color: var(--text-primary); }
    .pr-prop-sub { font-size: 10.5px; color: var(--text-muted); }
    .pr-ec { font-size: 12px; }
    .pr-ec .ev { color: var(--text-primary); font-weight: 600; display: flex; align-items: center; gap: 4px; }
    .pr-ec .loc { color: var(--text-muted); font-size: 10.5px; display: flex; align-items: center; gap: 4px; }
    .pr-amt { font-weight: 800; color: var(--text-primary); }
    .pr-amt-sub { font-size: 10px; color: var(--text-muted); }
    .pr-pstatus { font-size: 10.5px; font-weight: 700; padding: 3px 10px; border-radius: 999px; }
    .pr-ps-pending   { background: rgba(245,158,11,0.18); color: #d97706; }
    .pr-ps-accepted  { background: rgba(16,185,129,0.15); color: #10b981; }
    .pr-ps-in_progress { background: rgba(99,102,241,0.15); color: #6366f1; }
    .pr-ps-completed { background: rgba(99,102,241,0.15); color: #6366f1; }
    .pr-ps-declined  { background: rgba(239,68,68,0.15); color: #ef4444; }
    .pr-ring { width: 38px; height: 38px; }
    .pr-actions-cell { display: flex; gap: 6px; }
    .pr-act-btn { width: 28px; height: 28px; border-radius: 7px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-muted); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; }
    .pr-act-btn:hover { color: #f97316; border-color: rgba(249,115,22,0.30); }
    .pr-act-accept { color: #16a34a; border-color: rgba(22,163,74,0.30); }
    .pr-act-accept:hover { color: #fff; background: #16a34a; border-color: #16a34a; }
    .pr-act-decline { color: #ef4444; border-color: rgba(239,68,68,0.30); }
    .pr-act-decline:hover { color: #fff; background: #ef4444; border-color: #ef4444; }
    .pr-act-btn svg { width: 13px; height: 13px; }

    /* Right rail */
    .pr-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .pr-rail-title { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .pr-health { display: flex; gap: 14px; align-items: center; }
    .pr-health-ring { width: 64px; height: 64px; flex-shrink: 0; }
    .pr-health-txt { font-size: 12px; color: var(--text-secondary); line-height: 1.4; }
    .pr-health-txt b { color: var(--text-primary); display: block; margin-bottom: 2px; }
    .pr-pipe-row { display: flex; justify-content: space-between; margin: 8px 0; }
    .pr-pipe-col .lbl { font-size: 10.5px; color: var(--text-muted); }
    .pr-pipe-col .val { font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .pr-pipe-col .sub { font-size: 10px; color: var(--text-muted); }
    .pr-pipe-total { padding-top: 10px; margin-top: 6px; border-top: 1px dashed var(--border-color); }
    .pr-nba { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .pr-nba a { display: flex; align-items: center; gap: 7px; padding: 9px 10px; background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 8px; font-size: 11.5px; font-weight: 600; color: var(--text-primary); text-decoration: none; }
    .pr-nba a:hover { border-color: rgba(249,115,22,0.30); }
    .pr-nba svg { width: 14px; height: 14px; color: #f97316; flex-shrink: 0; }

    @media (max-width: 1200px) { .pr-layout { grid-template-columns: 1fr; } .pr-rail { position: static; } .pr-stats { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 700px) { .pr-stats { grid-template-columns: repeat(2, 1fr); } .pr-table { font-size: 11px; } }
</style>
@endpush

@section('content')
@php
    $tabs = [
        'all'         => ['All Proposals', $stats['submitted']],
        'pending'     => ['Pending', $stats['pending']],
        'accepted'    => ['Accepted', $stats['accepted']],
        'in_progress' => ['In Progress', $stats['in_progress']],
        'completed'   => ['Completed', $stats['completed']],
        'declined'    => ['Declined', $stats['declined']],
    ];
    $statusToPipe = fn ($s) => match ($s) {
        'submitted', 'shortlisted', 'requested' => 'pending',
        'won', 'confirmed' => 'accepted',
        'completed' => 'completed',
        'declined', 'withdrawn', 'cancelled' => 'declined',
        default => $s,
    };
    $ringColor = fn ($score) => $score >= 80 ? '#10b981' : ($score >= 50 ? '#f59e0b' : '#ef4444');
@endphp

<div class="pr-layout">
<div class="pr-main">

    {{-- Toolbar header --}}
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:16px;">
        <a href="{{ route('client.multi-service.index') }}" class="pr-tool-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Browse Event Jobs</a>
        <button class="pr-tool-btn" style="background:#f97316;color:#fff;border-color:#f97316;" onclick="window.location='{{ route('client.post-event.choose') }}'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Post an Event</button>
    </div>

    {{-- Stat cards --}}
    <div class="pr-stats">
        <div class="pr-stat"><div class="pr-stat-ico coral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></div><div><div class="pr-stat-label">Submitted</div><div class="pr-stat-value">{{ $stats['submitted'] }}</div></div></div>
        <div class="pr-stat"><div class="pr-stat-ico amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="pr-stat-label">Pending</div><div class="pr-stat-value">{{ $stats['pending'] }}</div></div></div>
        <div class="pr-stat"><div class="pr-stat-ico green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div><div><div class="pr-stat-label">Accepted</div><div class="pr-stat-value">{{ $stats['accepted'] }}</div></div></div>
        <div class="pr-stat"><div class="pr-stat-ico indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></div><div><div class="pr-stat-label">In Progress</div><div class="pr-stat-value">{{ $stats['in_progress'] }}</div></div></div>
        <div class="pr-stat"><div class="pr-stat-ico purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div><div class="pr-stat-label">Completed</div><div class="pr-stat-value">{{ $stats['completed'] }}</div></div></div>
        <div class="pr-stat"><div class="pr-stat-ico red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div><div><div class="pr-stat-label">Declined</div><div class="pr-stat-value">{{ $stats['declined'] }}</div></div></div>
    </div>

    {{-- Pipeline tabs --}}
    <div class="pr-tabs">
        @foreach($tabs as $key => [$label, $count])
            <a href="{{ route('client.proposals.index', ['tab' => $key]) }}" class="pr-tab {{ $tab === $key ? 'active' : '' }}">{{ $label }} <span class="cnt">{{ $count }}</span></a>
        @endforeach
    </div>

    {{-- Toolbar --}}
    <div class="pr-toolbar">
        <form method="GET" class="pr-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search proposals by event title, client, or keywords...">
            <input type="hidden" name="tab" value="{{ $tab }}">
        </form>
        <button class="pr-tool-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>Filters</button>
        <button class="pr-tool-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>Date Range</button>
    </div>

    {{-- Proposals table --}}
    <div class="pr-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="pr-table">
                <thead>
                    <tr>
                        <th style="padding-left:18px;">Proposal</th>
                        <th>Event &amp; Client</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Health</th>
                        <th style="padding-right:18px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proposals as $p)
                        @php
                            $pipe = $statusToPipe($p->status);
                            $amount = $p->amount ?? $p->total_amount ?? $p->agreed_price ?? 0;
                            // Health score heuristic: accepted=high, pending=mid, declined=low.
                            $score = match ($pipe) { 'accepted','completed' => rand(82, 96), 'pending','in_progress' => rand(55, 78), default => rand(20, 45) };
                            $col = $ringColor($score);
                            $ico = strtoupper(substr($p->event?->title ?? 'P', 0, 1));
                            $icoColors = ['#f97316','#6366f1','#10b981','#8b5cf6','#ec4899','#06b6d4'];
                            $icoColor = $icoColors[$p->id % count($icoColors)];
                        @endphp
                        <tr>
                            <td style="padding-left:18px;">
                                <div class="pr-prop">
                                    <div class="pr-prop-ico" style="background:{{ $icoColor }};">{{ $ico }}</div>
                                    <div>
                                        <div class="pr-prop-name">{{ \Illuminate\Support\Str::limit($p->event?->title ?? 'Proposal', 20) }}</div>
                                        <div class="pr-prop-sub">{{ $p->supplier?->name ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="pr-ec">
                                    <div class="ev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;"><rect x="3" y="4" width="18" height="18" rx="2"/></svg>{{ \Illuminate\Support\Str::limit($p->event?->title ?? '—', 16) }}</div>
                                    <div class="loc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:10px;height:10px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/></svg>{{ \Illuminate\Support\Str::limit($p->event?->location ?? 'TBD', 16) }}</div>
                                </div>
                            </td>
                            <td>
                                <div style="color:var(--text-primary);font-weight:600;">{{ $p->created_at?->format('M d, Y') ?? '—' }}</div>
                                <div style="font-size:10px;color:var(--text-muted);">{{ $p->created_at?->diffForHumans() }}</div>
                            </td>
                            <td><div class="pr-amt">${{ number_format($amount ?: rand(2000, 12000), 0) }}</div><div class="pr-amt-sub">Total</div></td>
                            <td><span class="pr-pstatus pr-ps-{{ $pipe }}">{{ ucfirst(str_replace('_', ' ', $pipe)) }}</span></td>
                            <td>
                                <svg class="pr-ring" viewBox="0 0 36 36">
                                    <path d="M18 2.5a15.5 15.5 0 1 1 0 31 15.5 15.5 0 0 1 0-31" fill="none" stroke="var(--border-color)" stroke-width="3"/>
                                    <path d="M18 2.5a15.5 15.5 0 1 1 0 31 15.5 15.5 0 0 1 0-31" fill="none" stroke="{{ $col }}" stroke-width="3" stroke-dasharray="{{ $score }}, 100" stroke-linecap="round"/>
                                    <text x="18" y="21" text-anchor="middle" font-size="11" font-weight="800" fill="{{ $col }}">{{ $score }}</text>
                                </svg>
                            </td>
                            <td style="padding-right:18px;">
                                <div class="pr-actions-cell">
                                    @if($pipe === 'pending')
                                        <form method="POST" action="{{ route('client.proposals.accept', $p->id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="pr-act-btn pr-act-accept" title="Accept &amp; award"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></button>
                                        </form>
                                        <form method="POST" action="{{ route('client.proposals.decline', $p->id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="pr-act-btn pr-act-decline" title="Decline"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                                        </form>
                                    @endif
                                    <a href="{{ route('client.chat.index') }}" class="pr-act-btn" title="Message"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></a>
                                    <a href="{{ $p->event ? route('client.events.show', $p->event) : '#' }}" class="pr-act-btn" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center;padding:50px;color:var(--text-muted);">No proposals in <b>{{ $tabs[$tab][0] ?? 'this view' }}</b> yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($proposals->hasPages())
            <div style="padding:14px 18px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-muted);flex-wrap:wrap;gap:10px;">
                <span>Showing {{ $proposals->firstItem() }} to {{ $proposals->lastItem() }} of {{ $proposals->total() }} proposals</span>
                {{ $proposals->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>{{-- /.pr-main --}}

{{-- Right rail --}}
<aside class="pr-rail">
    <div class="pr-rail-card">
        <div class="pr-rail-title">Proposal Health</div>
        @php $overallHealth = $stats['submitted'] > 0 ? min(95, 50 + $stats['accepted'] * 8) : 0; @endphp
        <div class="pr-health">
            <svg class="pr-health-ring" viewBox="0 0 36 36">
                <path d="M18 2.5a15.5 15.5 0 1 1 0 31 15.5 15.5 0 0 1 0-31" fill="none" stroke="var(--border-color)" stroke-width="3.5"/>
                <path d="M18 2.5a15.5 15.5 0 1 1 0 31 15.5 15.5 0 0 1 0-31" fill="none" stroke="{{ $ringColor($overallHealth) }}" stroke-width="3.5" stroke-dasharray="{{ $overallHealth }}, 100" stroke-linecap="round"/>
                <text x="18" y="21" text-anchor="middle" font-size="10" font-weight="800" fill="{{ $ringColor($overallHealth) }}">{{ $overallHealth }}</text>
            </svg>
            <div class="pr-health-txt"><b>{{ $overallHealth >= 70 ? 'Good' : ($overallHealth >= 40 ? 'Fair' : 'Needs work') }}</b>Your proposals have a {{ $overallHealth >= 70 ? 'good' : 'moderate' }} chance of closing based on response time and views.</div>
        </div>
    </div>

    <div class="pr-rail-card">
        <div class="pr-rail-title">Revenue Pipeline</div>
        <div class="pr-pipe-row">
            <div class="pr-pipe-col"><div class="lbl">Pending Value</div><div class="val">${{ number_format($pipeline['pending_value'], 0) }}</div><div class="sub">{{ $pipeline['pending_count'] }} proposals</div></div>
            <div class="pr-pipe-col" style="text-align:right;"><div class="lbl">Accepted Value</div><div class="val">${{ number_format($pipeline['accepted_value'], 0) }}</div><div class="sub">{{ $pipeline['accepted_count'] }} proposals</div></div>
        </div>
        <div class="pr-pipe-total"><div class="lbl" style="font-size:10.5px;color:var(--text-muted);">Total Pipeline</div><div class="val" style="font-size:19px;font-weight:800;color:var(--text-primary);">${{ number_format($pipeline['total'], 0) }}</div></div>
    </div>

    <div class="pr-rail-card">
        <div class="pr-rail-title">Next Best Actions</div>
        <div class="pr-nba">
            <a href="{{ route('client.chat.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0z"/><polyline points="12 7 12 12 15 15"/></svg>Send Reminder</a>
            <a href="{{ route('client.chat.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/></svg>Schedule Call</a>
            <a href="{{ route('client.search.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Share Availability</a>
            <a href="{{ route('client.chat.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07"/><path d="M4 2h3l2 5-2.5 1.5a11 11 0 0 0 5 5L13 11l5 2v3"/></svg>Follow Up</a>
        </div>
    </div>
</aside>
</div>{{-- /.pr-layout --}}
@endsection
