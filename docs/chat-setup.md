# Chat System Setup Guide

## Install Dependencies

```bash
# 1. Install Laravel Reverb (WebSocket server)
composer require laravel/reverb

# 2. Install broadcasting scaffolding
php artisan install:broadcasting

# 3. Install frontend WebSocket packages
npm install laravel-echo pusher-js
```

## Environment Configuration

Add these to your `.env` file:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=khadija-app
REVERB_APP_KEY=khadija-app-key
REVERB_APP_SECRET=khadija-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## Run Migrations

```bash
php artisan migrate
```

This creates:
- `conversations` table
- `conversation_participants` table
- `message_reads` table
- `message_attachments` table
- Adds `conversation_id` column to existing `messages` table

## Storage Setup

```bash
# Create private storage directory for chat attachments
mkdir -p storage/app/private/chat-attachments

# Link storage (if not already done)
php artisan storage:link
```

## Running the App

You need 3 terminals:

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Reverb WebSocket server
php artisan reverb:start

# Terminal 3: Vite dev server
npm run dev
```

Or use the existing `composer run dev` which already runs concurrently — you just need to add Reverb to it.

## Verify

1. Log in as `client@example.com` (password: `password`)
2. Navigate to `/app/chat`
3. Click "+" to start a new conversation with the supplier
4. Send a message
5. Open a second browser/incognito → log in as `supplier@example.com`
6. Navigate to `/app/chat` — you should see the conversation with unread badge
7. Open the conversation — messages appear instantly via WebSocket

## Run Tests

```bash
php artisan test --filter=ConversationTest
php artisan test  # Run all tests including backward compatibility
```

## What Changed

### New Files
- `database/migrations/2026_03_10_*` — 5 migration files
- `app/Models/Conversation.php`, `MessageAttachment.php`, `MessageRead.php`
- `app/Http/Controllers/ConversationController.php`, `MessageAttachmentController.php`
- `app/Http/Controllers/Dashboard/ChatPageController.php`
- `app/Policies/ConversationPolicy.php`
- `app/Domain/Messaging/Events/MessageSent.php`, `TypingStarted.php`, `MessageReadBroadcast.php`
- `resources/views/dashboard/chat/index.blade.php`
- `public/js/chat.js`
- `routes/channels.php`
- `tests/Feature/ConversationTest.php`

### Modified Files
- `app/Models/Message.php` — added conversation, attachments, reads relationships
- `app/Models/User.php` — added conversations relationship
- `app/Models/Booking.php` — added conversation relationship
- `app/Providers/AppServiceProvider.php` — registered ConversationPolicy
- `routes/web.php` — added chat routes, redirected /app/messages → /app/chat
- `resources/views/layouts/dashboard.blade.php` — sidebar link changed to Chat
- `resources/js/bootstrap.js` — enabled Laravel Echo with Reverb

### Backward Compatibility
- Old REST API endpoints (`GET /messages`, `POST /messages`, `GET /messages/{id}`) still work
- Old `MessageInserted` event still dispatched alongside new `MessageSent`
- `/app/messages` redirects to `/app/chat`
