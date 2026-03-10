@extends('layouts.dashboard')

@section('title', 'Chat')

@section('content')
<div id="chat-app" class="chat-container" data-user-id="{{ $currentUser->id }}" data-user-name="{{ $currentUser->name }}" @if($initialConversationId) data-initial-conversation="{{ $initialConversationId }}" @endif>

    {{-- Left: Conversations List --}}
    <div class="chat-sidebar" id="chat-sidebar">
        <div class="chat-sidebar-header">
            <h5 class="mb-0">Chats</h5>
            <button class="btn btn-sm btn-primary" id="new-conversation-btn" title="New Conversation">
                <i data-lucide="plus" style="width:16px;height:16px"></i>
            </button>
        </div>

        {{-- Search --}}
        <div class="chat-search px-3 pb-2">
            <input type="text" class="form-control form-control-sm" id="conversation-search" placeholder="Search conversations...">
        </div>

        {{-- Filter Tabs --}}
        <div class="chat-filter-tabs px-3 pb-2">
            <div class="btn-group btn-group-sm w-100" role="group">
                <button type="button" class="btn btn-outline-secondary active" data-filter="all">All</button>
                <button type="button" class="btn btn-outline-secondary" data-filter="direct">Direct</button>
                <button type="button" class="btn btn-outline-secondary" data-filter="booking">Booking</button>
                <button type="button" class="btn btn-outline-secondary" data-filter="event">Event</button>
            </div>
        </div>

        {{-- Conversation List --}}
        <div class="conversation-list" id="conversation-list">
            <div class="text-center text-muted p-4" id="conversations-loading">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <div class="mt-2 small">Loading conversations...</div>
            </div>
        </div>
    </div>

    {{-- Right: Chat Window --}}
    <div class="chat-main" id="chat-main">
        {{-- Empty State --}}
        <div class="chat-empty-state" id="chat-empty-state">
            <div class="text-center text-muted">
                <i data-lucide="message-circle" style="width:64px;height:64px;opacity:0.3"></i>
                <h5 class="mt-3">Select a conversation</h5>
                <p class="small">Or start a new one to begin chatting</p>
            </div>
        </div>

        {{-- Chat Header (hidden initially) --}}
        <div class="chat-header d-none" id="chat-header">
            <button class="btn btn-sm btn-light d-md-none me-2" id="back-to-list">
                <i data-lucide="arrow-left" style="width:16px;height:16px"></i>
            </button>
            <div class="chat-header-info">
                <h6 class="mb-0" id="chat-header-name"></h6>
                <small class="text-muted" id="chat-header-context"></small>
            </div>
        </div>

        {{-- Messages Area (hidden initially) --}}
        <div class="chat-messages d-none" id="chat-messages">
            <div class="text-center p-3 d-none" id="messages-loading">
                <div class="spinner-border spinner-border-sm" role="status"></div>
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
                <button class="btn btn-sm btn-light" id="attach-btn" title="Attach file">
                    <i data-lucide="paperclip" style="width:18px;height:18px"></i>
                </button>
                <input type="file" id="file-input" class="d-none" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.mp4,.webm">
                <textarea id="message-input" class="form-control" rows="1" placeholder="Type a message..." maxlength="5000"></textarea>
                <button class="btn btn-primary btn-sm" id="send-btn" disabled title="Send">
                    <i data-lucide="send" style="width:18px;height:18px"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- New Conversation Modal --}}
    <div class="modal fade" id="newConversationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Conversation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" id="new-conv-type">
                            <option value="direct">Direct Message</option>
                            <option value="booking">Booking Chat</option>
                            <option value="event">Event Chat</option>
                        </select>
                    </div>
                    <div class="mb-3" id="participant-group">
                        <label class="form-label">Participant</label>
                        <select class="form-select" id="new-conv-participant">
                            <option value="">Select user...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="booking-group">
                        <label class="form-label">Booking</label>
                        <select class="form-select" id="new-conv-booking">
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
                    <div class="mb-3 d-none" id="event-group">
                        <label class="form-label">Event</label>
                        <select class="form-select" id="new-conv-event">
                            <option value="">Select event...</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}">{{ $event->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="create-conversation-btn">Start Chat</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== Theme Variables ===== */
:root,
[data-bs-theme="light"] {
    --chat-bg: #ffffff;
    --chat-sidebar-bg: #f8f9fa;
    --chat-border: #e9ecef;
    --chat-hover: #e9ecef;
    --chat-active: #e3edf7;
    --chat-item-border: #f0f0f0;
    --chat-text: #212529;
    --chat-text-muted: #6c757d;
    --chat-text-subtle: #adb5bd;
    --chat-received-bg: #e9ecef;
    --chat-received-text: #212529;
    --chat-sent-bg: #0d6efd;
    --chat-sent-text: #ffffff;
    --chat-date-bg: #f0f0f0;
    --chat-date-text: #adb5bd;
    --chat-input-bg: #ffffff;
    --chat-input-border: #dee2e6;
    --chat-input-text: #212529;
    --chat-attachment-bg: #e9ecef;
    --chat-file-link-bg: rgba(0,0,0,0.1);
    --chat-file-received-text: #212529;
    --chat-empty-icon: 0.3;
    --chat-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

[data-bs-theme="dark"] {
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
}

/* ===== Chat Layout ===== */
.chat-container {
    display: flex;
    height: calc(100vh - 130px);
    background: var(--chat-bg);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--chat-shadow);
}

/* Sidebar */
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
.conversation-list {
    flex: 1;
    overflow-y: auto;
}

/* Conversation Item */
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
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #6c757d;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
    margin-right: 12px;
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

/* Main Chat */
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
.chat-empty-state svg { opacity: var(--chat-empty-icon); }
.chat-header {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid var(--chat-border);
    background: var(--chat-bg);
}
.chat-header-info h6 { font-size: 15px; color: var(--chat-text); }
.chat-header-info small { color: var(--chat-text-muted); }

/* Messages */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    background: var(--chat-bg);
}
#messages-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

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
.message-row.sent .message-bubble {
    background: var(--chat-sent-bg);
    color: var(--chat-sent-text);
    border-bottom-right-radius: 4px;
}
.message-row.received .message-bubble {
    background: var(--chat-received-bg);
    color: var(--chat-received-text);
    border-bottom-left-radius: 4px;
}

.message-sender {
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 2px;
    color: var(--chat-text-muted);
}
.message-row.received .message-sender { color: #6ea8fe; }

.message-meta {
    font-size: 10px;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.message-row.sent .message-meta { color: rgba(255,255,255,0.7); justify-content: flex-end; }
.message-row.received .message-meta { color: var(--chat-text-subtle); }

.read-receipt { font-size: 12px; }
.read-receipt.read { color: rgba(255,255,255,0.9); }

/* Date Separator */
.date-separator {
    text-align: center;
    margin: 12px 0;
    font-size: 12px;
    color: var(--chat-date-text);
}
.date-separator span {
    background: var(--chat-date-bg);
    padding: 2px 12px;
    border-radius: 10px;
}

/* Attachment in bubble */
.message-attachment {
    margin-top: 6px;
}
.message-attachment img {
    max-width: 240px;
    max-height: 180px;
    border-radius: 8px;
    cursor: pointer;
}
.message-attachment .file-link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    background: var(--chat-file-link-bg);
    border-radius: 8px;
    font-size: 12px;
    text-decoration: none;
}
.message-row.sent .file-link { color: #fff; }
.message-row.received .file-link { color: var(--chat-file-received-text); }

/* Typing Indicator */
.typing-indicator {
    font-size: 12px;
    color: var(--chat-text-muted);
    padding: 4px 0;
}
.typing-dots span {
    animation: typingDot 1.4s infinite;
}
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typingDot {
    0%, 60%, 100% { opacity: 0.2; }
    30% { opacity: 1; }
}

/* Input Area */
.chat-input {
    padding: 12px 16px;
    border-top: 1px solid var(--chat-border);
    background: var(--chat-bg);
}
.chat-input-row {
    display: flex;
    align-items: flex-end;
    gap: 8px;
}
#message-input {
    resize: none;
    max-height: 120px;
    border-radius: 20px;
    padding: 8px 16px;
    font-size: 14px;
    background: var(--chat-input-bg);
    border-color: var(--chat-input-border);
    color: var(--chat-input-text);
}
#message-input::placeholder { color: var(--chat-text-subtle); }
.attachment-preview {
    display: flex;
    gap: 8px;
    padding-bottom: 8px;
    flex-wrap: wrap;
}
.attachment-preview-item {
    position: relative;
    padding: 6px 10px;
    background: var(--chat-attachment-bg);
    border-radius: 8px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--chat-text);
}
.attachment-preview-item .remove-attachment {
    cursor: pointer;
    color: #dc3545;
    font-weight: bold;
}

/* Search input dark mode */
.chat-search .form-control {
    background: var(--chat-input-bg);
    border-color: var(--chat-input-border);
    color: var(--chat-input-text);
}
.chat-search .form-control::placeholder { color: var(--chat-text-subtle); }

/* Filter buttons dark mode */
[data-bs-theme="dark"] .chat-filter-tabs .btn-outline-secondary {
    color: var(--chat-text-muted);
    border-color: var(--chat-border);
}
[data-bs-theme="dark"] .chat-filter-tabs .btn-outline-secondary.active {
    background: var(--chat-active);
    color: var(--chat-text);
    border-color: var(--chat-active);
}

/* No conversations text */
.conversation-list .text-muted { color: var(--chat-text-subtle) !important; }

/* Mobile Responsive */
@media (max-width: 768px) {
    .chat-sidebar { width: 100%; min-width: 100%; }
    .chat-main { display: none; }
    .chat-container.chat-active .chat-sidebar { display: none; }
    .chat-container.chat-active .chat-main { display: flex; }
}
</style>

@push('scripts')
<script src="{{ asset('js/chat.js') }}" defer></script>
@endpush
@endsection
