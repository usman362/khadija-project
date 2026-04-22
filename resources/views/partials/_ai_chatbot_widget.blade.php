{{-- AI Chatbot Floating Widget — included on authenticated dashboard layouts --}}

@php
    $chatbotEnabled = (bool) \App\Models\Setting::get('chatbot.enabled', true);
    $chatbotKey     = \App\Models\Setting::get('openai.api_key') ?: config('services.openai.key');
@endphp

@if(auth()->check() && $chatbotEnabled && !empty($chatbotKey))
<div id="aiChatBubble" class="aic-bubble" role="button" aria-label="Open AI assistant" tabindex="0">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
    <span class="aic-bubble-dot"></span>
</div>

<div id="aiChatPanel" class="aic-panel" aria-hidden="true">
    <div class="aic-header">
        <div class="aic-header-info">
            <div class="aic-avatar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
            </div>
            <div>
                <div class="aic-title">AI Assistant</div>
                <div class="aic-subtitle" id="aicStatus">Here to help</div>
            </div>
        </div>
        <div class="aic-header-actions">
            <button type="button" class="aic-icon-btn" id="aicNewBtn" title="New conversation">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
            <button type="button" class="aic-icon-btn" id="aicHistoryBtn" title="History">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><path d="M12 7v5l4 2"/></svg>
            </button>
            <button type="button" class="aic-icon-btn" id="aicCloseBtn" title="Close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    </div>

    {{-- Main conversation view --}}
    <div class="aic-body" id="aicBody">
        <div id="aicWelcome" class="aic-welcome">
            <div class="aic-welcome-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="aic-welcome-title">Hi {{ auth()->user()->name }}! 👋</div>
            <div class="aic-welcome-text">How can I help you today?</div>
            <div class="aic-suggestions">
                <button type="button" class="aic-suggestion" data-msg="How do I post an event?">How do I post an event?</button>
                <button type="button" class="aic-suggestion" data-msg="How does the influencer program work?">How does the influencer program work?</button>
                <button type="button" class="aic-suggestion" data-msg="What are the commission tiers?">What are the commission tiers?</button>
                <button type="button" class="aic-suggestion" data-msg="How do I switch between client and professional mode?">Switch between client/professional mode</button>
            </div>
        </div>
        <div id="aicMessages" class="aic-messages"></div>
    </div>

    {{-- History panel (overlay) --}}
    <div id="aicHistory" class="aic-history" aria-hidden="true">
        <div class="aic-history-header">
            <div class="aic-history-title">Conversation History</div>
            <button type="button" class="aic-icon-btn" id="aicHistoryCloseBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="aic-history-list" id="aicHistoryList">
            <div class="aic-history-empty">Loading…</div>
        </div>
    </div>

    <div class="aic-footer">
        <div class="aic-input-row">
            <textarea id="aicInput" class="aic-input" rows="1" placeholder="Type your message…" maxlength="4000"></textarea>
            <button type="button" class="aic-send-btn" id="aicSendBtn" disabled>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
        <div class="aic-limit" id="aicLimit"></div>
    </div>
</div>

<style>
    /* ── Bubble ── */
    .aic-bubble {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 12px 32px rgba(99, 102, 241, 0.4);
        cursor: pointer;
        z-index: 9998;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .aic-bubble:hover { transform: translateY(-3px); box-shadow: 0 16px 40px rgba(99, 102, 241, 0.55); }
    .aic-bubble-dot {
        position: absolute; top: 6px; right: 6px;
        width: 10px; height: 10px; border-radius: 50%;
        background: #10b981; border: 2px solid #fff;
    }
    .aic-bubble.hidden { display: none; }

    /* ── Panel ── */
    .aic-panel {
        position: fixed;
        bottom: 24px; right: 24px;
        width: 380px; height: 600px;
        max-height: calc(100vh - 48px);
        background: #0f1629;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(0,0,0,0.5);
        display: none;
        flex-direction: column;
        overflow: hidden;
        z-index: 9999;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #e2e8f0;
    }
    .aic-panel.open { display: flex; animation: aicSlideUp 0.3s cubic-bezier(0.16,1,0.3,1); }
    @keyframes aicSlideUp {
        from { transform: translateY(40px) scale(0.96); opacity: 0; }
        to   { transform: translateY(0) scale(1); opacity: 1; }
    }
    [data-theme="light"] .aic-panel { background: #ffffff; border-color: rgba(0,0,0,0.08); color: #0f172a; }
    [data-bs-theme="light"] .aic-panel { background: #ffffff; border-color: rgba(0,0,0,0.08); color: #0f172a; }

    /* ── Header ── */
    .aic-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        flex-shrink: 0;
    }
    .aic-header-info { display: flex; align-items: center; gap: 10px; }
    .aic-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.15);
        display: flex; align-items: center; justify-content: center;
    }
    .aic-title { font-size: 14px; font-weight: 700; }
    .aic-subtitle { font-size: 11px; opacity: 0.85; }
    .aic-header-actions { display: flex; gap: 4px; }
    .aic-icon-btn {
        width: 30px; height: 30px;
        background: transparent; border: none;
        color: #fff;
        border-radius: 8px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.15s;
    }
    .aic-icon-btn:hover { background: rgba(255,255,255,0.15); }

    /* ── Body ── */
    .aic-body {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        position: relative;
    }

    /* Welcome state */
    .aic-welcome { text-align: center; padding: 20px 10px; }
    .aic-welcome-icon {
        width: 64px; height: 64px;
        margin: 0 auto 14px;
        border-radius: 50%;
        background: rgba(99,102,241,0.15);
        color: #a5b4fc;
        display: flex; align-items: center; justify-content: center;
    }
    .aic-welcome-title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
    .aic-welcome-text { font-size: 13px; color: #94a3b8; margin-bottom: 20px; }
    .aic-suggestions { display: flex; flex-direction: column; gap: 8px; }
    .aic-suggestion {
        padding: 10px 14px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        color: #cbd5e1;
        font-size: 12.5px;
        text-align: left;
        cursor: pointer;
        transition: all 0.15s;
        font-family: inherit;
    }
    .aic-suggestion:hover {
        background: rgba(99,102,241,0.1);
        border-color: rgba(99,102,241,0.35);
        color: #fff;
    }
    [data-theme="light"] .aic-suggestion,
    [data-bs-theme="light"] .aic-suggestion {
        background: rgba(0,0,0,0.02); border-color: rgba(0,0,0,0.08); color: #475569;
    }
    [data-theme="light"] .aic-welcome-text,
    [data-bs-theme="light"] .aic-welcome-text { color: #64748b; }

    /* Messages */
    .aic-messages { display: flex; flex-direction: column; gap: 12px; }
    .aic-msg {
        max-width: 85%;
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 13.5px;
        line-height: 1.55;
        word-wrap: break-word;
        white-space: pre-wrap;
    }
    .aic-msg-user {
        align-self: flex-end;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        border-bottom-right-radius: 4px;
    }
    .aic-msg-assistant {
        align-self: flex-start;
        background: rgba(255,255,255,0.05);
        color: #e2e8f0;
        border-bottom-left-radius: 4px;
    }
    [data-theme="light"] .aic-msg-assistant,
    [data-bs-theme="light"] .aic-msg-assistant {
        background: #f1f5f9; color: #0f172a;
    }
    .aic-msg-error {
        align-self: center;
        background: rgba(239,68,68,0.1);
        border: 1px solid rgba(239,68,68,0.3);
        color: #f87171;
        font-size: 12px;
    }

    /* Typing indicator */
    .aic-typing {
        align-self: flex-start;
        padding: 14px 16px;
        background: rgba(255,255,255,0.05);
        border-radius: 14px;
        border-bottom-left-radius: 4px;
        display: flex;
        gap: 4px;
    }
    .aic-typing-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        background: #a5b4fc;
        animation: aicBounce 1.4s infinite;
    }
    .aic-typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .aic-typing-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes aicBounce {
        0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
        30% { transform: translateY(-6px); opacity: 1; }
    }

    /* History overlay */
    .aic-history {
        position: absolute;
        inset: 0;
        background: #0f1629;
        display: none;
        flex-direction: column;
        z-index: 10;
    }
    [data-theme="light"] .aic-history,
    [data-bs-theme="light"] .aic-history { background: #ffffff; }
    .aic-history.open { display: flex; }
    .aic-history-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    [data-theme="light"] .aic-history-header,
    [data-bs-theme="light"] .aic-history-header { border-color: rgba(0,0,0,0.08); }
    .aic-history-title { font-size: 14px; font-weight: 700; }
    .aic-history-list {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
    }
    .aic-history-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.15s;
        margin-bottom: 4px;
    }
    .aic-history-item:hover { background: rgba(255,255,255,0.05); }
    [data-theme="light"] .aic-history-item:hover,
    [data-bs-theme="light"] .aic-history-item:hover { background: rgba(0,0,0,0.03); }
    .aic-history-item-title { font-size: 13px; font-weight: 600; }
    .aic-history-item-date { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .aic-history-del {
        width: 26px; height: 26px;
        background: transparent; border: none;
        color: #94a3b8; opacity: 0.6;
        border-radius: 6px;
        cursor: pointer;
    }
    .aic-history-del:hover { background: rgba(239,68,68,0.15); color: #ef4444; opacity: 1; }
    .aic-history-empty {
        text-align: center;
        color: #94a3b8;
        font-size: 13px;
        padding: 40px 20px;
    }

    /* Footer (input) */
    .aic-footer {
        padding: 12px 14px;
        border-top: 1px solid rgba(255,255,255,0.08);
        flex-shrink: 0;
    }
    [data-theme="light"] .aic-footer,
    [data-bs-theme="light"] .aic-footer { border-color: rgba(0,0,0,0.08); }
    .aic-input-row { display: flex; gap: 8px; align-items: flex-end; }
    .aic-input {
        flex: 1;
        padding: 10px 14px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        color: #e2e8f0;
        font-size: 13px;
        font-family: inherit;
        resize: none;
        outline: none;
        max-height: 100px;
        line-height: 1.4;
    }
    [data-theme="light"] .aic-input,
    [data-bs-theme="light"] .aic-input {
        background: #f8fafc; border-color: rgba(0,0,0,0.08); color: #0f172a;
    }
    .aic-input:focus { border-color: #6366f1; }
    .aic-send-btn {
        width: 38px; height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        border: none;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.15s;
        flex-shrink: 0;
    }
    .aic-send-btn:hover:not(:disabled) { transform: scale(1.06); }
    .aic-send-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .aic-limit {
        font-size: 10.5px;
        color: #94a3b8;
        text-align: center;
        margin-top: 8px;
        min-height: 14px;
    }

    /* Mobile */
    @media (max-width: 480px) {
        .aic-panel {
            bottom: 0; right: 0; left: 0;
            width: 100%; height: 100%;
            max-height: 100vh;
            border-radius: 0;
        }
        .aic-bubble { bottom: 20px; right: 20px; }
    }
</style>

<script>
(function () {
    'use strict';

    const bubble     = document.getElementById('aiChatBubble');
    const panel      = document.getElementById('aiChatPanel');
    const closeBtn   = document.getElementById('aicCloseBtn');
    const newBtn     = document.getElementById('aicNewBtn');
    const historyBtn = document.getElementById('aicHistoryBtn');
    const historyCloseBtn = document.getElementById('aicHistoryCloseBtn');
    const welcome    = document.getElementById('aicWelcome');
    const messagesEl = document.getElementById('aicMessages');
    const historyEl  = document.getElementById('aicHistory');
    const historyList= document.getElementById('aicHistoryList');
    const input      = document.getElementById('aicInput');
    const sendBtn    = document.getElementById('aicSendBtn');
    const statusEl   = document.getElementById('aicStatus');
    const limitEl    = document.getElementById('aicLimit');

    let currentConvId = null;
    let isSending     = false;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // ── Open/close ──
    function openPanel() {
        panel.classList.add('open');
        panel.setAttribute('aria-hidden', 'false');
        bubble.classList.add('hidden');
        setTimeout(() => input.focus(), 300);
        refreshLimit();
    }
    function closePanel() {
        panel.classList.remove('open');
        panel.setAttribute('aria-hidden', 'true');
        bubble.classList.remove('hidden');
    }

    bubble.addEventListener('click', openPanel);
    bubble.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPanel(); } });
    closeBtn.addEventListener('click', closePanel);

    // ── New conversation ──
    newBtn.addEventListener('click', () => {
        currentConvId = null;
        messagesEl.innerHTML = '';
        welcome.style.display = 'block';
        input.value = '';
        input.focus();
        statusEl.textContent = 'Here to help';
        closeHistory();
    });

    // ── History toggle ──
    historyBtn.addEventListener('click', async () => {
        historyEl.classList.add('open');
        historyEl.setAttribute('aria-hidden', 'false');
        historyList.innerHTML = '<div class="aic-history-empty">Loading…</div>';
        try {
            const r = await fetch('/ai-chatbot/conversations', { credentials: 'same-origin' });
            const data = await r.json();
            renderHistory(data.conversations || []);
        } catch (e) {
            historyList.innerHTML = '<div class="aic-history-empty">Failed to load history.</div>';
        }
    });

    function closeHistory() {
        historyEl.classList.remove('open');
        historyEl.setAttribute('aria-hidden', 'true');
    }
    historyCloseBtn.addEventListener('click', closeHistory);

    function renderHistory(items) {
        if (!items.length) {
            historyList.innerHTML = '<div class="aic-history-empty">No previous conversations yet.</div>';
            return;
        }
        historyList.innerHTML = items.map(c => `
            <div class="aic-history-item" data-id="${c.id}">
                <div style="flex:1;min-width:0;">
                    <div class="aic-history-item-title" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml(c.title)}</div>
                    <div class="aic-history-item-date">${c.last_message_at ? new Date(c.last_message_at).toLocaleDateString([], {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'}) : ''}</div>
                </div>
                <button type="button" class="aic-history-del" data-del="${c.id}" title="Delete">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                </button>
            </div>
        `).join('');

        historyList.querySelectorAll('.aic-history-item').forEach(el => {
            el.addEventListener('click', e => {
                if (e.target.closest('.aic-history-del')) return;
                loadConversation(el.dataset.id);
            });
        });
        historyList.querySelectorAll('.aic-history-del').forEach(btn => {
            btn.addEventListener('click', async e => {
                e.stopPropagation();
                if (!confirm('Delete this conversation?')) return;
                await fetch(`/ai-chatbot/conversations/${btn.dataset.del}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                btn.closest('.aic-history-item').remove();
                if (String(currentConvId) === btn.dataset.del) {
                    newBtn.click();
                }
            });
        });
    }

    async function loadConversation(id) {
        try {
            const r = await fetch(`/ai-chatbot/conversations/${id}`, { credentials: 'same-origin' });
            const data = await r.json();
            currentConvId = data.id;
            statusEl.textContent = data.title;
            welcome.style.display = 'none';
            messagesEl.innerHTML = '';
            (data.messages || []).forEach(m => appendMessage(m.role, m.content));
            closeHistory();
            scrollToBottom();
        } catch (e) {
            alert('Failed to load conversation.');
        }
    }

    // ── Suggestions ──
    document.querySelectorAll('.aic-suggestion').forEach(s => {
        s.addEventListener('click', () => {
            input.value = s.dataset.msg;
            updateSendState();
            sendMessage();
        });
    });

    // ── Input handling ──
    input.addEventListener('input', () => {
        // Auto-resize
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 100) + 'px';
        updateSendState();
    });

    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    function updateSendState() {
        sendBtn.disabled = isSending || input.value.trim().length === 0;
    }

    sendBtn.addEventListener('click', sendMessage);

    // ── Message rendering ──
    function appendMessage(role, text) {
        const el = document.createElement('div');
        el.className = 'aic-msg aic-msg-' + (role === 'user' ? 'user' : role === 'assistant' ? 'assistant' : 'error');
        el.textContent = text;
        messagesEl.appendChild(el);
        scrollToBottom();
        return el;
    }

    function appendTyping() {
        const el = document.createElement('div');
        el.className = 'aic-typing';
        el.id = 'aicTyping';
        el.innerHTML = '<span class="aic-typing-dot"></span><span class="aic-typing-dot"></span><span class="aic-typing-dot"></span>';
        messagesEl.appendChild(el);
        scrollToBottom();
    }

    function removeTyping() {
        document.getElementById('aicTyping')?.remove();
    }

    function scrollToBottom() {
        const body = document.getElementById('aicBody');
        body.scrollTop = body.scrollHeight;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    }

    // ── Send ──
    async function sendMessage() {
        const text = input.value.trim();
        if (!text || isSending) return;

        isSending = true;
        welcome.style.display = 'none';
        appendMessage('user', text);
        input.value = '';
        input.style.height = 'auto';
        updateSendState();
        appendTyping();

        try {
            const r = await fetch('/ai-chatbot/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ message: text, conversation_id: currentConvId }),
            });

            removeTyping();
            const data = await r.json();

            if (!data.success) {
                appendMessage('error', data.message || 'Something went wrong. Please try again.');
            } else {
                currentConvId = data.conversation_id;
                statusEl.textContent = data.title || 'Here to help';
                appendMessage('assistant', data.reply);
                limitEl.textContent = data.remaining_today >= 999999
                    ? ''
                    : `${data.remaining_today} messages remaining today`;
            }
        } catch (e) {
            removeTyping();
            appendMessage('error', 'Network error. Please check your connection.');
        } finally {
            isSending = false;
            updateSendState();
            input.focus();
        }
    }

    async function refreshLimit() {
        try {
            const r = await fetch('/ai-chatbot/conversations', { credentials: 'same-origin' });
            const data = await r.json();
            if (data.daily_limit > 0) {
                limitEl.textContent = `${data.remaining_today} messages remaining today`;
            }
        } catch (_) {}
    }
})();
</script>
@endif
