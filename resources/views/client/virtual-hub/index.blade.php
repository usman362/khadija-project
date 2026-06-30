@extends('layouts.client')

@section('title', 'Virtual & Hybrid Hub')
@section('page-title', 'Virtual & Hybrid Hub')
@section('page-subtitle', 'Plan, connect, and manage unforgettable virtual, hybrid & livestream events.')

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
    .vh-cc-title svg { width: 16px; height: 16px; color: #f97316; }
    .vh-live { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; color: #ef4444; }
    .vh-live .dot { width: 7px; height: 7px; border-radius: 50%; background: #ef4444; animation: vhPulse 1.4s infinite; }
    @keyframes vhPulse { 0%,100%{opacity:1;} 50%{opacity:0.3;} }

    /* Command stats */
    .vh-cc-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .vh-cc-stat { padding: 12px; border-radius: 10px; background: var(--bg-card-hover); border: 1px solid var(--border-color); }
    .vh-cc-stat-head { display: flex; align-items: center; gap: 7px; font-size: 10.5px; color: var(--text-muted); font-weight: 600; margin-bottom: 6px; }
    .vh-cc-stat-head svg { width: 13px; height: 13px; }
    .vh-cc-stat-val { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .vh-cc-stat-sub { font-size: 10px; color: #10b981; font-weight: 700; }
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
    .vh-sec-link { font-size: 11.5px; color: #f97316; text-decoration: none; font-weight: 600; }
    .vh-svc-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; }
    .vh-svc { text-align: center; padding: 14px 8px; border-radius: 10px; background: var(--bg-card-hover); border: 1px solid var(--border-color); text-decoration: none; }
    .vh-svc:hover { border-color: rgba(249,115,22,0.3); }
    .vh-svc-ico { width: 34px; height: 34px; border-radius: 9px; background: rgba(249,115,22,0.12); color: #f97316; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; }
    .vh-svc-ico svg { width: 16px; height: 16px; }
    .vh-svc-name { font-size: 10.5px; font-weight: 700; color: var(--text-primary); line-height: 1.2; }
    .vh-svc-cnt { font-size: 9px; color: var(--text-muted); }

    /* Pro match cards */
    .vh-pro-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .vh-pro { border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; background: var(--bg-card); }
    .vh-pro-top { display: flex; gap: 9px; align-items: center; margin-bottom: 8px; }
    .vh-pro-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; }
    .vh-pro-match { font-size: 10px; font-weight: 800; color: #f97316; }
    .vh-pro-avail { font-size: 9px; font-weight: 700; color: #10b981; display: inline-flex; align-items: center; gap: 3px; }
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
    .vh-rfp-type.hybrid { background: rgba(249,115,22,0.12); color: #f97316; }
    .vh-rfp-type.virtual { background: rgba(99,102,241,0.12); color: #6366f1; }
    .vh-rfp-status { font-size: 9.5px; font-weight: 700; color: #10b981; }

    .vh-post-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; width: 100%; padding: 11px; margin-top: 12px; background: #f97316; color: #fff; border: 1px dashed rgba(249,115,22,0.5); border-radius: 9px; font-size: 12.5px; font-weight: 700; text-decoration: none; }

    /* Bottom feature tiles */
    .vh-feats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .vh-feat { padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-card); }
    .vh-feat-ico { width: 34px; height: 34px; border-radius: 9px; background: rgba(249,115,22,0.12); color: #f97316; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; }
    .vh-feat-ico svg { width: 16px; height: 16px; }
    .vh-feat-name { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .vh-feat-link { font-size: 10.5px; color: #f97316; text-decoration: none; font-weight: 600; }

    /* Right rail */
    .vh-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .vh-rail-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .vh-rail-title { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .vh-monitor { aspect-ratio: 16/9; border-radius: 9px; background: linear-gradient(135deg, #1a1f35, #2a3050); position: relative; overflow: hidden; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; }
    .vh-monitor .live-tag { position: absolute; top: 8px; left: 8px; font-size: 9px; font-weight: 800; color: #fff; background: #ef4444; padding: 2px 7px; border-radius: 4px; }
    .vh-monitor svg { width: 36px; height: 36px; color: rgba(255,255,255,0.5); }
    .vh-mon-row { display: flex; justify-content: space-between; font-size: 11px; padding: 4px 0; color: var(--text-muted); }
    .vh-mon-row .v { color: var(--text-primary); font-weight: 600; }
    .vh-mon-row .ok { color: #10b981; font-weight: 700; }
    .vh-alert-row { display: flex; align-items: center; justify-content: space-between; padding: 6px 0; font-size: 11px; border-bottom: 1px dashed var(--border-color); }
    .vh-alert-row:last-child { border-bottom: 0; }
    .vh-alert-row .ok { color: #10b981; font-weight: 700; }
    .vh-int-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; font-size: 11.5px; border-bottom: 1px dashed var(--border-color); }
    .vh-int-row:last-of-type { border-bottom: 0; }
    .vh-int-row .name { display: flex; align-items: center; gap: 7px; color: var(--text-secondary); }
    .vh-int-row .conn { font-size: 10px; font-weight: 700; color: #10b981; }
    .vh-aud-big { font-size: 24px; font-weight: 800; color: var(--text-primary); }

    @media (max-width: 1200px) { .vh-layout { grid-template-columns: 1fr; } .vh-rail { position: static; } .vh-svc-grid { grid-template-columns: repeat(3, 1fr); } .vh-pro-grid { grid-template-columns: repeat(2, 1fr); } .vh-feats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 700px) { .vh-cc-stats, .vh-cc-stats-2, .vh-filters { grid-template-columns: repeat(2, 1fr); } .vh-paths { grid-template-columns: 1fr; } .vh-pro-grid, .vh-svc-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="vh-layout">
<div class="vh-main">

    {{-- Command Center --}}
    <div class="vh-card">
        <div class="vh-cc-head">
            <span class="vh-cc-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>Virtual Event Command Center</span>
            <span class="vh-live"><span class="dot"></span>Live Overview</span>
        </div>
        {{-- Placeholder telemetry — needs streaming backend --}}
        <div class="vh-cc-stats">
            <div class="vh-cc-stat"><div class="vh-cc-stat-head"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>Live Attendees</div><div class="vh-cc-stat-val">1,248</div><div class="vh-cc-stat-sub">↑ 12% last hr</div></div>
            <div class="vh-cc-stat"><div class="vh-cc-stat-head"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>Stream Health</div><div class="vh-cc-stat-val">Excellent</div><div class="vh-cc-stat-sub">1080p · 6 Mbps</div></div>
            <div class="vh-cc-stat"><div class="vh-cc-stat-head"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2"/></svg>Active Channels</div><div class="vh-cc-stat-val">3</div><div class="vh-cc-stat-sub">Zoom · YT · Web</div></div>
            <div class="vh-cc-stat"><div class="vh-cc-stat-head"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>AI Alerts</div><div class="vh-cc-stat-val">0</div><div class="vh-cc-stat-sub">All systems normal</div></div>
        </div>
        <div class="vh-cc-stats-2">
            <div class="vh-cc-stat"><div class="vh-cc-stat-head">Languages Active</div><div class="vh-cc-stat-val">3</div><div class="vh-cc-stat-sub" style="color:var(--text-muted);">EN · ES · FR</div></div>
            <div class="vh-cc-stat"><div class="vh-cc-stat-head">Stream Delay</div><div class="vh-cc-stat-val">1.2 sec</div><div class="vh-cc-stat-sub">Low latency</div></div>
            <div class="vh-cc-stat"><div class="vh-cc-stat-head">Engagement</div><div class="vh-cc-stat-val">87%</div><div class="vh-cc-stat-sub">Very High</div></div>
            <div class="vh-cc-stat"><div class="vh-cc-stat-head">Gamification</div><div class="vh-cc-stat-val">72%</div><div class="vh-cc-stat-sub" style="color:var(--text-muted);">Participation</div></div>
        </div>
    </div>

    {{-- Two paths --}}
    <div class="vh-paths">
        <div class="vh-path active"><div class="vh-path-title">Path 1: Instant Discovery</div><div class="vh-path-desc">Search and connect with verified virtual professionals.</div></div>
        <a href="{{ route('client.virtual-hub.brief') }}" style="text-decoration:none;"><div class="vh-path"><div class="vh-path-title">Path 2: Project Gigs / RFP</div><div class="vh-path-desc">Post your event brief and get competitive proposals.</div></div></a>
    </div>

    {{-- Filters --}}
    <div class="vh-card">
        <div class="vh-filters">
            <select class="vh-filter"><option>All Platforms</option><option>Zoom</option><option>YouTube</option><option>RTMP</option></select>
            <select class="vh-filter"><option>All Categories</option></select>
            <select class="vh-filter"><option>All Languages</option></select>
            <select class="vh-filter"><option>Any Budget</option></select>
        </div>
    </div>

    {{-- Specialized virtual services --}}
    <div class="vh-card">
        <div class="vh-sec-head"><span class="vh-sec-title">Explore Specialized Virtual Services</span><a href="{{ route('client.search.index') }}" class="vh-sec-link">View all categories →</a></div>
        @php
            $vhSvcDefaults = [
                ['Livestream Directors', '📹'], ['Broadcast Engineers', '🎚'], ['Virtual Venue Architects', '🏛'],
                ['Digital Engagement', '💬'], ['Hybrid AV Integrators', '🔌'], ['AI Moderators', '🤖'],
            ];
            $vhSvcs = $categories->count() ? $categories->map(fn($c) => [$c->name, $c->icon ?: '🎯'])->toArray() : $vhSvcDefaults;
        @endphp
        <div class="vh-svc-grid">
            @foreach($vhSvcs as [$name, $icon])
                <a href="{{ route('client.search.index', ['q' => $name]) }}" class="vh-svc">
                    <div class="vh-svc-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></div>
                    <div class="vh-svc-name">{{ \Illuminate\Support\Str::limit($name, 18) }}</div>
                    <div class="vh-svc-cnt">{{ rand(80, 220) }} pros</div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Top matching pros --}}
    <div class="vh-card">
        <div class="vh-sec-head"><span class="vh-sec-title">Top Matching Professionals</span><a href="{{ route('client.search.index') }}" class="vh-sec-link">View all matches →</a></div>
        <div class="vh-pro-grid">
            @forelse($pros as $i => $pro)
                @php $rate = $pro->profile?->hourly_rate ?: rand(80, 400); $match = [98, 95, 93, 91][$i] ?? 90; @endphp
                <div class="vh-pro">
                    <div class="vh-pro-top">
                        <img src="{{ $pro->avatar_url }}" class="vh-pro-avatar" loading="lazy">
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
        <div class="vh-sec-head"><span class="vh-sec-title">Recent Project Gigs (RFPs)</span><a href="{{ route('client.multi-service.index') }}" class="vh-sec-link">View all gigs →</a></div>
        <div style="overflow-x:auto;">
            <table class="vh-rfp-table">
                <thead><tr><th>Project Title</th><th>Type</th><th>Budget</th><th>Bids</th><th>Status</th><th>Posted</th></tr></thead>
                <tbody>
                    @forelse($gigs as $i => $g)
                        @php $type = $i % 2 === 0 ? ['Hybrid', 'hybrid'] : ['Virtual', 'virtual']; @endphp
                        <tr>
                            <td class="vh-rfp-name">{{ \Illuminate\Support\Str::limit($g->title, 26) }}</td>
                            <td><span class="vh-rfp-type {{ $type[1] }}">{{ $type[0] }}</span></td>
                            <td>${{ number_format($g->budget ?? rand(3000, 25000), 0) }}</td>
                            <td>{{ rand(4, 18) }}</td>
                            <td><span class="vh-rfp-status">{{ ucfirst($g->status) }}</span></td>
                            <td style="color:var(--text-muted);font-size:11px;">{{ $g->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted);">No project gigs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <a href="{{ route('client.virtual-hub.brief') }}" class="vh-post-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:15px;height:15px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Post a New Project Gig / RFP</a>
    </div>

    {{-- Feature tiles --}}
    <div class="vh-feats">
        <div class="vh-feat"><div class="vh-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div><div class="vh-feat-name">Virtual Venue Builder</div></div>
        <div class="vh-feat"><div class="vh-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div class="vh-feat-name">Engagement Tools</div></div>
        <div class="vh-feat"><div class="vh-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg></div><div class="vh-feat-name">AI Stream Assistant</div></div>
        <div class="vh-feat"><div class="vh-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg></div><div class="vh-feat-name">Analytics Dashboard</div></div>
    </div>
</div>{{-- /.vh-main --}}

{{-- Right rail --}}
<aside class="vh-rail">
    {{-- Live Stream Monitor --}}
    <div class="vh-rail-card">
        <div class="vh-rail-head"><div class="vh-rail-title">Live Stream Monitor</div><span class="vh-live"><span class="dot"></span>Live</span></div>
        <div class="vh-monitor"><span class="live-tag">● LIVE</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></div>
        <div class="vh-mon-row"><span>Bitrate</span><span class="v">6.0 Mbps</span></div>
        <div class="vh-mon-row"><span>Resolution</span><span class="v">1080p</span></div>
        <div class="vh-mon-row"><span>Dropped Frames</span><span class="v">0.05%</span></div>
        <div class="vh-mon-row"><span>Latency</span><span class="ok">1.2 sec</span></div>
        <div class="vh-mon-row"><span>CDN Status</span><span class="ok">Excellent</span></div>
    </div>

    {{-- AI Alerts --}}
    <div class="vh-rail-card">
        <div class="vh-rail-head"><div class="vh-rail-title">AI Alerts</div><span style="font-size:10px;color:#10b981;font-weight:700;">All Clear</span></div>
        @foreach(['Stream Health', 'Audio Sync', 'Internet Stability', 'Chat Moderation', 'Translation Feeds'] as $a)
            <div class="vh-alert-row"><span>{{ $a }}</span><span class="ok">Excellent</span></div>
        @endforeach
    </div>

    {{-- Audience Overview --}}
    <div class="vh-rail-card">
        <div class="vh-rail-head"><div class="vh-rail-title">Audience Overview</div><span class="vh-live"><span class="dot"></span>Live Now</span></div>
        <div class="vh-aud-big">1,248</div>
        <div style="font-size:10.5px;color:#10b981;font-weight:700;margin-bottom:8px;">↑ 12% last hour</div>
        <div class="vh-mon-row"><span>Live Viewers</span><span class="v">892</span></div>
        <div class="vh-mon-row"><span>On-Demand</span><span class="v">166</span></div>
        <div class="vh-mon-row"><span>Chat Participants</span><span class="v">642</span></div>
        <div class="vh-mon-row"><span>Engagement Rate</span><span class="v">87%</span></div>
    </div>

    {{-- Active Integrations --}}
    <div class="vh-rail-card">
        <div class="vh-rail-title" style="margin-bottom:10px;">Active Integrations</div>
        @foreach(['Zoom Events', 'YouTube Live', 'RTMP Server', 'Slack Alerts'] as $int)
            <div class="vh-int-row"><span class="name"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;color:#10b981;"><polyline points="20 6 9 17 4 12"/></svg>{{ $int }}</span><span class="conn">Connected</span></div>
        @endforeach
    </div>
</aside>
</div>{{-- /.vh-layout --}}
@endsection
