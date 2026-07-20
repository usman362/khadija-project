@extends('layouts.client')

@section('title', 'Notification Preferences')
@section('page-title', 'Notification Preferences')
@section('page-subtitle', 'Control what you are notified about — and through which channels (email, push, SMS).')

@push('styles')
<style>
    /* ── Notification Preferences (dedicated page) ── */
    .np-wrap { max-width: 760px; margin: 0 auto; }
    .np-back {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 13px; font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 16px;
        transition: var(--transition);
    }
    .np-back:hover { color: #f97316; }
    .np-back svg { width: 16px; height: 16px; }

    .np-success {
        padding: 12px 16px;
        background: rgba(16,185,129,0.1);
        border: 1px solid rgba(16,185,129,0.3);
        border-radius: var(--radius-sm);
        color: #10b981;
        font-size: 14px;
        margin-bottom: 16px;
    }

    .np-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 24px;
        margin-bottom: 20px;
    }
    .np-card-title { font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
    .np-card-desc { font-size: 13px; color: var(--text-muted); margin-bottom: 4px; }
    .np-section-label {
        font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        color: var(--text-muted);
        margin: 24px 0 4px;
    }

    .np-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: 16px;
        padding: 14px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .np-row:last-of-type { border-bottom: none; }
    .np-info { flex: 1; min-width: 0; }
    .np-title { font-size: 14px; font-weight: 600; color: var(--text-primary); }
    .np-desc { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    .np-switch { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
    .np-switch input { opacity: 0; width: 0; height: 0; }
    .np-slider {
        position: absolute; inset: 0;
        background: var(--border-color);
        border-radius: 12px; cursor: pointer;
        transition: var(--transition);
    }
    .np-slider::before {
        content: ''; position: absolute;
        width: 18px; height: 18px; left: 3px; top: 3px;
        background: #fff; border-radius: 50%;
        transition: var(--transition);
    }
    .np-switch input:checked + .np-slider { background: #f97316; }
    .np-switch input:checked + .np-slider::before { transform: translateX(20px); }

    .np-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 24px;
        background: #f97316; color: #fff;
        border: none; border-radius: var(--radius-sm);
        font-size: 14px; font-weight: 600; cursor: pointer;
        transition: var(--transition);
    }
    .np-btn:hover { opacity: 0.9; transform: translateY(-1px); }
</style>
@endpush

@section('content')
<div class="np-wrap">
    <a href="{{ route('client.profile.index') }}" class="np-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Account Settings
    </a>

    @if(session('status'))
        <div class="np-success">{{ session('status') }}</div>
    @endif

    <form action="{{ route('client.profile.update.notifications') }}" method="POST">
        @csrf @method('PATCH')

        <div class="np-card">
            <div class="np-card-title">Email Notifications</div>
            <div class="np-card-desc">Choose which emails GigResource sends you.</div>

            <div class="np-row">
                <div class="np-info">
                    <div class="np-title">Booking Updates</div>
                    <div class="np-desc">New proposals, confirmations, and status changes.</div>
                </div>
                <label class="np-switch">
                    <input type="checkbox" name="notify_email_bookings" value="1" {{ $profile->notify_email_bookings ? 'checked' : '' }}>
                    <span class="np-slider"></span>
                </label>
            </div>
            <div class="np-row">
                <div class="np-info">
                    <div class="np-title">New Messages</div>
                    <div class="np-desc">Email when someone sends you a message.</div>
                </div>
                <label class="np-switch">
                    <input type="checkbox" name="notify_email_messages" value="1" {{ $profile->notify_email_messages ? 'checked' : '' }}>
                    <span class="np-slider"></span>
                </label>
            </div>
            <div class="np-row">
                <div class="np-info">
                    <div class="np-title">Event Reminders</div>
                    <div class="np-desc">Reminders about upcoming events and deadlines.</div>
                </div>
                <label class="np-switch">
                    <input type="checkbox" name="notify_email_events" value="1" {{ $profile->notify_email_events ? 'checked' : '' }}>
                    <span class="np-slider"></span>
                </label>
            </div>
            <div class="np-row">
                <div class="np-info">
                    <div class="np-title">Marketing &amp; Offers</div>
                    <div class="np-desc">Promotions, tips, and platform updates.</div>
                </div>
                <label class="np-switch">
                    <input type="checkbox" name="notify_email_marketing" value="1" {{ $profile->notify_email_marketing ? 'checked' : '' }}>
                    <span class="np-slider"></span>
                </label>
            </div>
        </div>

        <div class="np-card">
            <div class="np-card-title">Channels</div>
            <div class="np-card-desc">Beyond email, how else should we reach you?</div>

            <div class="np-row">
                <div class="np-info">
                    <div class="np-title">Push Notifications</div>
                    <div class="np-desc">In-browser push alerts for time-sensitive updates.</div>
                </div>
                <label class="np-switch">
                    <input type="checkbox" name="notify_push" value="1" {{ $profile->notify_push ? 'checked' : '' }}>
                    <span class="np-slider"></span>
                </label>
            </div>
            <div class="np-row">
                <div class="np-info">
                    <div class="np-title">SMS Notifications</div>
                    <div class="np-desc">Text alerts for urgent items (carrier rates may apply).</div>
                </div>
                <label class="np-switch">
                    <input type="checkbox" name="notify_sms" value="1" {{ $profile->notify_sms ? 'checked' : '' }}>
                    <span class="np-slider"></span>
                </label>
            </div>
        </div>

        <button type="submit" class="np-btn">Save Preferences</button>
    </form>
</div>
@endsection
