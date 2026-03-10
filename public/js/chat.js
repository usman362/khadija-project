/**
 * Khadija Chat Application
 * WhatsApp/Messenger-style real-time chat using Laravel Echo + Reverb
 */
(function () {
    'use strict';

    // ===== State =====
    const state = {
        userId: null,
        userName: null,
        conversations: new Map(),
        activeConversationId: null,
        currentFilter: 'all',
        subscribedChannels: new Map(),
        typingTimers: new Map(),
        pendingAttachments: [],
        messagesPage: 1,
        hasMoreMessages: true,
        loadingMessages: false,
    };

    // ===== DOM References =====
    const dom = {};

    // ===== Init =====
    function init() {
        const app = document.getElementById('chat-app');
        if (!app) return;

        state.userId = parseInt(app.dataset.userId);
        state.userName = app.dataset.userName;
        const initialConversation = app.dataset.initialConversation;

        cacheDom();
        bindEvents();
        loadConversations().then(() => {
            if (initialConversation) {
                selectConversation(parseInt(initialConversation));
            }
        });

        // Re-init lucide icons for new elements
        if (window.lucide) lucide.createIcons();
    }

    function cacheDom() {
        dom.app = document.getElementById('chat-app');
        dom.sidebar = document.getElementById('chat-sidebar');
        dom.conversationList = document.getElementById('conversation-list');
        dom.conversationsLoading = document.getElementById('conversations-loading');
        dom.searchInput = document.getElementById('conversation-search');
        dom.chatMain = document.getElementById('chat-main');
        dom.emptyState = document.getElementById('chat-empty-state');
        dom.chatHeader = document.getElementById('chat-header');
        dom.headerName = document.getElementById('chat-header-name');
        dom.headerContext = document.getElementById('chat-header-context');
        dom.messagesArea = document.getElementById('chat-messages');
        dom.messagesContainer = document.getElementById('messages-container');
        dom.messagesLoading = document.getElementById('messages-loading');
        dom.typingIndicator = document.getElementById('typing-indicator');
        dom.chatInput = document.getElementById('chat-input');
        dom.messageInput = document.getElementById('message-input');
        dom.sendBtn = document.getElementById('send-btn');
        dom.attachBtn = document.getElementById('attach-btn');
        dom.fileInput = document.getElementById('file-input');
        dom.attachmentPreview = document.getElementById('attachment-preview');
        dom.newConversationBtn = document.getElementById('new-conversation-btn');
        dom.backToList = document.getElementById('back-to-list');
    }

    function bindEvents() {
        dom.searchInput.addEventListener('input', debounce(handleSearch, 300));
        dom.messageInput.addEventListener('input', handleMessageInput);
        dom.messageInput.addEventListener('keydown', handleMessageKeydown);
        dom.sendBtn.addEventListener('click', sendMessage);
        dom.attachBtn.addEventListener('click', () => dom.fileInput.click());
        dom.fileInput.addEventListener('change', handleFileSelect);
        dom.newConversationBtn.addEventListener('click', openNewConversationModal);
        dom.backToList.addEventListener('click', goBackToList);

        document.getElementById('create-conversation-btn').addEventListener('click', createConversation);

        // Toggle booking/event selectors based on type
        document.getElementById('new-conv-type').addEventListener('change', function () {
            const type = this.value;
            document.getElementById('booking-group').classList.toggle('d-none', type !== 'booking');
            document.getElementById('event-group').classList.toggle('d-none', type !== 'event');
            // For booking type, auto-select participant when a booking is chosen
            if (type === 'booking') {
                document.getElementById('participant-group').classList.add('d-none');
            } else {
                document.getElementById('participant-group').classList.remove('d-none');
            }
        });

        // Auto-select participant when booking is selected
        document.getElementById('new-conv-booking').addEventListener('change', function () {
            const option = this.options[this.selectedIndex];
            if (!option.value) return;
            const currentUserId = parseInt(dom.app.dataset.userId);
            const clientId = parseInt(option.dataset.client);
            const supplierId = parseInt(option.dataset.supplier);
            // The other party is the participant
            const otherPartyId = (clientId === currentUserId) ? supplierId : clientId;
            const participantSelect = document.getElementById('new-conv-participant');
            participantSelect.value = otherPartyId;
        });

        // Filter tabs
        document.querySelectorAll('.chat-filter-tabs [data-filter]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.chat-filter-tabs .btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                state.currentFilter = btn.dataset.filter;
                renderConversationList();
            });
        });

        // Scroll to load more messages
        dom.messagesArea.addEventListener('scroll', () => {
            if (dom.messagesArea.scrollTop < 50 && state.hasMoreMessages && !state.loadingMessages) {
                loadMoreMessages();
            }
        });
    }

    // ===== API Calls =====
    async function api(method, url, data = null) {
        const config = { method, headers: { 'X-Requested-With': 'XMLHttpRequest' } };
        if (data && method !== 'GET') {
            if (data instanceof FormData) {
                config.body = data;
            } else {
                config.headers['Content-Type'] = 'application/json';
                config.body = JSON.stringify(data);
            }
        }
        // Get CSRF token
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) config.headers['X-CSRF-TOKEN'] = token.content;

        const response = await fetch(url, config);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    }

    // ===== Conversations =====
    async function loadConversations() {
        try {
            const params = new URLSearchParams();
            if (state.currentFilter !== 'all') params.set('type', state.currentFilter);
            const data = await api('GET', `/conversations?${params}`);
            state.conversations.clear();
            (data.data || []).forEach(conv => state.conversations.set(conv.id, conv));
            renderConversationList();
            subscribeToAll();
        } catch (err) {
            console.error('Failed to load conversations:', err);
        } finally {
            if (dom.conversationsLoading) dom.conversationsLoading.classList.add('d-none');
        }
    }

    function renderConversationList() {
        const list = dom.conversationList;
        // Remove old items but keep loading spinner
        list.querySelectorAll('.conversation-item').forEach(el => el.remove());

        const filtered = [...state.conversations.values()].filter(conv => {
            if (state.currentFilter === 'all') return true;
            return conv.type === state.currentFilter;
        });

        if (filtered.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'conversation-item text-center text-muted small';
            empty.textContent = 'No conversations yet';
            list.appendChild(empty);
            return;
        }

        filtered.forEach(conv => {
            list.appendChild(createConversationItem(conv));
        });
    }

    function createConversationItem(conv) {
        const el = document.createElement('div');
        el.className = 'conversation-item' + (conv.id === state.activeConversationId ? ' active' : '');
        el.dataset.id = conv.id;
        el.addEventListener('click', () => selectConversation(conv.id));

        const otherParticipants = (conv.participants || []).filter(p => p.id !== state.userId);
        const displayName = getConversationName(conv, otherParticipants);
        const initial = displayName.charAt(0).toUpperCase();
        const preview = conv.last_message_body || 'No messages yet';
        const time = conv.last_message_at ? formatTime(conv.last_message_at) : '';

        el.innerHTML = `
            <div class="conv-avatar ${conv.type}">${initial}</div>
            <div class="conv-info">
                <div class="conv-name">${escapeHtml(displayName)}</div>
                <div class="conv-preview">${escapeHtml(preview)}</div>
            </div>
            <div class="conv-meta">
                <span class="conv-time">${time}</span>
                ${conv.unread_count > 0 ? `<span class="conv-unread">${conv.unread_count}</span>` : ''}
            </div>
        `;
        return el;
    }

    function getConversationName(conv, otherParticipants) {
        if (conv.type === 'booking' && conv.booking) {
            return `Booking #${conv.booking.id}`;
        }
        if (conv.type === 'event' && conv.event) {
            return conv.event.title || `Event #${conv.event.id}`;
        }
        if (otherParticipants.length > 0) {
            return otherParticipants.map(p => p.name).join(', ');
        }
        return 'Conversation';
    }

    async function selectConversation(id) {
        state.activeConversationId = id;
        state.messagesPage = 1;
        state.hasMoreMessages = true;

        // Mobile: switch view
        dom.app.classList.add('chat-active');

        // Highlight in list
        dom.conversationList.querySelectorAll('.conversation-item').forEach(el => {
            el.classList.toggle('active', parseInt(el.dataset.id) === id);
        });

        // Show chat UI
        dom.emptyState.classList.add('d-none');
        dom.chatHeader.classList.remove('d-none');
        dom.messagesArea.classList.remove('d-none');
        dom.chatInput.classList.remove('d-none');

        // Clear previous messages
        dom.messagesContainer.innerHTML = '';

        // Load conversation data
        try {
            dom.messagesLoading.classList.remove('d-none');
            const data = await api('GET', `/conversations/${id}`);
            const conv = data.conversation;
            const messages = data.messages;

            // Update header
            const others = (conv.participants || []).filter(p => p.id !== state.userId);
            dom.headerName.textContent = getConversationName(conv, others);

            let context = conv.type;
            if (conv.booking) context = `Booking #${conv.booking.id} — ${conv.booking.status}`;
            if (conv.event) context = conv.event.title;
            dom.headerContext.textContent = context;

            // Render messages (they come newest first, reverse for display)
            const msgs = (messages.data || []).reverse();
            state.hasMoreMessages = messages.next_page_url !== null;
            renderMessages(msgs);
            scrollToBottom();

            // Mark as read
            markAsRead(id);

            // Subscribe to real-time
            subscribeToConversation(id);

        } catch (err) {
            console.error('Failed to load conversation:', err);
        } finally {
            dom.messagesLoading.classList.add('d-none');
        }

        dom.messageInput.focus();
    }

    // ===== Messages =====
    function renderMessages(messages) {
        let lastDate = null;
        messages.forEach(msg => {
            const msgDate = formatDate(msg.created_at);
            if (msgDate !== lastDate) {
                lastDate = msgDate;
                appendDateSeparator(msgDate);
            }
            appendMessage(msg);
        });
    }

    function appendMessage(msg, prepend = false) {
        const isSent = msg.sender_id === state.userId;
        const row = document.createElement('div');
        row.className = `message-row ${isSent ? 'sent' : 'received'}`;
        row.dataset.messageId = msg.id;

        const senderName = msg.sender ? msg.sender.name : 'Unknown';
        const time = formatTime(msg.created_at);
        const isRead = msg.reads && msg.reads.some(r => r.user_id !== state.userId);

        let attachmentsHtml = '';
        if (msg.attachments && msg.attachments.length > 0) {
            attachmentsHtml = msg.attachments.map(att => {
                if (att.is_image || (att.mime_type && att.mime_type.startsWith('image/'))) {
                    return `<div class="message-attachment"><img src="${att.url}" alt="${escapeHtml(att.file_name)}" loading="lazy"></div>`;
                }
                return `<div class="message-attachment"><a class="file-link" href="${att.url}" target="_blank"><i data-lucide="file" style="width:14px;height:14px"></i> ${escapeHtml(att.file_name)}</a></div>`;
            }).join('');
        }

        row.innerHTML = `
            <div class="message-bubble">
                ${!isSent ? `<div class="message-sender">${escapeHtml(senderName)}</div>` : ''}
                <div class="message-body">${escapeHtml(msg.body)}</div>
                ${attachmentsHtml}
                <div class="message-meta">
                    <span>${time}</span>
                    ${isSent ? `<span class="read-receipt ${isRead ? 'read' : ''}">${isRead ? '✓✓' : '✓'}</span>` : ''}
                </div>
            </div>
        `;

        if (prepend) {
            dom.messagesContainer.prepend(row);
        } else {
            dom.messagesContainer.appendChild(row);
        }

        if (window.lucide) lucide.createIcons({ nodes: [row] });
    }

    function appendDateSeparator(dateStr) {
        const sep = document.createElement('div');
        sep.className = 'date-separator';
        sep.innerHTML = `<span>${dateStr}</span>`;
        dom.messagesContainer.appendChild(sep);
    }

    async function loadMoreMessages() {
        if (!state.activeConversationId || state.loadingMessages) return;
        state.loadingMessages = true;
        state.messagesPage++;

        try {
            dom.messagesLoading.classList.remove('d-none');
            const data = await api('GET', `/conversations/${state.activeConversationId}?page=${state.messagesPage}`);
            const messages = data.messages;
            state.hasMoreMessages = messages.next_page_url !== null;

            const scrollH = dom.messagesArea.scrollHeight;
            const msgs = (messages.data || []).reverse();
            msgs.forEach(msg => appendMessage(msg, true));
            // Maintain scroll position
            dom.messagesArea.scrollTop = dom.messagesArea.scrollHeight - scrollH;
        } catch (err) {
            console.error('Failed to load more messages:', err);
        } finally {
            state.loadingMessages = false;
            dom.messagesLoading.classList.add('d-none');
        }
    }

    // ===== Send Message =====
    async function sendMessage() {
        const body = dom.messageInput.value.trim();
        if (!body && state.pendingAttachments.length === 0) return;
        if (!state.activeConversationId) return;

        dom.sendBtn.disabled = true;

        try {
            // Upload attachments first
            const attachmentIds = [];
            for (const file of state.pendingAttachments) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('conversation_id', state.activeConversationId);
                const result = await api('POST', '/attachments', formData);
                attachmentIds.push(result.id);
            }

            const data = await api('POST', `/conversations/${state.activeConversationId}/messages`, {
                body: body || '📎 Attachment',
                attachment_ids: attachmentIds.length > 0 ? attachmentIds : undefined,
            });

            // Append optimistically
            appendMessage(data);
            scrollToBottom();

            // Update conversation preview
            updateConversationPreview(state.activeConversationId, body, new Date().toISOString());

            // Clear input
            dom.messageInput.value = '';
            dom.messageInput.style.height = 'auto';
            clearAttachments();
        } catch (err) {
            console.error('Failed to send message:', err);
        } finally {
            dom.sendBtn.disabled = false;
            dom.messageInput.focus();
        }
    }

    // ===== Typing =====
    let typingTimeout = null;
    function handleMessageInput() {
        // Auto-resize textarea
        dom.messageInput.style.height = 'auto';
        dom.messageInput.style.height = Math.min(dom.messageInput.scrollHeight, 120) + 'px';

        // Enable/disable send
        dom.sendBtn.disabled = !dom.messageInput.value.trim() && state.pendingAttachments.length === 0;

        // Broadcast typing
        if (!typingTimeout && state.activeConversationId) {
            api('POST', `/conversations/${state.activeConversationId}/typing`).catch(() => {});
        }
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => { typingTimeout = null; }, 3000);
    }

    function handleMessageKeydown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    // ===== File Attachments =====
    function handleFileSelect(e) {
        const files = Array.from(e.target.files);
        files.forEach(file => {
            if (file.size > 10 * 1024 * 1024) {
                alert(`File "${file.name}" exceeds 10MB limit`);
                return;
            }
            state.pendingAttachments.push(file);
        });
        renderAttachmentPreview();
        dom.sendBtn.disabled = false;
        dom.fileInput.value = '';
    }

    function renderAttachmentPreview() {
        dom.attachmentPreview.innerHTML = '';
        if (state.pendingAttachments.length === 0) {
            dom.attachmentPreview.classList.add('d-none');
            return;
        }
        dom.attachmentPreview.classList.remove('d-none');

        state.pendingAttachments.forEach((file, idx) => {
            const item = document.createElement('div');
            item.className = 'attachment-preview-item';
            item.innerHTML = `
                <span>${escapeHtml(file.name)}</span>
                <span class="remove-attachment" data-index="${idx}">&times;</span>
            `;
            item.querySelector('.remove-attachment').addEventListener('click', () => {
                state.pendingAttachments.splice(idx, 1);
                renderAttachmentPreview();
                dom.sendBtn.disabled = !dom.messageInput.value.trim() && state.pendingAttachments.length === 0;
            });
            dom.attachmentPreview.appendChild(item);
        });
    }

    function clearAttachments() {
        state.pendingAttachments = [];
        dom.attachmentPreview.innerHTML = '';
        dom.attachmentPreview.classList.add('d-none');
    }

    // ===== Real-Time (Echo) =====
    function subscribeToAll() {
        state.conversations.forEach((conv, id) => subscribeToConversation(id));
    }

    function subscribeToConversation(id) {
        if (state.subscribedChannels.has(id) || !window.Echo) return;

        const channel = window.Echo.private(`conversation.${id}`);
        state.subscribedChannels.set(id, channel);

        channel.listen('.message.sent', (e) => {
            // If this is the active conversation, append the message
            if (state.activeConversationId === e.conversation_id) {
                appendMessage(e);
                scrollToBottom();
                markAsRead(e.conversation_id);
            }
            // Update conversation preview
            updateConversationPreview(e.conversation_id, e.body, e.created_at);
        });

        channel.listen('.typing.started', (e) => {
            if (e.user_id === state.userId) return;
            if (state.activeConversationId === id) {
                showTypingIndicator(e.user_name);
            }
        });

        channel.listen('.messages.read', (e) => {
            if (e.reader_id === state.userId) return;
            // Update read receipts in current view
            if (state.activeConversationId === e.conversation_id) {
                e.message_ids.forEach(msgId => {
                    const row = dom.messagesContainer.querySelector(`[data-message-id="${msgId}"] .read-receipt`);
                    if (row) {
                        row.textContent = '✓✓';
                        row.classList.add('read');
                    }
                });
            }
        });
    }

    function showTypingIndicator(name) {
        dom.typingIndicator.querySelector('.typing-name').textContent = name;
        dom.typingIndicator.classList.remove('d-none');

        clearTimeout(state.typingTimers.get('indicator'));
        state.typingTimers.set('indicator', setTimeout(() => {
            dom.typingIndicator.classList.add('d-none');
        }, 3500));
    }

    // ===== Mark as Read =====
    async function markAsRead(conversationId) {
        try {
            await api('POST', `/conversations/${conversationId}/read`);
            // Update unread count in sidebar
            const conv = state.conversations.get(conversationId);
            if (conv) {
                conv.unread_count = 0;
                renderConversationList();
            }
        } catch (err) { /* silent */ }
    }

    // ===== New Conversation =====
    function openNewConversationModal() {
        const modal = new bootstrap.Modal(document.getElementById('newConversationModal'));
        modal.show();
    }

    async function createConversation() {
        const type = document.getElementById('new-conv-type').value;
        const participantId = document.getElementById('new-conv-participant').value;
        const bookingId = document.getElementById('new-conv-booking').value;
        const eventId = document.getElementById('new-conv-event').value;

        if (!participantId && type === 'direct') {
            alert('Please select a participant');
            return;
        }

        if (type === 'booking' && !bookingId) {
            alert('Please select a booking');
            return;
        }

        if (type === 'event' && !eventId) {
            alert('Please select an event');
            return;
        }

        // Build payload
        const payload = {
            type,
            participant_ids: participantId ? [parseInt(participantId)] : [],
        };

        if (type === 'booking' && bookingId) {
            payload.booking_id = parseInt(bookingId);
            // Auto-resolve participant from booking if not manually set
            if (!participantId) {
                const opt = document.getElementById('new-conv-booking').options[document.getElementById('new-conv-booking').selectedIndex];
                const currentUserId = parseInt(dom.app.dataset.userId);
                const clientId = parseInt(opt.dataset.client);
                const supplierId = parseInt(opt.dataset.supplier);
                const otherPartyId = (clientId === currentUserId) ? supplierId : clientId;
                payload.participant_ids = [otherPartyId];
            }
        }

        if (type === 'event' && eventId) {
            payload.event_id = parseInt(eventId);
        }

        try {
            const data = await api('POST', '/conversations', payload);

            state.conversations.set(data.id, data);
            renderConversationList();
            selectConversation(data.id);

            bootstrap.Modal.getInstance(document.getElementById('newConversationModal')).hide();
        } catch (err) {
            console.error('Failed to create conversation:', err);
            alert(err.message || 'Failed to create conversation');
        }
    }

    // ===== Helpers =====
    function updateConversationPreview(id, body, time) {
        const conv = state.conversations.get(id);
        if (conv) {
            conv.last_message_body = body;
            conv.last_message_at = time;
            if (id !== state.activeConversationId) {
                conv.unread_count = (conv.unread_count || 0) + 1;
            }
        }
        renderConversationList();
    }

    function goBackToList() {
        dom.app.classList.remove('chat-active');
    }

    function scrollToBottom() {
        requestAnimationFrame(() => {
            dom.messagesArea.scrollTop = dom.messagesArea.scrollHeight;
        });
    }

    function handleSearch() {
        const query = dom.searchInput.value.trim().toLowerCase();
        dom.conversationList.querySelectorAll('.conversation-item').forEach(el => {
            const name = el.querySelector('.conv-name')?.textContent.toLowerCase() || '';
            el.style.display = !query || name.includes(query) ? '' : 'none';
        });
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        const now = new Date();
        if (d.toDateString() === now.toDateString()) {
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
        return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        const now = new Date();
        if (d.toDateString() === now.toDateString()) return 'Today';
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
        return d.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function debounce(fn, delay) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // ===== Start =====
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
