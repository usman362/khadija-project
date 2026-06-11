@extends('layouts.professional')

@section('title', 'Threads')

{{-- Professional Threads — per-conversation deep view: thread + linked booking
     + shared files + conversation info + AI-extracted commitments (mined from
     the chat text). Blue professional theme. Send → conversations.messages.store. --}}

@push('styles')
<style>
    .th { --th: #2563eb; }
    .th-head h1 { font-size: 24px; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 9px; }
    .th-head h1 svg { width: 18px; height: 18px; color: #2563eb; }
    .th-head p { font-size: 13px; color: var(--text-muted); margin: 4px 0 18px; }

    .th-grid { display: grid; grid-template-columns: minmax(0,290px) minmax(0,1fr) minmax(0,300px); gap: 16px; align-items: start; }
    .th-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; }

    /* left: conversations */
    .th-conv-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 16px 12px; }
    .th-conv-head b { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .th-conv-head .ic { display: flex; gap: 8px; }
    .th-conv-head .ic svg { width: 16px; height: 16px; color: var(--text-muted); cursor: pointer; }
    .th-search { padding: 0 16px 12px; position: relative; }
    .th-search svg { position: absolute; left: 27px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); }
    .th-search input { width: 100%; box-sizing: border-box; padding: 9px 12px 9px 34px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; }
    .th-filters { display: flex; gap: 7px; padding: 0 16px 12px; }
    .th-filter { font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 999px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); cursor: pointer; }
    .th-filter.on { background: var(--th); border-color: var(--th); color: #fff; }
    .th-conv-list { max-height: 560px; overflow-y: auto; }
    .th-conv { display: flex; gap: 11px; padding: 12px 16px; border-top: 1px solid var(--border-color); cursor: pointer; text-decoration: none; }
    .th-conv:hover { background: var(--bg-card-hover); }
    .th-conv.active { background: rgba(37,99,235,0.06); }
    .th-conv-av { width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 13px; font-weight: 800; }
    .th-conv-mid { flex: 1; min-width: 0; }
    .th-conv-name { font-size: 13.5px; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .th-conv-proj { font-size: 11.5px; color: var(--text-muted); margin: 1px 0 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .th-conv-prev { font-size: 12px; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .th-conv-prev.typing { color: var(--th); font-weight: 700; }
    .th-conv-meta { text-align: right; flex-shrink: 0; }
    .th-conv-time { font-size: 11px; color: var(--text-muted); }
    .th-conv-badge { display: inline-block; min-width: 18px; height: 18px; line-height: 18px; text-align: center; background: var(--th); color: #fff; font-size: 10px; font-weight: 800; border-radius: 999px; margin-top: 7px; }
    .th-archived { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 14px; border-top: 1px solid var(--border-color); font-size: 12.5px; font-weight: 700; color: var(--text-secondary); cursor: pointer; }
    .th-archived svg { width: 14px; height: 14px; }

    /* center: thread */
    .th-thread { display: flex; flex-direction: column; }
    .th-th-head { display: flex; align-items: center; gap: 14px; padding: 18px 20px; border-bottom: 1px solid var(--border-color); }
    .th-th-av { width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px; font-weight: 800; }
    .th-th-mid { flex: 1; min-width: 0; }
    .th-th-name { font-size: 16px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
    .th-verified { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 800; color: #059669; background: rgba(16,185,129,0.12); border-radius: 5px; padding: 2px 7px; }
    .th-verified svg { width: 11px; height: 11px; }
    .th-th-sub { font-size: 12px; color: var(--text-muted); margin-top: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .th-th-sub .star { color: #f59e0b; font-weight: 800; }
    .th-th-actions { display: flex; align-items: center; gap: 9px; }
    .th-icon-btn { width: 34px; height: 34px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .th-icon-btn svg { width: 16px; height: 16px; }
    .th-profile-btn { padding: 8px 14px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-secondary); font-size: 12.5px; font-weight: 700; cursor: pointer; }

    .th-booking { display: flex; align-items: center; gap: 22px; flex-wrap: wrap; padding: 13px 18px; border-bottom: 1px solid var(--border-color); background: var(--bg-card-hover); }
    .th-booking .bk { font-size: 12px; }
    .th-booking .bk .k { color: var(--text-muted); font-size: 11px; }
    .th-booking .bk .v { color: var(--text-primary); font-weight: 800; }
    .th-booking .bk .v.green { color: #059669; }
    .th-booking-btn { margin-left: auto; padding: 8px 14px; border: 1px solid var(--th); border-radius: 9px; background: var(--bg-card); color: var(--th); font-size: 12px; font-weight: 800; cursor: pointer; text-decoration: none; }

    .th-msgs { padding: 20px; display: flex; flex-direction: column; gap: 18px; min-height: 240px; max-height: 420px; overflow-y: auto; }
    .th-day { text-align: center; font-size: 11px; color: var(--text-muted); font-weight: 700; }
    .th-msg { max-width: 74%; }
    .th-msg.me { margin-left: auto; }
    .th-msg-row { display: flex; gap: 10px; align-items: flex-end; }
    .th-msg.me .th-msg-row { flex-direction: row-reverse; }
    .th-msg-av { width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 11px; font-weight: 800; }
    .th-bubble { background: var(--bg-card-hover); border-radius: 14px; padding: 10px 13px; font-size: 13px; color: var(--text-primary); line-height: 1.5; word-break: break-word; }
    .th-msg.me .th-bubble { background: rgba(37,99,235,0.1); }
    .th-msg-time { font-size: 10.5px; color: var(--text-muted); margin-top: 3px; }
    .th-msg.me .th-msg-time { text-align: right; }
    .th-typing { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--th); font-weight: 600; }
    .th-typing .dots { display: inline-flex; gap: 3px; }
    .th-typing .dots i { width: 5px; height: 5px; border-radius: 50%; background: var(--th); display: inline-block; }

    .th-compose { border-top: 1px solid var(--border-color); padding: 14px 18px; }
    .th-c-tabs { display: flex; align-items: center; gap: 16px; border-bottom: 1px solid var(--border-color); margin-bottom: 12px; flex-wrap: wrap; }
    .th-c-tab { font-size: 12.5px; font-weight: 700; color: var(--text-muted); padding-bottom: 9px; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .th-c-tab.on { color: var(--th); border-bottom-color: var(--th); }
    .th-c-ai { margin-left: auto; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: var(--th); cursor: pointer; }
    .th-c-ai svg { width: 14px; height: 14px; }
    .th-c-box { display: flex; align-items: center; gap: 10px; }
    .th-c-box input { flex: 1; padding: 11px 13px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; outline: none; }
    .th-c-box input:focus { border-color: var(--th); }
    .th-send { display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; border: none; border-radius: 9px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; font-size: 13px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .th-send svg { width: 14px; height: 14px; }
    .th-c-icons { display: flex; gap: 4px; margin-bottom: 9px; }
    .th-c-icons svg { width: 16px; height: 16px; color: var(--text-muted); cursor: pointer; }
    .th-consent { display: flex; align-items: center; gap: 9px; margin-top: 11px; font-size: 12px; color: var(--text-secondary); flex-wrap: wrap; }
    .th-consent input { accent-color: var(--th); }
    .th-consent .meta { color: var(--text-muted); font-size: 11px; }
    .th-consent a { margin-left: auto; color: var(--th); font-weight: 700; text-decoration: none; }

    /* extracted commitments */
    .th-commit { border-top: 1px solid var(--border-color); padding: 16px 18px; }
    .th-commit-h { display: flex; align-items: center; gap: 8px; margin-bottom: 3px; }
    .th-commit-h b { font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .th-commit-h .beta { font-size: 9.5px; font-weight: 800; color: var(--th); background: rgba(37,99,235,0.12); border-radius: 5px; padding: 2px 7px; }
    .th-commit-h .manage { margin-left: auto; font-size: 12px; font-weight: 700; color: var(--th); border: 1px solid var(--border-color); border-radius: 8px; padding: 6px 12px; background: var(--bg-card); cursor: pointer; }
    .th-commit-sub { font-size: 12px; color: var(--text-muted); margin: 0 0 14px; }
    .th-commit-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 12px; }
    .th-commit-card { border: 1px solid var(--border-color); border-radius: 12px; padding: 13px; }
    .th-commit-card b { font-size: 12.5px; color: var(--text-primary); display: block; }
    .th-commit-card p { font-size: 11.5px; color: var(--text-muted); margin: 5px 0 10px; line-height: 1.45; }
    .th-commit-foot { display: flex; align-items: center; justify-content: space-between; }
    .th-commit-foot .when { font-size: 10.5px; color: var(--text-muted); }
    .th-commit-add { font-size: 11.5px; font-weight: 800; color: #059669; background: rgba(16,185,129,0.1); border: none; border-radius: 7px; padding: 5px 12px; cursor: pointer; }
    .th-commit-add.added { color: var(--text-muted); background: var(--bg-card-hover); }
    .th-commit-all { display: block; text-align: center; margin-top: 12px; font-size: 12.5px; font-weight: 800; color: var(--th); text-decoration: none; }

    /* right sidebar */
    .th-side-h { display: flex; align-items: center; justify-content: space-between; padding: 15px 16px; border-bottom: 1px solid var(--border-color); }
    .th-side-h b { font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .th-side-h .ic svg { width: 15px; height: 15px; color: var(--text-muted); cursor: pointer; margin-left: 8px; }
    .th-info { padding: 14px 16px; }
    .th-info-row { display: flex; gap: 11px; padding: 9px 0; }
    .th-info-row svg { width: 16px; height: 16px; color: var(--th); flex-shrink: 0; margin-top: 1px; }
    .th-info-row .k { font-size: 11px; color: var(--text-muted); }
    .th-info-row .v { font-size: 13px; font-weight: 700; color: var(--text-primary); }
    .th-info-row .v.green { color: #059669; }
    .th-link { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 800; color: var(--th); text-decoration: none; margin-top: 8px; }
    .th-link svg { width: 13px; height: 13px; }
    .th-file { display: flex; align-items: center; gap: 11px; padding: 9px 0; }
    .th-file-ic { width: 30px; height: 34px; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 9px; font-weight: 800; color: #fff; }
    .th-file-mid { flex: 1; min-width: 0; }
    .th-file-name { font-size: 12.5px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .th-file-meta { font-size: 10.5px; color: var(--text-muted); }
    .th-file-date { font-size: 10.5px; color: var(--text-muted); flex-shrink: 0; }
    .th-qa { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; padding: 14px 16px; }
    .th-qa-btn { display: flex; align-items: center; gap: 7px; padding: 10px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-secondary); font-size: 11.5px; font-weight: 700; cursor: pointer; font-family: inherit; }
    .th-qa-btn svg { width: 14px; height: 14px; color: var(--th); flex-shrink: 0; }

    @media (max-width: 1280px) { .th-grid { grid-template-columns: minmax(0,260px) minmax(0,1fr); } .th-side { display: none; } }
    @media (max-width: 900px) { .th-grid { grid-template-columns: 1fr; } .th-commit-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="th" data-csrf="{{ csrf_token() }}">
    <div class="th-head">
        <h1>Threads <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></h1>
        <p>Manage all your event conversations in one place.</p>
    </div>

    <div class="th-grid">
        {{-- LEFT: conversations --}}
        <div class="th-card">
            <div class="th-conv-head"><b>Conversations</b><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6"/><path d="M10 14L21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span></div>
            <div class="th-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="text" id="th-search" placeholder="Search conversations..."></div>
            <div class="th-filters">
                <span class="th-filter on" data-f="all">All</span>
                <span class="th-filter" data-f="direct">Direct</span>
                <span class="th-filter" data-f="booking">Booking</span>
                <span class="th-filter" data-f="event">Event</span>
            </div>
            <div class="th-conv-list" id="th-conv-list">
                @foreach($conversations as $c)
                    <a href="{{ route('professional.threads.show', $c['id']) }}" class="th-conv {{ ($thread && $thread['id'] === $c['id']) ? 'active' : '' }}" data-type="{{ $c['type'] }}" data-name="{{ \Illuminate\Support\Str::lower($c['name'].' '.$c['project']) }}">
                        <span class="th-conv-av" style="background:#2563eb;">{{ $c['initials'] }}</span>
                        <div class="th-conv-mid">
                            <div class="th-conv-name">{{ $c['name'] }}</div>
                            <div class="th-conv-proj">{{ $c['project'] }}</div>
                            <div class="th-conv-prev {{ $c['unread'] > 0 ? 'typing' : '' }}">{{ $c['preview'] }}</div>
                        </div>
                        <div class="th-conv-meta"><div class="th-conv-time">{{ $c['time'] }}</div>@if($c['unread'] > 0)<span class="th-conv-badge">{{ $c['unread'] }}</span>@endif</div>
                    </a>
                @endforeach
            </div>
            <div class="th-archived"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>View Archived ({{ $archivedCount }})</div>
        </div>

        {{-- CENTER: thread --}}
        <div class="th-card th-thread">
            @if($thread)
                <div class="th-th-head">
                    <span class="th-th-av" style="background:#2563eb;">{{ $thread['initials'] }}</span>
                    <div class="th-th-mid">
                        <div class="th-th-name">{{ $thread['name'] }} @if($thread['verified'])<span class="th-verified"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Verified</span>@endif</div>
                        <div class="th-th-sub"><span>{{ $thread['role'] }}</span> · <span class="star">★ {{ $thread['rating'] }}</span> ({{ $thread['reviews'] }}) · {{ $thread['gigs'] }} Completed Gigs</div>
                    </div>
                    <div class="th-th-actions">
                        <span class="th-icon-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                        <span class="th-icon-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></span>
                        <a href="{{ route('professional.gigs.index') }}" class="th-profile-btn">View Profile</a>
                        <span class="th-icon-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg></span>
                    </div>
                </div>

                @if($thread['booking'])
                    <div class="th-booking">
                        <div class="bk"><span class="th-icon-btn" style="display:inline-flex;width:32px;height:32px;margin-right:6px;vertical-align:middle;"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg></span><span class="v">{{ $thread['booking']['event'] }}</span><div class="k">{{ $thread['booking']['date'] }} · {{ \Illuminate\Support\Str::limit($thread['booking']['location'], 20) }}</div></div>
                        <div class="bk"><div class="k">Booking ID</div><div class="v">{{ $thread['booking']['id'] }}</div></div>
                        <div class="bk"><div class="k">Status</div><div class="v green">{{ $thread['booking']['status'] }}</div></div>
                        <div class="bk"><div class="k">Total</div><div class="v">{{ $thread['booking']['total'] }}</div></div>
                        <a href="{{ route('professional.gigs.index') }}" class="th-booking-btn">View Booking</a>
                    </div>
                @endif

                <div class="th-msgs" id="th-msgs">
                    @php($lastDay = null)
                    @forelse($thread['messages'] as $m)
                        @if($m['day'] !== $lastDay)<div class="th-day">{{ $m['day'] }}</div>@php($lastDay = $m['day'])@endif
                        <div class="th-msg {{ $m['mine'] ? 'me' : '' }}">
                            <div class="th-msg-row">
                                @if(!$m['mine'])<span class="th-msg-av" style="background:#2563eb;">{{ strtoupper(substr($m['sender'],0,1)) }}</span>@endif
                                <div><div class="th-bubble">{{ $m['body'] }}</div></div>
                            </div>
                            <div class="th-msg-time">{{ $m['time'] }}@if($m['mine']) ✓✓@endif</div>
                        </div>
                    @empty
                        <div style="text-align:center;color:var(--text-muted);font-size:13px;margin:auto;">No messages yet.</div>
                    @endforelse
                </div>
                <div class="th-typing" style="padding: 0 18px 8px;"><span class="dots"><i></i><i></i><i></i></span> {{ \Illuminate\Support\Str::of($thread['name'])->explode(' ')->first() }} is typing...</div>

                <div class="th-compose">
                    <div class="th-c-tabs">
                        <span class="th-c-tab on">Message</span><span class="th-c-tab">Files</span><span class="th-c-tab">Templates</span><span class="th-c-tab">Quick Replies</span><span class="th-c-tab">Notes</span>
                        <span class="th-c-ai" id="th-ai"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.9 4.1L18 8l-4.1 1.9L12 14l-1.9-4.1L6 8l4.1-1.9L12 2z"/></svg>AI Assist</span>
                    </div>
                    <div class="th-c-icons"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg></div>
                    <form class="th-c-box" id="th-form">
                        <input type="text" id="th-input" placeholder="Write a message to {{ \Illuminate\Support\Str::of($thread['name'])->explode(' ')->first() }}...">
                        <button type="submit" class="th-send" id="th-send"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Send</button>
                    </form>
                    <div class="th-consent">
                        <input type="checkbox" checked> <span>Include this chat's agreements in the final contract via AI.</span>
                        <span class="meta">(Requires consent from both parties. Currently: You ✓ | Client ✓ | Status: Waiting)</span>
                        <a href="#">Learn more</a>
                    </div>
                </div>

                @if(!empty($thread['commitments']))
                    <div class="th-commit">
                        <div class="th-commit-h"><b>AI Extracted Commitments</b><span class="beta">Beta</span><button type="button" class="manage">Manage in Contract</button></div>
                        <p class="th-commit-sub">Key terms found in this conversation that can be added to your contract.</p>
                        <div class="th-commit-grid">
                            @foreach($thread['commitments'] as $cm)
                                <div class="th-commit-card">
                                    <b>{{ $cm['title'] }}</b>
                                    <p>{{ $cm['detail'] }}</p>
                                    <div class="th-commit-foot"><span class="when">{{ $cm['when'] }}</span><button type="button" class="th-commit-add" onclick="this.classList.add('added');this.textContent='Added ✓'">Add</button></div>
                                </div>
                            @endforeach
                        </div>
                        <a href="#" class="th-commit-all">View All Extracted Terms ({{ count($thread['commitments']) }})</a>
                    </div>
                @endif
            @else
                <div style="padding:80px;text-align:center;color:var(--text-muted);">Select a conversation.</div>
            @endif
        </div>

        {{-- RIGHT: info / files / actions --}}
        <div class="th-side">
            @if($thread)
                <div class="th-card" style="margin-bottom:16px;">
                    <div class="th-side-h"><b>Conversation Info</b><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg></span></div>
                    <div class="th-info">
                        <div class="th-info-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg><div><div class="k">Project</div><div class="v">{{ optional($thread['booking'])['event'] ?? '—' }}</div></div></div>
                        <div class="th-info-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><div><div class="k">Event Date</div><div class="v">{{ optional($thread['booking'])['date'] ?? '—' }}</div></div></div>
                        <div class="th-info-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><div><div class="k">Location</div><div class="v">{{ optional($thread['booking'])['location'] ?? '—' }}</div></div></div>
                        <div class="th-info-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><div><div class="k">Booking Status</div><div class="v green">{{ optional($thread['booking'])['status'] ?? '—' }}</div></div></div>
                        <div class="th-info-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg><div><div class="k">Total Amount</div><div class="v">{{ optional($thread['booking'])['total'] ?? '—' }}</div></div></div>
                        <a href="{{ route('professional.gigs.index') }}" class="th-link">View Booking Details <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                    </div>
                </div>

                <div class="th-card" style="margin-bottom:16px;">
                    <div class="th-side-h"><b>Shared Files</b><a href="#" class="th-link" style="margin:0;">View All</a></div>
                    <div class="th-info">
                        @forelse($thread['files'] as $f)
                            <div class="th-file"><span class="th-file-ic" style="background:{{ $f['ext'] === 'PDF' ? '#dc2626' : '#059669' }};">{{ $f['ext'] }}</span><div class="th-file-mid"><div class="th-file-name">{{ $f['name'] }}</div><div class="th-file-meta">{{ $f['ext'] }} · {{ $f['size'] }}</div></div><span class="th-file-date">{{ $f['date'] }}</span></div>
                        @empty
                            <div style="font-size:12px;color:var(--text-muted);padding:8px 0;">No shared files yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="th-card">
                    <div class="th-side-h"><b>Quick Actions</b></div>
                    <div class="th-qa">
                        <button type="button" class="th-qa-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>Send Payment Link</button>
                        <button type="button" class="th-qa-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Create Invoice</button>
                        <button type="button" class="th-qa-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>Share Contract</button>
                        <button type="button" class="th-qa-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>Schedule Call</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    const root = document.querySelector('.th');
    if (!root) return;
    const csrf = root.dataset.csrf;
    const $ = (id) => document.getElementById(id);
    // search
    const s = $('th-search');
    if (s) s.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#th-conv-list .th-conv').forEach((el) => { el.style.display = (el.dataset.name || '').includes(q) ? '' : 'none'; });
    });
    // filter tabs
    document.querySelectorAll('.th-filter').forEach((f) => f.addEventListener('click', function () {
        document.querySelectorAll('.th-filter').forEach((x) => x.classList.remove('on'));
        this.classList.add('on');
        const t = this.dataset.f;
        document.querySelectorAll('#th-conv-list .th-conv').forEach((el) => { el.style.display = (t === 'all' || el.dataset.type === t) ? '' : 'none'; });
    }));
    // compose tabs
    document.querySelectorAll('.th-c-tab').forEach((t) => t.addEventListener('click', function () {
        document.querySelectorAll('.th-c-tab').forEach((x) => x.classList.remove('on'));
        this.classList.add('on');
    }));
    const ai = $('th-ai');
    if (ai) ai.addEventListener('click', () => { const i = $('th-input'); if (i) { i.value = 'Thanks for confirming! I\'ll lock in the schedule and share the contract with these terms shortly.'; i.focus(); } });
})();
</script>

@if($thread)
<script>
window.CHAT_LIVE = {
    box: '#th-msgs', form: '#th-form', input: '#th-input',
    sendUrl: @json($thread['sendUrl']), showUrl: @json($thread['showUrl']), readUrl: @json($thread['readUrl']),
    meId: @json($thread['meId']), seen: @json(array_column($thread['messages'], 'id')),
    bubble: function (m, mine) {
        const esc = (s) => { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
        const name = mine ? 'You' : ((m.sender && m.sender.name) || 'User');
        let t = ''; try { t = new Date(m.created_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }); } catch (e) {}
        const av = mine ? '' : '<span class="th-msg-av" style="background:#2563eb;">' + esc(name.charAt(0).toUpperCase()) + '</span>';
        return '<div class="th-msg ' + (mine ? 'me' : '') + '"><div class="th-msg-row">' + av + '<div><div class="th-bubble">' + esc(m.body) + '</div></div></div><div class="th-msg-time">' + t + (mine ? ' ✓✓' : '') + '</div></div>';
    },
};
</script>
@include('partials._chat_live')
@endif
@endsection
