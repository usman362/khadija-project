@extends('layouts.professional')

@section('title', 'Messages — Inbox')

{{-- Professional Messages — Inbox. Server-rendered: real conversations +
     thread + derived stats; compose posts to conversations.messages.store.
     Blue professional theme. --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
    $tagColors = ['green' => ['#059669','rgba(16,185,129,0.12)'], 'amber' => ['#d97706','rgba(217,119,6,0.12)'], 'red' => ['#dc2626','rgba(220,38,38,0.12)'], 'blue' => ['#2563eb','rgba(37,99,235,0.12)']];
    $typeColor = ['direct' => '#2563eb', 'booking' => '#059669', 'event' => '#7c3aed'];
@endphp

@push('styles')
<style>
    .pm { --pm: #2563eb; }
    .pm-head h1 { font-size: 24px; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 9px; }
    .pm-head h1 svg { width: 19px; height: 19px; color: #2563eb; }
    .pm-head p { font-size: 13px; color: var(--text-muted); margin: 4px 0 18px; }

    /* stats + actions */
    .pm-top { display: grid; grid-template-columns: minmax(0,1fr) 220px; gap: 16px; align-items: start; margin-bottom: 18px; }
    .pm-stats { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 12px; }
    .pm-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px; }
    .pm-stat-h { display: flex; align-items: center; gap: 8px; font-size: 11.5px; font-weight: 700; color: var(--text-muted); }
    .pm-stat-ico { width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pm-stat-ico svg { width: 14px; height: 14px; }
    .pm-stat .v { font-size: 23px; font-weight: 800; color: var(--text-primary); margin: 7px 0 2px; }
    .pm-stat .s { font-size: 11px; color: var(--text-muted); }
    .pm-actions { display: flex; flex-direction: column; gap: 10px; }
    .pm-btn-primary { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 13px; border: none; border-radius: 11px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .pm-btn-primary svg { width: 15px; height: 15px; }
    .pm-btn-ghost { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border: 1px solid var(--border-color); border-radius: 11px; background: var(--bg-card); color: var(--text-secondary); font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; }
    .pm-btn-ghost svg { width: 14px; height: 14px; color: #2563eb; }

    /* main split */
    .pm-main { display: grid; grid-template-columns: minmax(0,340px) minmax(0,1fr); gap: 16px; align-items: start; }
    .pm-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; }

    /* conversation list */
    .pm-tabs { display: flex; gap: 4px; padding: 12px 14px 0; border-bottom: 1px solid var(--border-color); }
    .pm-tab { display: inline-flex; align-items: center; gap: 6px; padding: 9px 12px; font-size: 12.5px; font-weight: 700; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .pm-tab.on { color: var(--pm); border-bottom-color: var(--pm); }
    .pm-tab .ct { font-size: 10px; background: var(--pm); color: #fff; border-radius: 999px; padding: 1px 6px; }
    .pm-search { display: flex; gap: 8px; padding: 12px 14px; }
    .pm-search-box { flex: 1; position: relative; }
    .pm-search-box svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); }
    .pm-search-box input { width: 100%; box-sizing: border-box; padding: 9px 12px 9px 34px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; }
    .pm-filter { width: 38px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .pm-filter svg { width: 15px; height: 15px; }
    .pm-subbar { display: flex; align-items: center; justify-content: space-between; padding: 0 14px 10px; font-size: 11.5px; color: var(--text-muted); }
    .pm-subbar select { border: 1px solid var(--border-color); border-radius: 7px; background: var(--bg-card); color: var(--text-secondary); font-size: 11.5px; padding: 4px 8px; font-family: inherit; }
    .pm-list { max-height: 620px; overflow-y: auto; }
    .pm-conv { display: flex; gap: 11px; padding: 13px 14px; border-top: 1px solid var(--border-color); cursor: pointer; text-decoration: none; }
    .pm-conv:hover { background: var(--bg-card-hover); }
    .pm-conv.active { background: rgba(37,99,235,0.06); border-left: 3px solid var(--pm); padding-left: 11px; }
    .pm-conv-av { width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; font-weight: 800; }
    .pm-conv-mid { flex: 1; min-width: 0; }
    .pm-conv-name { font-size: 13.5px; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pm-conv-name span { font-weight: 500; color: var(--text-muted); font-size: 12px; }
    .pm-conv-subj { font-size: 12px; font-weight: 700; color: var(--text-secondary); margin: 2px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pm-conv-prev { font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pm-conv-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; }
    .pm-tag { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
    .pm-conv-meta { text-align: right; flex-shrink: 0; }
    .pm-conv-time { font-size: 11px; color: var(--text-muted); }
    .pm-conv-badge { display: inline-block; min-width: 18px; height: 18px; line-height: 18px; text-align: center; background: #ef4444; color: #fff; font-size: 10px; font-weight: 800; border-radius: 999px; margin-top: 8px; padding: 0 5px; }
    .pm-pager { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-top: 1px solid var(--border-color); font-size: 11.5px; color: var(--text-muted); }

    /* thread */
    .pm-thread { display: flex; flex-direction: column; min-height: 620px; }
    .pm-th-head { display: flex; align-items: center; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--border-color); }
    .pm-th-av { width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 15px; font-weight: 800; }
    .pm-th-mid { flex: 1; min-width: 0; }
    .pm-th-name { font-size: 16px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
    .pm-open { font-size: 10px; font-weight: 800; color: #059669; background: rgba(16,185,129,0.12); border-radius: 5px; padding: 2px 7px; }
    .pm-th-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .pm-th-actions { display: flex; gap: 7px; }
    .pm-icon-btn { width: 34px; height: 34px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .pm-icon-btn svg { width: 16px; height: 16px; }
    .pm-msgs { flex: 1; padding: 18px; display: flex; flex-direction: column; gap: 16px; overflow-y: auto; max-height: 460px; }
    .pm-msg { display: flex; gap: 11px; max-width: 78%; }
    .pm-msg.me { flex-direction: row-reverse; margin-left: auto; }
    .pm-msg-av { width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 800; }
    .pm-msg-body { min-width: 0; }
    .pm-msg-meta { font-size: 11px; color: var(--text-muted); margin-bottom: 4px; }
    .pm-msg.me .pm-msg-meta { text-align: right; }
    .pm-bubble { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 12px; padding: 10px 13px; font-size: 13px; color: var(--text-primary); line-height: 1.5; word-break: break-word; }
    .pm-msg.me .pm-bubble { background: rgba(37,99,235,0.1); border-color: rgba(37,99,235,0.2); }
    .pm-att { display: flex; gap: 9px; margin-top: 8px; flex-wrap: wrap; }
    .pm-att-item { display: flex; align-items: center; gap: 8px; border: 1px solid var(--border-color); border-radius: 9px; padding: 8px 11px; background: var(--bg-card); }
    .pm-att-item svg { width: 16px; height: 16px; color: #dc2626; }
    .pm-att-item b { font-size: 12px; color: var(--text-primary); display: block; }
    .pm-att-item span { font-size: 10.5px; color: var(--text-muted); }
    .pm-banner { display: flex; align-items: center; gap: 10px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.22); border-radius: 10px; padding: 11px 14px; margin: 0 18px; font-size: 12.5px; font-weight: 700; color: #059669; }
    .pm-banner svg { width: 16px; height: 16px; }
    .pm-banner a { margin-left: auto; color: var(--pm); font-weight: 800; text-decoration: none; }
    .pm-ai { display: flex; align-items: center; gap: 11px; background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.18); border-radius: 11px; padding: 13px 16px; margin: 14px 18px 0; }
    .pm-ai svg.spark { width: 18px; height: 18px; color: var(--pm); flex-shrink: 0; }
    .pm-ai b { font-size: 12.5px; color: var(--text-primary); }
    .pm-ai p { font-size: 12px; color: var(--text-muted); margin: 1px 0 0; }
    .pm-ai button { margin-left: auto; padding: 7px 16px; border: 1px solid var(--pm); border-radius: 8px; background: var(--bg-card); color: var(--pm); font-size: 12px; font-weight: 800; cursor: pointer; font-family: inherit; }

    /* compose */
    .pm-compose { border-top: 1px solid var(--border-color); margin-top: 14px; padding: 14px 18px 16px; }
    .pm-c-tabs { display: flex; gap: 16px; border-bottom: 1px solid var(--border-color); margin-bottom: 12px; flex-wrap: wrap; }
    .pm-c-tab { font-size: 12.5px; font-weight: 700; color: var(--text-muted); padding-bottom: 9px; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .pm-c-tab.on { color: var(--pm); border-bottom-color: var(--pm); }
    .pm-c-box textarea { width: 100%; box-sizing: border-box; min-height: 56px; padding: 11px 13px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; resize: vertical; outline: none; }
    .pm-c-box textarea:focus { border-color: var(--pm); }
    .pm-c-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
    .pm-c-icons { display: flex; gap: 6px; }
    .pm-c-icons button { width: 32px; height: 32px; border: none; background: none; color: var(--text-muted); cursor: pointer; border-radius: 7px; display: flex; align-items: center; justify-content: center; }
    .pm-c-icons button:hover { background: var(--bg-card-hover); }
    .pm-c-icons svg { width: 16px; height: 16px; }
    .pm-send { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 9px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .pm-send svg { width: 14px; height: 14px; }
    .pm-c-foot { display: flex; align-items: center; gap: 12px; margin-top: 10px; font-size: 11.5px; color: var(--text-muted); flex-wrap: wrap; }
    .pm-c-foot label { display: flex; align-items: center; gap: 6px; }

    .pm-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); gap: 10px; padding: 60px; text-align: center; }
    .pm-empty svg { width: 42px; height: 42px; color: var(--border-color); }

    @media (max-width: 1100px) { .pm-top { grid-template-columns: 1fr; } .pm-stats { grid-template-columns: repeat(3, minmax(0,1fr)); } .pm-main { grid-template-columns: 1fr; } }
    @media (max-width: 640px) { .pm-stats { grid-template-columns: repeat(2, minmax(0,1fr)); } }
</style>
@endpush

@section('content')
<div class="pm" data-csrf="{{ csrf_token() }}">

    <div class="pm-head">
        <h1>Messages — Inbox <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg></h1>
        <p>All your conversations, event updates, documents, and payments—organized in one place.</p>
    </div>

    {{-- stats + actions --}}
    <div class="pm-top">
        <div class="pm-stats">
            <div class="pm-stat"><div class="pm-stat-h"><span class="pm-stat-ico" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>Unread</div><div class="v">{{ $stats['unread'] }}</div><div class="s">of {{ max($stats['total'], $stats['unread']) }}</div></div>
            <div class="pm-stat"><div class="pm-stat-h"><span class="pm-stat-ico" style="background:rgba(217,119,6,0.12);color:#d97706;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>Priority</div><div class="v">{{ $stats['priority'] }}</div><div class="s">Needs attention</div></div>
            <div class="pm-stat"><div class="pm-stat-h"><span class="pm-stat-ico" style="background:rgba(16,185,129,0.12);color:#059669;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg></span>Response Time</div><div class="v">{{ $stats['response'] }}</div><div class="s" style="color:#059669;">↓ 12% vs last 30 days</div></div>
            <div class="pm-stat"><div class="pm-stat-h"><span class="pm-stat-ico" style="background:rgba(220,38,38,0.12);color:#dc2626;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>Compliance</div><div class="v">{{ $stats['compliance'] }}</div><div class="s">Action required</div></div>
            <div class="pm-stat"><div class="pm-stat-h"><span class="pm-stat-ico" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>Payment Secured</div><div class="v">{{ $money($stats['escrow']) }}</div><div class="s">Across {{ $stats['escrow_convos'] }} conversations</div></div>
        </div>
        <div class="pm-actions">
            <button type="button" class="pm-btn-primary" id="pm-create"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Create Message</button>
            <button type="button" class="pm-btn-ghost" id="pm-ai-compose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.9 4.1L18 8l-4.1 1.9L12 14l-1.9-4.1L6 8l4.1-1.9L12 2z"/></svg>AI Compose</button>
        </div>
    </div>

    {{-- split --}}
    <div class="pm-main">
        {{-- conversation list --}}
        <div class="pm-card">
            <div class="pm-tabs">
                <span class="pm-tab on" data-tab="inbox">Inbox <span class="ct">{{ $tabCounts['unread'] }}</span></span>
                <span class="pm-tab" data-tab="sent">Sent @if(($tabCounts['sent'] ?? 0))<span class="ct">{{ $tabCounts['sent'] }}</span>@endif</span>
                <span class="pm-tab" data-tab="drafts">Drafts</span>
                <span class="pm-tab" data-tab="archived">Archived</span>
            </div>
            <div class="pm-search">
                <div class="pm-search-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="text" id="pm-search" placeholder="Search messages..."></div>
                <span class="pm-filter"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg></span>
            </div>
            <div class="pm-subbar">
                <select><option>All Events</option></select>
                <span>Sort: Latest</span>
            </div>
            <div class="pm-list" id="pm-list">
                @forelse($conversations as $c)
                    <a href="{{ route('professional.chat.show', $c['id']) }}" class="pm-conv {{ ($thread && $thread['id'] === $c['id']) ? 'active' : '' }}" data-name="{{ \Illuminate\Support\Str::lower($c['name'].' '.$c['subject']) }}" data-lastfrom="{{ ($c['lastFromMe'] ?? false) ? 'me' : 'them' }}">
                        <span class="pm-conv-av" style="background:{{ $typeColor[$c['type']] ?? '#2563eb' }};">{{ $c['initials'] }}</span>
                        <div class="pm-conv-mid">
                            <div class="pm-conv-name">{{ $c['name'] }} <span>({{ $c['role'] }})</span></div>
                            <div class="pm-conv-subj">{{ $c['subject'] }}</div>
                            <div class="pm-conv-prev">{{ $c['preview'] }}</div>
                            <div class="pm-conv-tags">
                                @foreach($c['tags'] as [$tname, $tcol])
                                    <span class="pm-tag" style="color:{{ ($tagColors[$tcol] ?? $tagColors['blue'])[0] }};background:{{ ($tagColors[$tcol] ?? $tagColors['blue'])[1] }};">{{ $tname }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="pm-conv-meta">
                            <div class="pm-conv-time">{{ $c['time'] }}</div>
                            @if($c['unread'] > 0)<span class="pm-conv-badge">{{ $c['unread'] }}</span>@endif
                        </div>
                    </a>
                @empty
                    <div style="padding:40px 16px;text-align:center;color:var(--text-muted);font-size:13px;">No conversations yet.</div>
                @endforelse
                <div id="pm-list-empty" style="display:none;padding:40px 16px;text-align:center;color:var(--text-muted);font-size:13px;"></div>
            </div>
            <div class="pm-pager"><span>Showing {{ count($conversations) }} of {{ $stats['total'] }} conversations</span></div>
        </div>

        {{-- thread --}}
        <div class="pm-card pm-thread">
            @if($thread)
                <div class="pm-th-head">
                    <span class="pm-th-av" style="background:#2563eb;">{{ $thread['initials'] }}</span>
                    <div class="pm-th-mid">
                        <div class="pm-th-name">{{ $thread['name'] }} <span class="pm-open">OPEN</span></div>
                        <div class="pm-th-sub">{{ $thread['subject'] }}@if($thread['date']) · {{ $thread['date'] }}@endif</div>
                    </div>
                    <div class="pm-th-actions">
                        <span class="pm-icon-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                        <span class="pm-icon-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></span>
                        <span class="pm-icon-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg></span>
                    </div>
                </div>

                <div class="pm-msgs" id="pm-msgs">
                    @forelse($thread['messages'] as $m)
                        <div class="pm-msg {{ $m['mine'] ? 'me' : '' }}">
                            <span class="pm-msg-av" style="background:{{ $m['mine'] ? '#1e293b' : '#2563eb' }};">{{ strtoupper(substr($m['sender'], 0, 1)) }}</span>
                            <div class="pm-msg-body">
                                <div class="pm-msg-meta">{{ $m['mine'] ? 'You' : $m['sender'] }} · {{ $m['time'] }}</div>
                                <div class="pm-bubble">{{ $m['body'] }}</div>
                                @if(!empty($m['attachments']))
                                    <div class="pm-att">
                                        @foreach($m['attachments'] as $a)
                                            <div class="pm-att-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><div><b>{{ $a['name'] }}</b><span>{{ $a['size'] }}</span></div></div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center;color:var(--text-muted);font-size:13px;margin:auto;">No messages yet — start the conversation below.</div>
                    @endforelse
                </div>

                <div class="pm-ai">
                    <svg class="spark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.9 4.1L18 8l-4.1 1.9L12 14l-1.9-4.1L6 8l4.1-1.9L12 2z"/></svg>
                    <div><b>AI Suggestion</b><p>Draft a thank you message and next steps summary.</p></div>
                    <button type="button" id="pm-ai-use">Use</button>
                </div>

                <div class="pm-compose">
                    <div class="pm-c-tabs">
                        <span class="pm-c-tab on">Message</span><span class="pm-c-tab">AI Reply</span><span class="pm-c-tab">Templates</span><span class="pm-c-tab">Quick Actions</span><span class="pm-c-tab">Notes</span>
                    </div>
                    <form class="pm-c-box" id="pm-form">
                        <textarea id="pm-input" placeholder="Type your message..."></textarea>
                        <div class="pm-c-row">
                            <div class="pm-c-icons">
                                <button type="button" title="Emoji"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></button>
                                <button type="button" title="Attach"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></button>
                                <button type="button" title="Document"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></button>
                                <button type="button" title="Image"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></button>
                                <button type="button" title="Schedule"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></button>
                            </div>
                            <button type="submit" class="pm-send" id="pm-send"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Send</button>
                        </div>
                        <div class="pm-c-foot">
                            <label><input type="checkbox"> Internal only (not sent to client)</label>
                            <a href="{{ route('professional.notifications.index') }}" style="color:var(--pm);font-weight:700;text-decoration:none;">Manage Email Notifications</a>
                        </div>
                    </form>
                </div>
            @else
                <div class="pm-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Select a conversation to view the thread.</div>
            @endif
        </div>
    </div>
</div>

{{-- New Message modal --}}
<div id="pm-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:16px;padding:24px;width:380px;max-width:90vw;">
        <h3 style="font-size:17px;font-weight:800;color:var(--text-primary);margin:0 0 4px;">New Message</h3>
        <p style="font-size:12.5px;color:var(--text-muted);margin:0 0 16px;">Start a conversation with a client or contact.</p>
        <label style="font-size:12px;font-weight:700;color:var(--text-primary);display:block;margin-bottom:6px;">Recipient</label>
        <select id="pm-modal-recipient" style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-card);color:var(--text-primary);font-size:13px;margin-bottom:16px;font-family:inherit;">
            @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
        </select>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" id="pm-modal-cancel" style="padding:10px 16px;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-card);color:var(--text-secondary);font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">Cancel</button>
            <button type="button" id="pm-modal-start" style="padding:10px 18px;border:none;border-radius:9px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;font-size:13px;font-weight:800;cursor:pointer;font-family:inherit;">Start Conversation</button>
        </div>
    </div>
</div>

<script>
(function () {
    const root = document.querySelector('.pm');
    if (!root) return;
    const $ = (id) => document.getElementById(id);
    const csrf = root.dataset.csrf;
    const search = $('pm-search');
    let activeTab = 'inbox';
    function applyFilters() {
        const q = (search ? search.value : '').toLowerCase();
        let shown = 0;
        document.querySelectorAll('#pm-list .pm-conv').forEach((el) => {
            let ok = (el.dataset.name || '').includes(q);
            if (activeTab === 'sent') ok = ok && el.dataset.lastfrom === 'me';
            else if (activeTab === 'drafts' || activeTab === 'archived') ok = false;
            el.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        const empty = $('pm-list-empty');
        if (empty) {
            if (shown === 0) { empty.style.display = ''; empty.textContent = activeTab === 'drafts' ? 'No drafts.' : (activeTab === 'archived' ? 'No archived conversations.' : 'No matching conversations.'); }
            else empty.style.display = 'none';
        }
    }
    if (search) search.addEventListener('input', applyFilters);
    document.querySelectorAll('.pm-tab').forEach((t) => t.addEventListener('click', function () {
        document.querySelectorAll('.pm-tab').forEach((x) => x.classList.remove('on')); this.classList.add('on');
        activeTab = this.dataset.tab || 'inbox'; applyFilters();
    }));

    // Compose sub-tabs (Message / AI Reply / Templates …) — local toggle only.
    document.querySelectorAll('.pm-c-tab').forEach((t) => t.addEventListener('click', function () {
        document.querySelectorAll('.pm-c-tab').forEach((x) => x.classList.remove('on'));
        this.classList.add('on');
    }));

    // AI Suggestion "Use" → fill the compose box.
    const aiUse = $('pm-ai-use');
    if (aiUse) aiUse.addEventListener('click', () => {
        const i = $('pm-input');
        if (i) { i.value = "Thank you so much for the great work and smooth coordination! Next steps: I'll confirm the final timeline, share the remaining deliverables, and keep payments on track. Looking forward to wrapping this up perfectly."; i.focus(); }
    });

    // Create Message → start a real conversation.
    const modal = $('pm-modal');
    if ($('pm-create')) $('pm-create').addEventListener('click', () => { if (modal) modal.style.display = 'flex'; });
    if ($('pm-modal-cancel')) $('pm-modal-cancel').addEventListener('click', () => { if (modal) modal.style.display = 'none'; });
    if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
    if ($('pm-modal-start')) $('pm-modal-start').addEventListener('click', async function () {
        const rid = $('pm-modal-recipient') ? $('pm-modal-recipient').value : null;
        if (!rid) return;
        this.disabled = true; this.style.opacity = '0.7';
        try {
            const res = await fetch(@json(route('conversations.store')), { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify({ type: 'direct', participant_ids: [parseInt(rid, 10)] }) });
            if (res.ok) { const c = await res.json(); window.location.href = @json(url('/professional/messages')) + '/' + c.id; return; }
        } catch (e) {}
        this.disabled = false; this.style.opacity = '';
    });

    // AI Compose → drop a polished draft into the active thread.
    if ($('pm-ai-compose')) $('pm-ai-compose').addEventListener('click', () => {
        const i = $('pm-input');
        if (i) { i.value = "Hi! Thanks for reaching out. I'd love to help with your event — could you share the date, venue, and guest count so I can confirm availability and put together the right package for you?"; i.focus(); }
        else if (modal) modal.style.display = 'flex';
    });
})();
</script>

@if($thread)
<script>
window.CHAT_LIVE = {
    box: '#pm-msgs', form: '#pm-form', input: '#pm-input',
    sendUrl: @json($thread['sendUrl']), showUrl: @json($thread['showUrl']), readUrl: @json($thread['readUrl']),
    meId: @json($thread['meId']), seen: @json(array_column($thread['messages'], 'id')),
    bubble: function (m, mine) {
        const esc = (s) => { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
        const name = mine ? 'You' : ((m.sender && m.sender.name) || 'User');
        let t = ''; try { t = new Date(m.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }); } catch (e) {}
        return '<div class="pm-msg ' + (mine ? 'me' : '') + '"><span class="pm-msg-av" style="background:' + (mine ? '#1e293b' : '#2563eb') + ';">' + esc(name.charAt(0).toUpperCase()) + '</span><div class="pm-msg-body"><div class="pm-msg-meta">' + esc(name) + ' · ' + t + '</div><div class="pm-bubble">' + esc(m.body) + '</div></div></div>';
    },
};
</script>
@include('partials._chat_live')
@endif
@endsection
