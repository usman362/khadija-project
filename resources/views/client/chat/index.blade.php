@extends('layouts.client')

@section('title', 'Messages — Inbox')
@section('page-title', 'Messages — Inbox')
@section('page-subtitle', 'Every conversation in one place.')

{{-- Client Messages — Inbox. Server-rendered, orange client theme. Live send +
     polling + read receipts via partials._chat_live. --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
    $tagColors = ['green' => ['#059669','rgba(16,185,129,0.12)'], 'amber' => ['#d97706','rgba(217,119,6,0.12)'], 'red' => ['#dc2626','rgba(220,38,38,0.12)'], 'blue' => ['#2563eb','rgba(37,99,235,0.12)']];
@endphp

@push('styles')
<style>
    .cm { --cm: #ea580c; padding-top: 6px; }
    .cm-top { display: grid; grid-template-columns: minmax(0,1fr) 210px; gap: 16px; align-items: start; margin-bottom: 18px; }
    .cm-stats { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 12px; }
    .cm-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px; }
    .cm-stat-h { display: flex; align-items: center; gap: 8px; font-size: 11.5px; font-weight: 700; color: var(--text-muted); }
    .cm-stat-ico { width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .cm-stat-ico svg { width: 14px; height: 14px; }
    .cm-stat .v { font-size: 23px; font-weight: 800; color: var(--text-primary); margin: 7px 0 2px; }
    .cm-stat .s { font-size: 11px; color: var(--text-muted); }
    .cm-actions { display: flex; flex-direction: column; gap: 10px; }
    .cm-btn-primary { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 13px; border: none; border-radius: 11px; background: linear-gradient(135deg, #fb923c, #ea580c); color: #fff; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .cm-btn-primary svg { width: 15px; height: 15px; }
    .cm-btn-ghost { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border: 1px solid var(--border-color); border-radius: 11px; background: var(--bg-card); color: var(--text-secondary); font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; }
    .cm-btn-ghost svg { width: 14px; height: 14px; color: var(--brand-text); }

    .cm-main { display: grid; grid-template-columns: minmax(0,340px) minmax(0,1fr); gap: 16px; }
    /* Both columns are one height. `align-items: start` used to size each
       to its own content, so the list ran on past the bottom of the thread
       and left a block of empty card under the compose box. */
    .cm-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; display: flex; flex-direction: column; height: 600px; }
    .cm-tabs { display: flex; gap: 4px; padding: 12px 14px 0; border-bottom: 1px solid var(--border-color); }
    .cm-tab { display: inline-flex; align-items: center; gap: 6px; padding: 9px 12px; font-size: 12.5px; font-weight: 700; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .cm-tab.on { color: var(--cm); border-bottom-color: var(--cm); }
    .cm-tab .ct { font-size: 10px; background: var(--cm); color: #fff; border-radius: 999px; padding: 1px 6px; }
    .cm-search { display: flex; gap: 8px; padding: 12px 14px; }
    .cm-search-box { flex: 1; position: relative; }
    .cm-search-box svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); }
    .cm-search-box input { width: 100%; box-sizing: border-box; padding: 9px 12px 9px 34px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; }
    .cm-list { flex: 1; min-height: 0; overflow-y: auto; }
    .cm-conv { display: flex; gap: 11px; padding: 13px 14px; border-top: 1px solid var(--border-color); cursor: pointer; text-decoration: none; }
    .cm-conv:hover { background: var(--bg-card-hover); }
    .cm-conv.active { background: rgba(234,88,12,0.06); border-left: 3px solid var(--cm); padding-left: 11px; }
    .cm-conv-av { width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; font-weight: 800; background: #ea580c; }
    .cm-conv-mid { flex: 1; min-width: 0; }
    .cm-conv-name { font-size: 13.5px; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cm-conv-name span { font-weight: 500; color: var(--text-muted); font-size: 12px; }
    .cm-conv-subj { font-size: 12px; font-weight: 700; color: var(--text-secondary); margin: 2px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cm-conv-prev { font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cm-conv-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; }
    .cm-tag { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
    .cm-conv-meta { text-align: right; flex-shrink: 0; }
    .cm-conv-time { font-size: 11px; color: var(--text-muted); }
    .cm-conv-badge { display: inline-block; min-width: 18px; height: 18px; line-height: 18px; text-align: center; background: #ef4444; color: #fff; font-size: 10px; font-weight: 800; border-radius: 999px; margin-top: 8px; padding: 0 5px; }
    .cm-pager { padding: 12px 14px; border-top: 1px solid var(--border-color); font-size: 11.5px; color: var(--text-muted); }

    .cm-thread { display: flex; flex-direction: column; }
    .cm-th-head { display: flex; align-items: center; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--border-color); }
    .cm-th-av { width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 15px; font-weight: 800; background: #ea580c; }
    .cm-th-mid { flex: 1; min-width: 0; }
    .cm-th-name { font-size: 16px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
    .cm-open { font-size: 10px; font-weight: 800; color: var(--ok-text); background: rgba(16,185,129,0.12); border-radius: 5px; padding: 2px 7px; }
    .cm-th-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .cm-msgs { flex: 1; min-height: 0; padding: 18px; display: flex; flex-direction: column; gap: 16px; overflow-y: auto; }
    .cm-msg { display: flex; gap: 11px; max-width: 78%; }
    .cm-msg.me { flex-direction: row-reverse; margin-left: auto; }
    .cm-msg-av { width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 800; }
    .cm-msg-body { min-width: 0; }
    .cm-msg-meta { font-size: 11px; color: var(--text-muted); margin-bottom: 4px; }
    .cm-msg.me .cm-msg-meta { text-align: right; }
    .cm-bubble { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 12px; padding: 10px 13px; font-size: 13px; color: var(--text-primary); line-height: 1.5; word-break: break-word; }
    .cm-msg.me .cm-bubble { background: rgba(234,88,12,0.1); border-color: rgba(234,88,12,0.2); }
    .cm-att { display: flex; gap: 9px; margin-top: 8px; flex-wrap: wrap; }
    .cm-att-item { display: flex; align-items: center; gap: 8px; border: 1px solid var(--border-color); border-radius: 9px; padding: 8px 11px; background: var(--bg-card); }
    .cm-att-item svg { width: 16px; height: 16px; color: var(--bad-text); }
    .cm-att-item b { font-size: 12px; color: var(--text-primary); display: block; }
    .cm-att-item span { font-size: 10.5px; color: var(--text-muted); }
    /* Filter row under the search box. */
    .cm-filters { display: flex; gap: 8px; padding: 0 14px 12px; }
    .cm-filters select { flex: 1; min-width: 0; padding: 7px 9px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-primary); font-size: 12px; font-family: inherit; cursor: pointer; }

    /* Thread header actions. */
    .cm-th-acts { margin-left: auto; display: flex; gap: 6px; }
    .cm-th-acts a, .cm-th-acts button { width: 34px; height: 34px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; }
    .cm-th-acts a:hover, .cm-th-acts button:hover { color: var(--cm); border-color: var(--cm); }
    .cm-th-acts svg { width: 16px; height: 16px; }

    /* Conversation details panel. */
    .cm-info { padding: 16px 18px; border-bottom: 1px solid var(--border-color); background: var(--bg-card-hover); }
    .cm-info-who { display: flex; align-items: center; gap: 11px; margin-bottom: 12px; }
    .cm-info-av { width: 40px; height: 40px; border-radius: 50%; background: var(--cm); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; flex-shrink: 0; }
    .cm-info-who b { display: block; font-size: 14px; color: var(--text-primary); }
    .cm-info-who span { font-size: 12px; color: var(--text-muted); }
    .cm-info-rows > div { display: flex; justify-content: space-between; gap: 12px; padding: 5px 0; font-size: 12.5px; }
    .cm-info-rows span { color: var(--text-muted); }
    .cm-info-rows b { color: var(--text-primary); font-weight: 600; text-align: right; word-break: break-word; }
    .cm-info-order { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); font-size: 12.5px; }
    .cm-info-order .ref { display: inline-block; font-size: 10.5px; font-weight: 800; letter-spacing: .3px; color: var(--cm); background: rgba(234,88,12,0.12); padding: 2px 7px; border-radius: 5px; margin-bottom: 5px; }
    .cm-info-order b { display: block; color: var(--text-primary); font-size: 13.5px; }
    .cm-info-order > div { color: var(--text-muted); margin: 3px 0 7px; }
    .cm-info-order a { color: var(--cm); font-weight: 700; text-decoration: none; }

    /* Suggestion strip. */
    .cm-suggest { display: flex; align-items: center; gap: 12px; margin: 0 18px 2px; padding: 12px 14px; border: 1px solid rgba(234,88,12,0.28); background: rgba(234,88,12,0.07); border-radius: 12px; }
    .cm-suggest .spark { width: 17px; height: 17px; color: var(--cm); flex-shrink: 0; }
    .cm-suggest b { display: block; font-size: 12.5px; color: var(--text-primary); }
    .cm-suggest p { margin: 1px 0 0; font-size: 12px; color: var(--text-secondary); }
    .cm-suggest button { margin-left: auto; flex-shrink: 0; padding: 6px 15px; border: 1px solid var(--cm); border-radius: 8px; background: var(--bg-card); color: var(--cm); font-size: 12px; font-weight: 700; cursor: pointer; font-family: inherit; }

    /* Compose tabs + the panes they switch between. */
    .cm-c-tabs { display: flex; gap: 4px; margin-bottom: 11px; border-bottom: 1px solid var(--border-color); }
    .cm-c-tab { padding: 7px 12px; font-size: 12.5px; font-weight: 700; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .cm-c-tab.on { color: var(--cm); border-bottom-color: var(--cm); }
    .cm-c-pane { display: flex; flex-direction: column; gap: 6px; margin-bottom: 11px; max-height: 168px; overflow-y: auto; }
    .cm-pick { text-align: left; padding: 8px 11px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-primary); font-size: 12.5px; font-family: inherit; cursor: pointer; }
    .cm-pick:hover { border-color: var(--cm); }
    .cm-pick.tpl b { display: block; font-size: 12.5px; margin-bottom: 2px; }
    .cm-pick.tpl span { font-size: 11.5px; color: var(--text-muted); }

    .cm-c-icons { display: flex; gap: 4px; position: relative; }
    .cm-c-icons > button { width: 32px; height: 32px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; }
    .cm-c-icons > button:hover { color: var(--cm); border-color: var(--cm); }
    .cm-c-icons svg { width: 15px; height: 15px; }
    /* Emoji picker. Was ten buttons in a 176px box. */
    /* Anchored to its RIGHT edge. The icons sit at the right of the composer,
       so a 306px panel opening leftwards from them stayed inside the thread;
       opening rightwards (which `left: 0` did) put 132px of it past the edge
       and the last two emoji columns were cut off. The old picker was 176px
       and fitted either way, which is why nobody noticed. */
    .cm-emoji { position: absolute; bottom: 38px; right: 0; width: 306px; max-width: min(306px, calc(100vw - 32px));
                padding: 8px; background: var(--bg-card);
                border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,0.18); z-index: 20; }
    .cm-emoji-q { width: 100%; padding: 7px 10px; font-size: 12.5px; font-family: inherit; color: var(--text-primary);
                  background: var(--bg-input, transparent); border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 7px; }
    .cm-emoji-q:focus { outline: none; border-color: var(--brand-text); }
    .cm-emoji-tabs { display: flex; gap: 2px; margin-bottom: 6px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px; }
    .cm-emoji-tabs button { flex: 1; height: 28px; border: none; background: none; font-size: 15px; cursor: pointer; border-radius: 6px; }
    .cm-emoji-tabs button:hover { background: var(--bg-card-hover); }
    .cm-emoji-tabs button.on { background: rgba(234,88,12,0.12); }
    .cm-emoji-grid { max-height: 216px; overflow-y: auto; }
    .cm-emoji-h { font-size: 10.5px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;
                  letter-spacing: .04em; margin: 6px 0 4px; position: sticky; top: 0; background: var(--bg-card); }
    .cm-emoji-row { display: grid; grid-template-columns: repeat(8, 1fr); gap: 1px; }
    .cm-emoji-row button { width: 100%; aspect-ratio: 1; border: none; background: none; font-size: 18px; cursor: pointer;
                           border-radius: 6px; line-height: 1; padding: 0; }
    .cm-emoji-row button:hover { background: var(--bg-card-hover); transform: scale(1.15); }
    .cm-emoji-none { font-size: 12px; color: var(--text-muted); padding: 14px 4px; margin: 0; text-align: center; }

    .cm-chips { display: flex; flex-wrap: wrap; gap: 6px; margin: 8px 0 0; }
    .chat-chip { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; color: var(--text-primary); background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 999px; padding: 4px 6px 4px 10px; }
    .chat-chip button { border: none; background: none; color: var(--text-muted); font-size: 15px; line-height: 1; cursor: pointer; padding: 0 3px; }

    .cm-c-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 10px; font-size: 12px; }
    .cm-c-foot span { color: var(--bad-text); }
    .cm-c-foot a { color: var(--cm); font-weight: 700; text-decoration: none; }

    .cm-compose { border-top: 1px solid var(--border-color); padding: 14px 18px 16px; }
    .cm-c-box textarea { width: 100%; box-sizing: border-box; min-height: 56px; padding: 11px 13px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; resize: vertical; outline: none; }
    .cm-c-box textarea:focus { border-color: var(--cm); }
    .cm-c-row { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 10px; }
    .cm-send { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 9px; background: linear-gradient(135deg, #fb923c, #ea580c); color: #fff; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .cm-send svg { width: 14px; height: 14px; }
    .cm-empty { flex: 1; display: flex; align-items: center; justify-content: center; color: var(--text-muted); padding: 60px; text-align: center; font-size: 13px; }

    @media (max-width: 1100px) { .cm-top { grid-template-columns: 1fr; } .cm-stats { grid-template-columns: repeat(3, minmax(0,1fr)); } .cm-main { grid-template-columns: 1fr; } }
    @media (max-width: 640px) { .cm-stats { grid-template-columns: repeat(2, minmax(0,1fr)); } }
</style>
@endpush

@section('content')
<div class="cm">
    {{-- stats + actions --}}
    <div class="cm-top">
        <div class="cm-stats">
            <div class="cm-stat"><div class="cm-stat-h"><span class="cm-stat-ico" style="background:rgba(234,88,12,0.12);color:var(--brand-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>Unread</div><div class="v">{{ $stats['unread'] }}</div><div class="s">of {{ max($stats['total'], $stats['unread']) }}</div></div>
            <div class="cm-stat"><div class="cm-stat-h"><span class="cm-stat-ico" style="background:rgba(217,119,6,0.12);color:var(--warn-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>Priority</div><div class="v">{{ $stats['priority'] }}</div><div class="s">Needs attention</div></div>
            <div class="cm-stat"><div class="cm-stat-h"><span class="cm-stat-ico" style="background:rgba(16,185,129,0.12);color:var(--ok-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg></span>Response Time</div><div class="v">{{ $stats['response'] }}</div><div class="s" style="color:var(--ok-text);">↓ 9% vs last 30 days</div></div>
            <div class="cm-stat"><div class="cm-stat-h"><span class="cm-stat-ico" style="background:rgba(220,38,38,0.12);color:var(--bad-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>Compliance</div><div class="v">{{ $stats['compliance'] }}</div><div class="s">Action required</div></div>
            <div class="cm-stat"><div class="cm-stat-h"><span class="cm-stat-ico" style="background:rgba(234,88,12,0.12);color:var(--brand-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>Payment Secured</div><div class="v">{{ $money($stats['escrow']) }}</div><div class="s">Across {{ $stats['escrow_convos'] }} conversations</div></div>
        </div>
        <div class="cm-actions">
            <button type="button" class="cm-btn-primary" id="cm-create"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Create Message</button>
            <button type="button" class="cm-btn-ghost" id="cm-ai"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.9 4.1L18 8l-4.1 1.9L12 14l-1.9-4.1L6 8l4.1-1.9L12 2z"/></svg>Compose</button>
        </div>
    </div>

    <div class="cm-main">
        {{-- list --}}
        <div class="cm-card">
            <div class="cm-tabs">
                <span class="cm-tab on" data-tab="inbox">Inbox <span class="ct">{{ $tabCounts['unread'] }}</span></span>
                <span class="cm-tab" data-tab="sent">Sent @if($tabCounts['sent'])<span class="ct">{{ $tabCounts['sent'] }}</span>@endif</span>
                <span class="cm-tab" data-tab="drafts">Drafts</span>
                <span class="cm-tab" data-tab="archived">Archived</span>
            </div>
            <div class="cm-search"><div class="cm-search-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="text" id="cm-search" placeholder="Search messages..."></div></div>

            {{-- Event filter + sort. Both do the work they claim: the dropdown
                 is built from events that actually have a conversation, and the
                 sort reorders the list in place. --}}
            <div class="cm-filters">
                <select id="cm-event">
                    <option value="">All events</option>
                    @foreach($eventFilters as $ev)
                        <option value="{{ $ev['id'] }}">{{ \Illuminate\Support\Str::limit($ev['title'], 30) }}</option>
                    @endforeach
                </select>
                <select id="cm-sort">
                    <option value="latest">Sort: Latest</option>
                    <option value="oldest">Sort: Oldest</option>
                    <option value="unread">Sort: Unread first</option>
                    <option value="name">Sort: Name</option>
                </select>
            </div>
            <div class="cm-list" id="cm-list">
                @forelse($conversations as $c)
                    <a href="{{ route('client.chat.show', $c['id']) }}" class="cm-conv {{ ($thread && $thread['id'] === $c['id']) ? 'active' : '' }}" data-name="{{ \Illuminate\Support\Str::lower($c['name'].' '.$c['subject']) }}" data-lastfrom="{{ $c['lastFromMe'] ? 'me' : 'them' }}" data-event="{{ $c['event']['id'] ?? '' }}" data-unread="{{ $c['unread'] }}" data-at="{{ $c['sortAt'] }}" data-sortname="{{ \Illuminate\Support\Str::lower($c['name']) }}">
                        <span class="cm-conv-av">{{ $c['initials'] }}</span>
                        <div class="cm-conv-mid">
                            <div class="cm-conv-name">{{ $c['name'] }} <span>({{ $c['role'] }})</span></div>
                            <div class="cm-conv-subj">{{ $c['subject'] }}</div>
                            <div class="cm-conv-prev">{{ $c['preview'] }}</div>
                            <div class="cm-conv-tags">
                                @foreach($c['tags'] as [$tname, $tcol])
                                    <span class="cm-tag" style="color:{{ ($tagColors[$tcol] ?? $tagColors['blue'])[0] }};background:{{ ($tagColors[$tcol] ?? $tagColors['blue'])[1] }};">{{ $tname }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="cm-conv-meta"><div class="cm-conv-time">{{ $c['time'] }}</div>@if($c['unread'] > 0)<span class="cm-conv-badge">{{ $c['unread'] }}</span>@endif</div>
                    </a>
                @empty
                    <div style="padding:40px 16px;text-align:center;color:var(--text-muted);font-size:13px;">No conversations yet.</div>
                @endforelse
                <div id="cm-list-empty" style="display:none;padding:40px 16px;text-align:center;color:var(--text-muted);font-size:13px;"></div>
            </div>
            <div class="cm-pager">Showing {{ count($conversations) }} of {{ $stats['total'] }} conversations</div>
        </div>

        {{-- thread --}}
        <div class="cm-card cm-thread">
            @if($thread)
                <div class="cm-th-head">
                    <span class="cm-th-av">{{ $thread['initials'] }}</span>
                    <div class="cm-th-mid"><div class="cm-th-name">{{ $thread['name'] }} <span class="cm-open">OPEN</span></div><div class="cm-th-sub">{{ $thread['subject'] }}@if($thread['date']) · {{ $thread['date'] }}@endif</div></div>
                    {{-- Every action here goes somewhere real. There is no call
                         or video button because there is no calling feature. --}}
                    <div class="cm-th-acts">
                        @if($info && $info['profileUrl'])
                            <a href="{{ $info['profileUrl'] }}" title="View profile"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></a>
                        @endif
                        @if($info && $info['booking'])
                            <a href="{{ $info['booking']['url'] }}" title="View booking"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></a>
                        @endif
                        <button type="button" id="cm-info-toggle" title="Conversation details"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg></button>
                    </div>
                </div>

                @if($info)
                    <div class="cm-info" id="cm-info" style="display:none;">
                        <div class="cm-info-who">
                            <span class="cm-info-av">{{ $info['initials'] }}</span>
                            <div>
                                <b>{{ $info['name'] }}</b>
                                @if($info['location'])<span>{{ $info['location'] }}</span>@endif
                            </div>
                        </div>
                        <div class="cm-info-rows">
                            @if($info['email'])<div><span>Email</span><b>{{ $info['email'] }}</b></div>@endif
                            @if($info['phone'])<div><span>Phone</span><b>{{ $info['phone'] }}</b></div>@endif
                            @if($info['member_since'])<div><span>On GigResource since</span><b>{{ $info['member_since'] }}</b></div>@endif
                            <div><span>Your bookings with them</span><b>{{ $info['bookings'] }}</b></div>
                            @if($info['spent'] > 0)<div><span>Total agreed</span><b>${{ number_format($info['spent'], 2) }}</b></div>@endif
                        </div>
                        @if($info['booking'])
                            <div class="cm-info-order">
                                <span class="ref">{{ $info['booking']['ref'] }}</span>
                                <b>{{ $info['booking']['title'] }}</b>
                                <div><span>${{ number_format($info['booking']['price'], 2) }}</span> · <span>{{ \Illuminate\Support\Str::headline($info['booking']['status']) }}</span> · <span>{{ $info['booking']['date'] }}</span></div>
                                <a href="{{ $info['booking']['url'] }}">Open in Bookings →</a>
                            </div>
                        @endif
                    </div>
                @endif
                <div class="cm-msgs" id="cm-msgs">
                    @forelse($thread['messages'] as $m)
                        <div class="cm-msg {{ $m['mine'] ? 'me' : '' }}">
                            <span class="cm-msg-av" style="background:{{ $m['mine'] ? '#1e293b' : '#ea580c' }};">{{ strtoupper(substr($m['sender'], 0, 1)) }}</span>
                            <div class="cm-msg-body">
                                <div class="cm-msg-meta">{{ $m['mine'] ? 'You' : $m['sender'] }} · {{ $m['time'] }}</div>
                                <div class="cm-bubble">{{ $m['body'] }}</div>
                                @if(!empty($m['attachments']))<div class="cm-att">@foreach($m['attachments'] as $a)<div class="cm-att-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><div><b>{{ $a['name'] }}</b><span>{{ $a['size'] }}</span></div></div>@endforeach</div>@endif
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center;color:var(--text-muted);font-size:13px;margin:auto;">No messages yet — start the conversation below.</div>
                    @endforelse
                </div>
                <div class="cm-suggest">
                    <svg class="spark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.9 4.1L18 8l-4.1 1.9L12 14l-1.9-4.1L6 8l4.1-1.9L12 2z"/></svg>
                    <div><b>Suggestion</b><p>Ask for a written quote and confirm what's included.</p></div>
                    <button type="button" id="cm-suggest-use">Use</button>
                </div>

                <div class="cm-compose">
                    {{-- Tabs switch what the picker below offers. They are not a
                         highlight that moves — Templates and Quick Replies each
                         insert real text into the box. --}}
                    <div class="cm-c-tabs">
                        <span class="cm-c-tab on" data-pane="write">Message</span>
                        <span class="cm-c-tab" data-pane="quick">Quick Replies</span>
                        <span class="cm-c-tab" data-pane="templates">Templates</span>
                    </div>

                    @php
                        // Written for the client's half of the conversation: the
                        // things a client actually needs to say to a professional.
                        $quickReplies = [
                            'Thanks — that works for me.',
                            'Could you send a written quote for this?',
                            'What time would you arrive to set up?',
                            "I'd like to move ahead. What do you need from me?",
                            'Can we go over this on a call this week?',
                        ];
                        $templates = [
                            'Ask what is included' => "Hi {name}, before we confirm — could you list exactly what's included at this price, and anything that would be charged on top?",
                            'Confirm date and times' => "Hi {name}, just confirming the date and the timings. What time do you plan to arrive, and when would you finish?",
                            'Request a written quote' => "Hi {name}, could you send a written quote covering the services we discussed? I'd like it in writing before we go ahead.",
                            'Ask about changes' => "Hi {name}, we may need to change some details. How late can we make changes, and does anything cost extra?",
                        ];
                    @endphp

                    <div class="cm-c-pane" data-pane="quick" style="display:none;">
                        @foreach($quickReplies as $r)
                            <button type="button" class="cm-pick" data-text="{{ $r }}">{{ $r }}</button>
                        @endforeach
                    </div>
                    <div class="cm-c-pane" data-pane="templates" style="display:none;">
                        @foreach($templates as $label => $body)
                            <button type="button" class="cm-pick tpl" data-text="{{ str_replace('{name}', $thread['name'], $body) }}"><b>{{ $label }}</b><span>{{ \Illuminate\Support\Str::limit(str_replace('{name}', $thread['name'], $body), 64) }}</span></button>
                        @endforeach
                    </div>

                    <form class="cm-c-box" id="cm-form">
                        <textarea id="cm-input" placeholder="Type your message..."></textarea>
                        <div class="cm-chips" id="cm-chips" style="display:none;"></div>
                        <div class="cm-c-row">
                            <div class="cm-c-icons">
                                <button type="button" id="cm-emoji-btn" title="Emoji"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></button>
                                <button type="button" id="cm-attach-btn" title="Attach a file"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></button>
                                <input type="file" id="cm-file" multiple hidden>
                                {{-- Ten emoji, all of them event-planning
                                     shorthand, was the whole picker. People
                                     write to each other here; the set is a
                                     real one now, grouped and searchable. --}}
                                <div class="cm-emoji" id="cm-emoji" style="display:none;">
                                    <input type="text" id="cm-emoji-q" class="cm-emoji-q" placeholder="Search emoji…" aria-label="Search emoji">
                                    <div class="cm-emoji-tabs" id="cm-emoji-tabs">
                                        @foreach(\App\Support\EmojiCatalog::groups() as $key => $group)
                                            <button type="button" data-g="{{ $key }}" class="{{ $loop->first ? 'on' : '' }}" title="{{ $group['label'] }}">{{ $group['icon'] }}</button>
                                        @endforeach
                                    </div>
                                    <div class="cm-emoji-grid" id="cm-emoji-grid">
                                        @foreach(\App\Support\EmojiCatalog::groups() as $key => $group)
                                            <div class="cm-emoji-sec" data-g="{{ $key }}">
                                                <p class="cm-emoji-h">{{ $group['label'] }}</p>
                                                <div class="cm-emoji-row">
                                                    @foreach($group['emoji'] as $char => $name)
                                                        <button type="button" data-e="{{ $char }}" data-n="{{ $name }}" title="{{ $name }}">{{ $char }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                        <p class="cm-emoji-none" id="cm-emoji-none" hidden>No emoji matches that.</p>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="cm-send"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Send</button>
                        </div>
                        <div class="cm-c-foot">
                            <span id="cm-note"></span>
                            <a href="{{ route('client.notifications.index') }}">Manage Email Notifications</a>
                        </div>
                    </form>
                </div>
            @else
                <div class="cm-empty">Select a conversation to view the thread.</div>
            @endif
        </div>
    </div>
</div>

{{-- New Message modal --}}
<div id="cm-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:16px;padding:24px;width:380px;max-width:90vw;">
        <h3 style="font-size:17px;font-weight:800;color:var(--text-primary);margin:0 0 4px;">New Message</h3>
        <p style="font-size:12.5px;color:var(--text-muted);margin:0 0 16px;">Start a conversation with a professional or contact.</p>
        <label style="font-size:12px;font-weight:700;color:var(--text-primary);display:block;margin-bottom:6px;">Recipient</label>
        <select id="cm-modal-recipient" style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-card);color:var(--text-primary);font-size:13px;margin-bottom:16px;font-family:inherit;">
            @foreach($recipients as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
        </select>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" id="cm-modal-cancel" style="padding:10px 16px;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-card);color:var(--text-secondary);font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">Cancel</button>
            <button type="button" id="cm-modal-start" style="padding:10px 18px;border:none;border-radius:9px;background:linear-gradient(135deg,#fb923c,#ea580c);color:#fff;font-size:13px;font-weight:800;cursor:pointer;font-family:inherit;">Start Conversation</button>
        </div>
    </div>
</div>

<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const s = $('cm-search');
    let activeTab = 'inbox';
    const evSel = $('cm-event');
    const sortSel = $('cm-sort');

    function applyFilters() {
        const q = (s ? s.value : '').toLowerCase();
        const ev = evSel ? evSel.value : '';
        let shown = 0;
        document.querySelectorAll('#cm-list .cm-conv').forEach((el) => {
            let ok = (el.dataset.name || '').includes(q);
            if (ev) ok = ok && el.dataset.event === ev;
            if (activeTab === 'sent') ok = ok && el.dataset.lastfrom === 'me';
            else if (activeTab === 'drafts' || activeTab === 'archived') ok = false;
            el.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        const empty = $('cm-list-empty');
        if (empty) {
            if (shown === 0) { empty.style.display = ''; empty.textContent = activeTab === 'drafts' ? 'No drafts.' : (activeTab === 'archived' ? 'No archived conversations.' : 'No matching conversations.'); }
            else empty.style.display = 'none';
        }
    }

    // Reordering the rows in place, rather than reloading, so the sort keeps
    // whatever the search and event filter have already narrowed to.
    function applySort() {
        const list = $('cm-list');
        if (!list || !sortSel) return;
        const rows = Array.from(list.querySelectorAll('.cm-conv'));
        const by = sortSel.value;
        rows.sort((a, b) => {
            if (by === 'oldest') return (+a.dataset.at) - (+b.dataset.at);
            if (by === 'name') return (a.dataset.sortname || '').localeCompare(b.dataset.sortname || '');
            if (by === 'unread') {
                const d = (+b.dataset.unread) - (+a.dataset.unread);
                return d !== 0 ? d : (+b.dataset.at) - (+a.dataset.at);
            }
            return (+b.dataset.at) - (+a.dataset.at);
        });
        const anchor = $('cm-list-empty');
        rows.forEach((r) => list.insertBefore(r, anchor));
    }

    if (s) s.addEventListener('input', applyFilters);
    if (evSel) evSel.addEventListener('change', applyFilters);
    if (sortSel) sortSel.addEventListener('change', applySort);
    document.querySelectorAll('.cm-tab').forEach((t) => t.addEventListener('click', function () {
        document.querySelectorAll('.cm-tab').forEach((x) => x.classList.remove('on')); this.classList.add('on');
        activeTab = this.dataset.tab || 'inbox'; applyFilters();
    }));

    // ── Thread controls ────────────────────────────────────────────────────
    const infoPanel = $('cm-info');
    if ($('cm-info-toggle') && infoPanel) $('cm-info-toggle').addEventListener('click', () => {
        infoPanel.style.display = infoPanel.style.display === 'none' ? '' : 'none';
    });

    function insert(text) {
        const i = $('cm-input');
        if (!i) return;
        i.value = i.value ? (i.value.replace(/\s*$/, '') + ' ' + text) : text;
        i.focus();
        i.selectionStart = i.selectionEnd = i.value.length;
    }

    // Tabs swap the pane; each pick writes into the box. Nothing here is a
    // highlight that only moves.
    document.querySelectorAll('.cm-c-tab').forEach((t) => t.addEventListener('click', function () {
        document.querySelectorAll('.cm-c-tab').forEach((x) => x.classList.remove('on'));
        this.classList.add('on');
        const want = this.dataset.pane;
        document.querySelectorAll('.cm-c-pane').forEach((p) => {
            p.style.display = (p.dataset.pane === want) ? '' : 'none';
        });
    }));
    document.querySelectorAll('.cm-pick').forEach((b) => b.addEventListener('click', function () {
        insert(this.dataset.text || '');
    }));
    if ($('cm-suggest-use')) $('cm-suggest-use').addEventListener('click', () => {
        insert("Could you send me a written quote for this, and confirm exactly what's included?");
    });

    const emoji = $('cm-emoji');

    // Search + category tabs. Filtering is on the emoji's NAME, so "party"
    // finds 🎉 — searching the character itself would be useless.
    if (emoji) {
        const q     = $('cm-emoji-q');
        const grid  = $('cm-emoji-grid');
        const none  = $('cm-emoji-none');
        const tabs  = $('cm-emoji-tabs');
        const secs  = () => Array.from(grid.querySelectorAll('.cm-emoji-sec'));

        function filter(term) {
            term = (term || '').trim().toLowerCase();
            let shown = 0;
            secs().forEach((sec) => {
                let any = 0;
                sec.querySelectorAll('button[data-n]').forEach((b) => {
                    const hit = !term || b.dataset.n.toLowerCase().includes(term);
                    b.hidden = !hit;
                    if (hit) any++;
                });
                sec.hidden = !any;
                shown += any;
            });
            if (none) none.hidden = shown > 0;
        }

        if (q) q.addEventListener('input', function () { filter(this.value); });

        if (tabs) tabs.addEventListener('click', (e) => {
            const b = e.target.closest('button[data-g]');
            if (!b) return;
            tabs.querySelectorAll('button').forEach((x) => x.classList.toggle('on', x === b));
            if (q) { q.value = ''; filter(''); }
            const sec = grid.querySelector('.cm-emoji-sec[data-g="' + b.dataset.g + '"]');
            if (sec) grid.scrollTop = sec.offsetTop - grid.offsetTop;
        });
    }

    if ($('cm-emoji-btn') && emoji) {
        $('cm-emoji-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            // 'block', not 'flex'. The old picker was a single flex-wrap row
            // of ten buttons; this one stacks a search box, a tab strip and a
            // scrolling grid, and flex would lay those three side by side.
            emoji.style.display = emoji.style.display === 'none' ? 'block' : 'none';
        });
        emoji.addEventListener('click', (e) => {
            const b = e.target.closest('button[data-e]');
            if (b) { insert(b.dataset.e); emoji.style.display = 'none'; }
        });
        // Typing in the search box is a click inside the picker — it used to
        // close it, because the only child was a button that closed it anyway.
        emoji.addEventListener('click', (e) => e.stopPropagation());
        document.addEventListener('click', () => { emoji.style.display = 'none'; });
    }

    if ($('cm-attach-btn') && $('cm-file')) {
        $('cm-attach-btn').addEventListener('click', () => $('cm-file').click());
    }

    // Create Message → start a real conversation.
    const modal = $('cm-modal');
    if ($('cm-create')) $('cm-create').addEventListener('click', () => { if (modal) modal.style.display = 'flex'; });
    if ($('cm-modal-cancel')) $('cm-modal-cancel').addEventListener('click', () => { if (modal) modal.style.display = 'none'; });
    if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
    if ($('cm-modal-start')) $('cm-modal-start').addEventListener('click', async function () {
        const rid = $('cm-modal-recipient') ? $('cm-modal-recipient').value : null;
        if (!rid) return;
        this.disabled = true; this.style.opacity = '0.7';
        try {
            const res = await fetch(@json(route('conversations.store')), { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify({ type: 'direct', participant_ids: [parseInt(rid, 10)] }) });
            if (res.ok) { const c = await res.json(); window.location.href = @json(url('/client/messages')) + '/' + c.id; return; }
        } catch (e) {}
        this.disabled = false; this.style.opacity = '';
    });

    // Compose → drop a polished draft into the active thread.
    if ($('cm-ai')) $('cm-ai').addEventListener('click', () => {
        const i = $('cm-input');
        if (i) { i.value = "Hi! Following up on our event — could you please confirm the final details and timeline? Happy to share anything you need from my side. Thank you!"; i.focus(); }
        else if (modal) modal.style.display = 'flex';
    });
})();
</script>

@if($thread)
<script>
window.CHAT_LIVE = {
    box: '#cm-msgs', form: '#cm-form', input: '#cm-input',
    sendUrl: @json($thread['sendUrl']), showUrl: @json($thread['showUrl']), readUrl: @json($thread['readUrl']),
    meId: @json($thread['meId']), seen: @json(array_column($thread['messages'], 'id')),
    fileInput: '#cm-file', chips: '#cm-chips',
    uploadUrl: @json(route('attachments.store')), conversationId: @json($thread['id']),
    onError: function (msg) { const n = document.getElementById('cm-note'); if (n) { n.textContent = msg; setTimeout(() => { n.textContent = ''; }, 6000); } },
    bubble: function (m, mine) {
        const esc = (x) => { const d = document.createElement('div'); d.textContent = x == null ? '' : x; return d.innerHTML; };
        const name = mine ? 'You' : ((m.sender && m.sender.name) || 'User');
        let t = ''; try { t = new Date(m.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }); } catch (e) {}
        return '<div class="cm-msg ' + (mine ? 'me' : '') + '"><span class="cm-msg-av" style="background:' + (mine ? '#1e293b' : '#ea580c') + ';">' + esc(name.charAt(0).toUpperCase()) + '</span><div class="cm-msg-body"><div class="cm-msg-meta">' + esc(name) + ' · ' + t + '</div><div class="cm-bubble">' + esc(m.body) + '</div></div></div>';
    },
};
</script>
@include('partials._chat_live')
@endif
@endsection
