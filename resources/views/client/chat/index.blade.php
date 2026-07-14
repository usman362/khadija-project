@extends('layouts.client')

@section('title', 'Messages — Inbox')
@section('page-title', 'Messages — Inbox')
@section('page-subtitle', 'All your conversations, event updates, documents, and payments — organized in one place.')

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
    .cm-btn-ghost svg { width: 14px; height: 14px; color: #ea580c; }

    .cm-main { display: grid; grid-template-columns: minmax(0,340px) minmax(0,1fr); gap: 16px; align-items: start; }
    .cm-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; }
    .cm-tabs { display: flex; gap: 4px; padding: 12px 14px 0; border-bottom: 1px solid var(--border-color); }
    .cm-tab { display: inline-flex; align-items: center; gap: 6px; padding: 9px 12px; font-size: 12.5px; font-weight: 700; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .cm-tab.on { color: var(--cm); border-bottom-color: var(--cm); }
    .cm-tab .ct { font-size: 10px; background: var(--cm); color: #fff; border-radius: 999px; padding: 1px 6px; }
    .cm-search { display: flex; gap: 8px; padding: 12px 14px; }
    .cm-search-box { flex: 1; position: relative; }
    .cm-search-box svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); }
    .cm-search-box input { width: 100%; box-sizing: border-box; padding: 9px 12px 9px 34px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; }
    .cm-list { max-height: 600px; overflow-y: auto; }
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

    .cm-thread { display: flex; flex-direction: column; min-height: 600px; }
    .cm-th-head { display: flex; align-items: center; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--border-color); }
    .cm-th-av { width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 15px; font-weight: 800; background: #ea580c; }
    .cm-th-mid { flex: 1; min-width: 0; }
    .cm-th-name { font-size: 16px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
    .cm-open { font-size: 10px; font-weight: 800; color: #059669; background: rgba(16,185,129,0.12); border-radius: 5px; padding: 2px 7px; }
    .cm-th-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .cm-msgs { flex: 1; padding: 18px; display: flex; flex-direction: column; gap: 16px; overflow-y: auto; max-height: 440px; }
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
    .cm-att-item svg { width: 16px; height: 16px; color: #dc2626; }
    .cm-att-item b { font-size: 12px; color: var(--text-primary); display: block; }
    .cm-att-item span { font-size: 10.5px; color: var(--text-muted); }
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
            <div class="cm-stat"><div class="cm-stat-h"><span class="cm-stat-ico" style="background:rgba(234,88,12,0.12);color:#ea580c;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>Unread</div><div class="v">{{ $stats['unread'] }}</div><div class="s">of {{ max($stats['total'], $stats['unread']) }}</div></div>
            <div class="cm-stat"><div class="cm-stat-h"><span class="cm-stat-ico" style="background:rgba(217,119,6,0.12);color:#d97706;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>Priority</div><div class="v">{{ $stats['priority'] }}</div><div class="s">Needs attention</div></div>
            <div class="cm-stat"><div class="cm-stat-h"><span class="cm-stat-ico" style="background:rgba(16,185,129,0.12);color:#059669;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg></span>Response Time</div><div class="v">{{ $stats['response'] }}</div><div class="s" style="color:#059669;">↓ 9% vs last 30 days</div></div>
            <div class="cm-stat"><div class="cm-stat-h"><span class="cm-stat-ico" style="background:rgba(220,38,38,0.12);color:#dc2626;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>Compliance</div><div class="v">{{ $stats['compliance'] }}</div><div class="s">Action required</div></div>
            <div class="cm-stat"><div class="cm-stat-h"><span class="cm-stat-ico" style="background:rgba(234,88,12,0.12);color:#ea580c;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>Payment Secured</div><div class="v">{{ $money($stats['secure payment']) }}</div><div class="s">Across {{ $stats['secure payment_convos'] }} conversations</div></div>
        </div>
        <div class="cm-actions">
            <button type="button" class="cm-btn-primary" id="cm-create"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Create Message</button>
            <button type="button" class="cm-btn-ghost" id="cm-ai"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.9 4.1L18 8l-4.1 1.9L12 14l-1.9-4.1L6 8l4.1-1.9L12 2z"/></svg>AI Compose</button>
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
            <div class="cm-list" id="cm-list">
                @forelse($conversations as $c)
                    <a href="{{ route('client.chat.show', $c['id']) }}" class="cm-conv {{ ($thread && $thread['id'] === $c['id']) ? 'active' : '' }}" data-name="{{ \Illuminate\Support\Str::lower($c['name'].' '.$c['subject']) }}" data-lastfrom="{{ $c['lastFromMe'] ? 'me' : 'them' }}">
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
                </div>
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
                <div class="cm-compose">
                    <form class="cm-c-box" id="cm-form">
                        <textarea id="cm-input" placeholder="Type your message..."></textarea>
                        <div class="cm-c-row"><button type="submit" class="cm-send"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Send</button></div>
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
    function applyFilters() {
        const q = (s ? s.value : '').toLowerCase();
        let shown = 0;
        document.querySelectorAll('#cm-list .cm-conv').forEach((el) => {
            let ok = (el.dataset.name || '').includes(q);
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
    if (s) s.addEventListener('input', applyFilters);
    document.querySelectorAll('.cm-tab').forEach((t) => t.addEventListener('click', function () {
        document.querySelectorAll('.cm-tab').forEach((x) => x.classList.remove('on')); this.classList.add('on');
        activeTab = this.dataset.tab || 'inbox'; applyFilters();
    }));

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

    // AI Compose → drop a polished draft into the active thread.
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
