@extends('layouts.professional')

@section('title', 'Messages')
@section('page-title', 'Messages')

@section('content')
<div id="chat-app" class="chat-container" data-user-id="{{ $currentUser->id }}" data-user-name="{{ $currentUser->name }}" @if($initialConversationId) data-initial-conversation="{{ $initialConversationId }}" @endif>

    {{-- Left: Conversations List --}}
    <div class="chat-sidebar" id="chat-sidebar">
        <div class="chat-sidebar-header">
            <h5 class="chat-h5">Chats</h5>
            <button class="chat-btn chat-btn-primary chat-btn-sm" id="new-conversation-btn" title="New Conversation">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
        </div>

        {{-- Search --}}
        <div class="chat-search">
            <input type="text" class="chat-form-control" id="conversation-search" placeholder="Search conversations...">
        </div>

        {{-- Filter Tabs --}}
        <div class="chat-filter-tabs">
            <div class="chat-btn-group" role="group">
                <button type="button" class="chat-filter-btn active" data-filter="all">All</button>
                <button type="button" class="chat-filter-btn" data-filter="direct">Direct</button>
                <button type="button" class="chat-filter-btn" data-filter="booking">Booking</button>
                <button type="button" class="chat-filter-btn" data-filter="event">Event</button>
            </div>
        </div>

        {{-- Conversation List --}}
        <div class="conversation-list" id="conversation-list">
            <div class="chat-loading" id="conversations-loading">
                <div class="chat-spinner"></div>
                <div class="chat-loading-text">Loading conversations...</div>
            </div>
        </div>
    </div>

    {{-- Right: Chat Window --}}
    <div class="chat-main" id="chat-main">
        {{-- Empty State --}}
        <div class="chat-empty-state" id="chat-empty-state">
            <div class="chat-empty-content">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.15"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <h5 class="chat-empty-title">Select a conversation</h5>
                <p class="chat-empty-text">Or start a new one to begin chatting</p>
            </div>
        </div>

        {{-- Chat Header (hidden initially) --}}
        <div class="chat-header d-none" id="chat-header">
            <button class="chat-btn chat-btn-ghost chat-btn-sm chat-back-btn" id="back-to-list">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <div class="chat-header-info">
                <h6 class="chat-header-name" id="chat-header-name"></h6>
                <small class="chat-header-context" id="chat-header-context"></small>
            </div>
        </div>

        {{-- Messages Area (hidden initially) --}}
        <div class="chat-messages d-none" id="chat-messages">
            <div class="chat-loading d-none" id="messages-loading">
                <div class="chat-spinner"></div>
            </div>
            <div id="messages-container"></div>
            <div class="typing-indicator d-none" id="typing-indicator">
                <span class="typing-name"></span> is typing
                <span class="typing-dots"><span>.</span><span>.</span><span>.</span></span>
            </div>
        </div>

        {{-- Input Area (hidden initially) --}}
        <div class="chat-input d-none" id="chat-input">
            {{-- Attachment Preview --}}
            <div class="attachment-preview d-none" id="attachment-preview"></div>

            <div class="chat-input-row">
                <button class="chat-btn chat-btn-ghost chat-btn-sm" id="attach-btn" title="Attach file">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                </button>
                <input type="file" id="file-input" class="d-none" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.mp4,.webm">
                <textarea id="message-input" class="chat-form-control chat-textarea" rows="1" placeholder="Type a message..." maxlength="5000"></textarea>
                <button class="chat-btn chat-btn-primary chat-btn-sm" id="send-btn" disabled title="Send">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- New Conversation Modal --}}
    <div class="chat-modal-overlay" id="newConversationModal">
        <div class="chat-modal">
            <div class="chat-modal-header">
                <h5>New Conversation</h5>
                <button type="button" class="chat-modal-close" data-bs-dismiss="modal" onclick="document.getElementById('newConversationModal').classList.remove('show')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="chat-modal-body">
                <div class="chat-form-group">
                    <label class="chat-form-label">Type</label>
                    <select class="chat-form-select" id="new-conv-type">
                        <option value="direct">Direct Message</option>
                        <option value="booking">Booking Chat</option>
                        <option value="event">Event Chat</option>
                    </select>
                </div>
                <div class="chat-form-group" id="participant-group">
                    <label class="chat-form-label">Participant</label>
                    <select class="chat-form-select" id="new-conv-participant">
                        <option value="">Select user...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="chat-form-group d-none" id="booking-group">
                    <label class="chat-form-label">Booking</label>
                    <select class="chat-form-select" id="new-conv-booking">
                        <option value="">Select booking...</option>
                        @foreach($bookings as $booking)
                            <option value="{{ $booking->id }}"
                                data-client="{{ $booking->client_id }}"
                                data-supplier="{{ $booking->supplier_id }}">
                                #{{ $booking->id }} — {{ $booking->event->title ?? 'N/A' }} ({{ ucfirst($booking->status) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="chat-form-group d-none" id="event-group">
                    <label class="chat-form-label">Event</label>
                    <select class="chat-form-select" id="new-conv-event">
                        <option value="">Select event...</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="chat-modal-footer">
                <button type="button" class="chat-btn chat-btn-ghost" onclick="document.getElementById('newConversationModal').classList.remove('show')">Cancel</button>
                <button type="button" class="chat-btn chat-btn-primary" id="create-conversation-btn">Start Chat</button>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== Theme Variables ─────────────────────────────────
   The chat UI has its own palette tokens so the component can live on
   any page background. Dark is the default; the [data-theme="light"]
   override below swaps every token — all chat CSS consumes the vars,
   so no further rule-by-rule light-mode work is needed.
====================================================== */
.chat-container {
    --chat-bg: #0c1427;
    --chat-sidebar-bg: #111a2e;
    --chat-border: #1e2d44;
    --chat-hover: #182541;
    --chat-active: #1a3058;
    --chat-item-border: #1e2d44;
    --chat-text: #e1e5eb;
    --chat-text-muted: #8a94a6;
    --chat-text-subtle: #5a6577;
    --chat-received-bg: #1e2d44;
    --chat-received-text: #d1d5db;
    --chat-sent-bg: #3b82f6;
    --chat-sent-text: #ffffff;
    --chat-date-bg: #1a2540;
    --chat-date-text: #5a6577;
    --chat-input-bg: #111a2e;
    --chat-input-border: #1e2d44;
    --chat-input-text: #e1e5eb;
    --chat-attachment-bg: #1e2d44;
    --chat-file-link-bg: rgba(255,255,255,0.08);
    --chat-file-received-text: #d1d5db;
    --chat-empty-icon: 0.15;
    --chat-shadow: 0 1px 4px rgba(0,0,0,0.3);
    --chat-modal-bg: #111a2e;
    --chat-modal-border: #1e2d44;
    --chat-ghost-bg: rgba(255,255,255,0.06);
    --chat-ghost-hover-bg: rgba(255,255,255,0.1);
    --chat-filter-hover-bg: rgba(255,255,255,0.04);
}

/* Light-mode token swap — triggered by the root data-theme="light" that
   the layout toggles. Mirrors the palette used by .cl-* components:
   white cards on a soft slate page, slate-700 text, slate-200 borders. */
[data-theme="light"] .chat-container {
    --chat-bg: #ffffff;
    --chat-sidebar-bg: #f8fafc;
    --chat-border: #e2e8f0;
    --chat-hover: #f1f5f9;
    --chat-active: #e0e7ff;
    --chat-item-border: #e2e8f0;
    --chat-text: #0f172a;
    --chat-text-muted: #64748b;
    --chat-text-subtle: #94a3b8;
    --chat-received-bg: #f1f5f9;
    --chat-received-text: #0f172a;
    --chat-sent-bg: #3b82f6;
    --chat-sent-text: #ffffff;
    --chat-date-bg: #f1f5f9;
    --chat-date-text: #64748b;
    --chat-input-bg: #ffffff;
    --chat-input-border: #cbd5e1;
    --chat-input-text: #0f172a;
    --chat-attachment-bg: #f1f5f9;
    --chat-file-link-bg: rgba(15,23,42,0.06);
    --chat-file-received-text: #0f172a;
    --chat-empty-icon: 0.25;
    --chat-shadow: 0 1px 4px rgba(15,23,42,0.08);
    --chat-modal-bg: #ffffff;
    --chat-modal-border: #e2e8f0;
    --chat-ghost-bg: rgba(15,23,42,0.04);
    --chat-ghost-hover-bg: rgba(15,23,42,0.08);
    --chat-filter-hover-bg: rgba(15,23,42,0.04);
}

/* ===== Utility ===== */
.d-none { display: none !important; }

/* ===== Chat Layout ===== */
.chat-container {
    display: flex;
    height: calc(100vh - var(--navbar-height, 64px) - 24px);
    background: var(--chat-bg);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--chat-shadow);
}

/* ===== Chat Buttons (standalone, no Bootstrap) ===== */
.chat-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid transparent;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    font-family: inherit;
    text-decoration: none;
    background: transparent;
    color: var(--chat-text);
}
.chat-btn-primary {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
}
.chat-btn-primary:hover { background: #2563eb; }
.chat-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.chat-btn-ghost {
    background: var(--chat-ghost-bg);
    border-color: var(--chat-border);
    color: var(--chat-text-muted);
}
.chat-btn-ghost:hover { background: var(--chat-ghost-hover-bg); color: var(--chat-text); }
.chat-btn-sm { padding: 6px 10px; font-size: 13px; }

/* ===== Chat Form Controls ===== */
.chat-form-control {
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid var(--chat-input-border);
    background: var(--chat-input-bg);
    color: var(--chat-input-text);
    font-size: 14px;
    font-family: inherit;
    outline: none;
    transition: border-color 0.15s;
}
.chat-form-control:focus { border-color: #3b82f6; }
.chat-form-control::placeholder { color: var(--chat-text-subtle); }
.chat-form-select {
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid var(--chat-input-border);
    background: var(--chat-input-bg);
    color: var(--chat-input-text);
    font-size: 14px;
    font-family: inherit;
    outline: none;
    appearance: auto;
}
.chat-form-group { margin-bottom: 16px; }
.chat-form-label { display: block; font-size: 13px; font-weight: 500; color: var(--chat-text-muted); margin-bottom: 6px; }
.chat-textarea {
    resize: none;
    max-height: 120px;
    border-radius: 20px;
    padding: 8px 16px;
}
.chat-h5 { font-size: 18px; font-weight: 600; margin: 0; }

/* ===== Sidebar ===== */
.chat-sidebar {
    width: 340px;
    min-width: 340px;
    border-right: 1px solid var(--chat-border);
    display: flex;
    flex-direction: column;
    background: var(--chat-sidebar-bg);
}
.chat-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 16px 8px;
    color: var(--chat-text);
}
.chat-search { padding: 0 12px 8px; }
.chat-filter-tabs { padding: 0 12px 8px; }
.chat-btn-group {
    display: flex;
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--chat-border);
}
.chat-filter-btn {
    flex: 1;
    padding: 6px 8px;
    font-size: 12px;
    font-weight: 500;
    border: none;
    background: transparent;
    color: var(--chat-text-muted);
    cursor: pointer;
    transition: all 0.15s;
    font-family: inherit;
}
.chat-filter-btn:not(:last-child) { border-right: 1px solid var(--chat-border); }
.chat-filter-btn:hover { background: var(--chat-filter-hover-bg); }
.chat-filter-btn.active { background: var(--chat-active); color: var(--chat-text); }
.conversation-list { flex: 1; overflow-y: auto; }

/* ===== Conversation Item ===== */
.conversation-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid var(--chat-item-border);
    transition: background 0.15s;
}
.conversation-item:hover { background: var(--chat-hover); }
.conversation-item.active { background: var(--chat-active); }
.conv-avatar {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: #6c757d; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 600; font-size: 16px;
    flex-shrink: 0; margin-right: 12px;
}
.conv-avatar.direct { background: #0d6efd; }
.conv-avatar.booking { background: #198754; }
.conv-avatar.event { background: #6f42c1; }
.conv-info { flex: 1; min-width: 0; }
.conv-name { font-weight: 600; font-size: 14px; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--chat-text); }
.conv-preview { font-size: 12px; color: var(--chat-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.conv-meta { text-align: right; flex-shrink: 0; margin-left: 8px; }
.conv-time { font-size: 11px; color: var(--chat-text-subtle); display: block; }
.conv-unread {
    display: inline-flex; align-items: center; justify-content: center;
    background: #0d6efd; color: #fff; border-radius: 10px;
    min-width: 20px; height: 20px; font-size: 11px; font-weight: 600;
    padding: 0 6px; margin-top: 4px;
}

/* ===== Main Chat ===== */
.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: var(--chat-bg);
}
.chat-empty-state {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--chat-text-muted);
}
.chat-empty-content { text-align: center; }
.chat-empty-title { font-size: 18px; font-weight: 600; margin-top: 12px; color: var(--chat-text-muted); }
.chat-empty-text { font-size: 13px; color: var(--chat-text-subtle); margin-top: 4px; }
.chat-header {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid var(--chat-border);
    background: var(--chat-bg);
}
.chat-back-btn { display: none; margin-right: 8px; }
.chat-header-info h6, .chat-header-name { font-size: 15px; font-weight: 600; color: var(--chat-text); margin: 0; }
.chat-header-context { font-size: 12px; color: var(--chat-text-muted); }

/* ===== Messages ===== */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    background: var(--chat-bg);
}
#messages-container { display: flex; flex-direction: column; gap: 4px; }

/* Message Bubbles */
.message-row { display: flex; margin-bottom: 2px; }
.message-row.sent { justify-content: flex-end; }
.message-row.received { justify-content: flex-start; }
.message-bubble {
    max-width: 65%;
    padding: 8px 14px;
    border-radius: 16px;
    font-size: 14px;
    line-height: 1.4;
    word-wrap: break-word;
    position: relative;
}
.message-row.sent .message-bubble { background: var(--chat-sent-bg); color: var(--chat-sent-text); border-bottom-right-radius: 4px; }
.message-row.received .message-bubble { background: var(--chat-received-bg); color: var(--chat-received-text); border-bottom-left-radius: 4px; }
.message-sender { font-size: 11px; font-weight: 600; margin-bottom: 2px; color: var(--chat-text-muted); }
.message-row.received .message-sender { color: #6ea8fe; }
.message-meta { font-size: 10px; margin-top: 4px; display: flex; align-items: center; gap: 4px; }
.message-row.sent .message-meta { color: rgba(255,255,255,0.7); justify-content: flex-end; }
.message-row.received .message-meta { color: var(--chat-text-subtle); }
.read-receipt { font-size: 12px; }
.read-receipt.read { color: rgba(255,255,255,0.9); }

/* Date Separator */
.date-separator { text-align: center; margin: 12px 0; font-size: 12px; color: var(--chat-date-text); }
.date-separator span { background: var(--chat-date-bg); padding: 2px 12px; border-radius: 10px; }

/* Attachment in bubble */
.message-attachment { margin-top: 6px; }
.message-attachment img { max-width: 240px; max-height: 180px; border-radius: 8px; cursor: pointer; }
.message-attachment .file-link {
    display: flex; align-items: center; gap: 6px;
    padding: 6px 10px; background: var(--chat-file-link-bg);
    border-radius: 8px; font-size: 12px; text-decoration: none;
}
.message-row.sent .file-link { color: #fff; }
.message-row.received .file-link { color: var(--chat-file-received-text); }

/* Typing */
.typing-indicator { font-size: 12px; color: var(--chat-text-muted); padding: 4px 0; }
.typing-dots span { animation: typingDot 1.4s infinite; }
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typingDot { 0%, 60%, 100% { opacity: 0.2; } 30% { opacity: 1; } }

/* ===== Input Area ===== */
.chat-input {
    padding: 12px 16px;
    border-top: 1px solid var(--chat-border);
    background: var(--chat-bg);
}
.chat-input-row { display: flex; align-items: flex-end; gap: 8px; }
.attachment-preview { display: flex; gap: 8px; padding-bottom: 8px; flex-wrap: wrap; }
.attachment-preview-item {
    position: relative; padding: 6px 10px;
    background: var(--chat-attachment-bg); border-radius: 8px;
    font-size: 12px; display: flex; align-items: center; gap: 6px; color: var(--chat-text);
}
.attachment-preview-item .remove-attachment { cursor: pointer; color: #dc3545; font-weight: bold; }

/* ===== Loading ===== */
.chat-loading { text-align: center; padding: 16px; color: var(--chat-text-muted); }
.chat-loading-text { font-size: 12px; margin-top: 8px; }
.chat-spinner {
    width: 20px; height: 20px;
    border: 2px solid var(--chat-border);
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: chatSpin 0.6s linear infinite;
    margin: 0 auto;
}
@keyframes chatSpin { to { transform: rotate(360deg); } }

/* ===== Modal ===== */
.chat-modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.chat-modal-overlay.show { display: flex; }
.chat-modal {
    background: var(--chat-modal-bg);
    border: 1px solid var(--chat-modal-border);
    border-radius: 12px;
    width: 480px;
    max-width: 90%;
    color: var(--chat-text);
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}
.chat-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--chat-modal-border);
}
.chat-modal-header h5 { font-size: 16px; font-weight: 600; margin: 0; }
.chat-modal-close {
    background: none; border: none; cursor: pointer;
    color: var(--chat-text-muted); padding: 4px;
    display: flex; align-items: center; justify-content: center;
}
.chat-modal-close:hover { color: var(--chat-text); }
.chat-modal-body { padding: 20px; }
.chat-modal-footer {
    display: flex; gap: 8px; justify-content: flex-end;
    padding: 12px 20px;
    border-top: 1px solid var(--chat-modal-border);
}

/* ===== Mobile ===== */
@media (max-width: 768px) {
    .chat-sidebar { width: 100%; min-width: 100%; }
    .chat-main { display: none; }
    .chat-container.chat-active .chat-sidebar { display: none; }
    .chat-container.chat-active .chat-main { display: flex; }
    .chat-back-btn { display: flex; }
}
</style>
@endsection

@push('scripts')
<script>
    // Bootstrap compatibility shim — chat.js uses Bootstrap modal API
    // We implement a lightweight replacement
    (function() {
        // Handle new conversation button — open our custom modal
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('newConversationModal');
            const newBtn = document.getElementById('new-conversation-btn');
            if (newBtn && modal) {
                newBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    modal.classList.add('show');
                });
            }
            // Close modal on overlay click
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) this.classList.remove('show');
                });
            }
            // Close on Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal) modal.classList.remove('show');
            });

            // Bootstrap Modal stub so chat.js doesn't crash
            if (typeof bootstrap === 'undefined') {
                window.bootstrap = {
                    Modal: function(el) {
                        this.el = el;
                        this.show = function() { el.classList.add('show'); };
                        this.hide = function() { el.classList.remove('show'); };
                    }
                };
                window.bootstrap.Modal.getInstance = function(el) {
                    return new window.bootstrap.Modal(el);
                };
                window.bootstrap.Modal.getOrCreateInstance = function(el) {
                    return new window.bootstrap.Modal(el);
                };
            }
        });
    })();
</script>
<script src="{{ asset('js/chat.js') }}" defer></script>
@endpush
