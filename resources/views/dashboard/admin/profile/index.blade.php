@extends('layouts.dashboard')

@section('title', 'My Profile')

@section('content')
<style>
    .pf-container { display: flex; gap: 24px; max-width: 960px; }
    .pf-sidebar { width: 260px; flex-shrink: 0; }
    .pf-main { flex: 1; min-width: 0; }
    @media (max-width: 768px) {
        .pf-container { flex-direction: column; }
        .pf-sidebar { width: 100%; }
    }

    /* ── Avatar Card ── */
    .pf-avatar-card {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        padding: 28px 24px;
        text-align: center;
        margin-bottom: 16px;
    }
    .pf-avatar-wrap { position: relative; display: inline-block; margin-bottom: 16px; }
    .pf-avatar-img {
        width: 120px; height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #6366f1;
        box-shadow: 0 4px 15px rgba(99,102,241,0.2);
    }
    .pf-avatar-upload {
        position: absolute; bottom: 4px; right: 4px;
        width: 36px; height: 36px;
        border-radius: 50%;
        background: #6366f1;
        color: #fff;
        border: 3px solid var(--bs-body-bg);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .pf-avatar-upload:hover { transform: scale(1.1); background: #4f46e5; }
    .pf-avatar-upload input { display: none; }
    .pf-avatar-name { font-size: 18px; font-weight: 700; color: var(--bs-body-color); margin-bottom: 4px; }
    .pf-avatar-email { font-size: 13px; color: var(--bs-secondary-color); margin-bottom: 6px; }
    .pf-avatar-phone { font-size: 13px; color: var(--bs-secondary-color); margin-bottom: 12px; }
    .pf-avatar-role {
        display: inline-block;
        padding: 4px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(239,68,68,0.1);
        color: #ef4444;
    }
    .pf-avatar-actions { margin-top: 12px; }
    .pf-avatar-remove {
        font-size: 12px; color: var(--bs-secondary-color); cursor: pointer;
        background: none; border: none;
        transition: all 0.2s;
    }
    .pf-avatar-remove:hover { color: #ef4444; }
    .pf-member-since {
        font-size: 11px; color: var(--bs-secondary-color);
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--bs-border-color);
    }

    /* ── Sidebar Tabs ── */
    .pf-tabs {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        overflow: hidden;
    }
    .pf-tab-link {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 20px;
        color: var(--bs-secondary-color);
        text-decoration: none;
        font-size: 14px; font-weight: 500;
        border-bottom: 1px solid var(--bs-border-color);
        transition: all 0.2s;
    }
    .pf-tab-link:last-child { border-bottom: none; }
    .pf-tab-link:hover { background: rgba(99,102,241,0.05); color: var(--bs-body-color); }
    .pf-tab-link.active {
        background: rgba(99,102,241,0.1);
        color: #6366f1;
        border-left: 3px solid #6366f1;
    }
    .pf-tab-link svg { width: 18px; height: 18px; flex-shrink: 0; }

    /* ── Form Cards ── */
    .pf-card {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        padding: 28px;
        margin-bottom: 20px;
    }
    .pf-card-title {
        font-size: 18px; font-weight: 700;
        color: var(--bs-body-color);
        margin-bottom: 4px;
    }
    .pf-card-desc {
        font-size: 13px; color: var(--bs-secondary-color);
        margin-bottom: 24px;
    }
    .pf-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    @media (max-width: 640px) { .pf-form-grid { grid-template-columns: 1fr; } }
    .pf-form-full { grid-column: 1 / -1; }
    .pf-label {
        display: block;
        font-size: 13px; font-weight: 600;
        color: var(--bs-secondary-color);
        margin-bottom: 6px;
    }
    .pf-input, .pf-select, .pf-textarea {
        width: 100%;
        padding: 10px 14px;
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        color: var(--bs-body-color);
        font-size: 14px;
        transition: all 0.2s;
    }
    .pf-input:focus, .pf-select:focus, .pf-textarea:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
    }
    .pf-textarea { resize: vertical; min-height: 100px; }
    .pf-select { appearance: auto; }
    .pf-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 24px;
        background: #6366f1;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 14px; font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .pf-btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .pf-error { color: #ef4444; font-size: 12px; margin-top: 4px; }
    .pf-success {
        padding: 12px 16px;
        background: rgba(16,185,129,0.1);
        border: 1px solid rgba(16,185,129,0.3);
        border-radius: 8px;
        color: #10b981;
        font-size: 14px;
        margin-bottom: 16px;
        display: flex; align-items: center; gap: 8px;
    }

    /* ── Section Divider ── */
    .pf-section-divider {
        border-top: 1px solid var(--bs-border-color);
        padding-top: 16px;
        margin-top: 8px;
    }
    .pf-section-title {
        font-size: 15px; font-weight: 600;
        color: var(--bs-body-color);
        margin-bottom: 16px;
    }

    /* ── Toggle Switch ── */
    .pf-toggle-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 0;
        border-bottom: 1px solid var(--bs-border-color);
    }
    .pf-toggle-row:last-child { border-bottom: none; }
    .pf-toggle-info { flex: 1; padding-right: 16px; }
    .pf-toggle-title { font-size: 14px; font-weight: 600; color: var(--bs-body-color); }
    .pf-toggle-desc { font-size: 12px; color: var(--bs-secondary-color); margin-top: 2px; }
    .pf-switch { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
    .pf-switch input { opacity: 0; width: 0; height: 0; }
    .pf-switch-slider {
        position: absolute; inset: 0;
        background: var(--bs-border-color);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .pf-switch-slider::before {
        content: '';
        position: absolute;
        width: 18px; height: 18px;
        left: 3px; top: 3px;
        background: #fff;
        border-radius: 50%;
        transition: all 0.2s;
    }
    .pf-switch input:checked + .pf-switch-slider { background: #6366f1; }
    .pf-switch input:checked + .pf-switch-slider::before { transform: translateX(20px); }

    /* ── Social Input Icons ── */
    .pf-social-input-wrap {
        position: relative;
    }
    .pf-social-input-wrap .pf-input {
        padding-left: 42px;
    }
    .pf-social-icon {
        position: absolute;
        left: 14px; top: 50%;
        transform: translateY(-50%);
        width: 18px; height: 18px;
        color: var(--bs-secondary-color);
    }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <h4 class="mb-0">My Profile</h4>
</div>

<div class="pf-container">
    {{-- ── Sidebar ── --}}
    <div class="pf-sidebar">
        {{-- Avatar Card --}}
        <div class="pf-avatar-card">
            <div class="pf-avatar-wrap">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="pf-avatar-img">
                <form action="{{ route('app.admin.profile.avatar') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
                    @csrf
                    <label class="pf-avatar-upload" title="Change Photo">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        <input type="file" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                    </label>
                </form>
            </div>
            <div class="pf-avatar-name">{{ $user->name }}</div>
            <div class="pf-avatar-email">{{ $user->email }}</div>
            @if($user->phone)
                <div class="pf-avatar-phone">{{ $user->phone }}</div>
            @endif
            <span class="pf-avatar-role">Administrator</span>
            @if($user->avatar)
                <div class="pf-avatar-actions">
                    <form action="{{ route('app.admin.profile.avatar.remove') }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="pf-avatar-remove">Remove photo</button>
                    </form>
                </div>
            @endif
            <div class="pf-member-since">Member since {{ $user->created_at->format('M Y') }}</div>
        </div>

        {{-- Tab Nav --}}
        <div class="pf-tabs">
            <a href="{{ route('app.admin.profile.index', ['tab' => 'general']) }}" class="pf-tab-link {{ $tab === 'general' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                General Info
            </a>
            <a href="{{ route('app.admin.profile.index', ['tab' => 'social']) }}" class="pf-tab-link {{ $tab === 'social' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                Social Links
            </a>
            <a href="{{ route('app.admin.profile.index', ['tab' => 'notifications']) }}" class="pf-tab-link {{ $tab === 'notifications' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                Notifications
            </a>
            <a href="{{ route('app.admin.profile.index', ['tab' => 'password']) }}" class="pf-tab-link {{ $tab === 'password' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Change Password
            </a>
        </div>
    </div>

    {{-- ── Main Content ── --}}
    <div class="pf-main">
        @if(session('status'))
            <div class="pf-success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                {{ session('status') }}
            </div>
        @endif

        {{-- ═══════ General Info Tab ═══════ --}}
        @if($tab === 'general')
        <div class="pf-card">
            <div class="pf-card-title">General Information</div>
            <div class="pf-card-desc">Update your personal details and contact information.</div>

            <form action="{{ route('app.admin.profile.update.general') }}" method="POST">
                @csrf @method('PATCH')
                <div class="pf-form-grid">
                    <div>
                        <label class="pf-label">Full Name *</label>
                        <input type="text" name="name" class="pf-input" value="{{ old('name', $user->name) }}" required>
                        @error('name') <div class="pf-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="pf-label">Email Address *</label>
                        <input type="email" name="email" class="pf-input" value="{{ old('email', $user->email) }}" required>
                        @error('email') <div class="pf-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="pf-label">Phone Number</label>
                        <input type="text" name="phone" class="pf-input" value="{{ old('phone', $user->phone) }}" placeholder="+92 300 1234567">
                        @error('phone') <div class="pf-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="pf-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="pf-input" value="{{ old('date_of_birth', $profile->date_of_birth?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label class="pf-label">Gender</label>
                        <select name="gender" class="pf-select">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $profile->gender) === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $profile->gender) === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $profile->gender) === 'other' ? 'selected' : '' }}>Other</option>
                            <option value="prefer_not_to_say" {{ old('gender', $profile->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                        </select>
                    </div>
                    <div>
                        <label class="pf-label">Website</label>
                        <input type="url" name="website" class="pf-input" value="{{ old('website', $profile->website) }}" placeholder="https://yourwebsite.com">
                    </div>
                    <div class="pf-form-full">
                        <label class="pf-label">Bio</label>
                        <textarea name="bio" class="pf-textarea" placeholder="Tell us a bit about yourself...">{{ old('bio', $profile->bio) }}</textarea>
                    </div>

                    {{-- Address Section --}}
                    <div class="pf-form-full pf-section-divider">
                        <div class="pf-section-title">Address</div>
                    </div>
                    <div class="pf-form-full">
                        <label class="pf-label">Street Address</label>
                        <input type="text" name="address" class="pf-input" value="{{ old('address', $profile->address) }}" placeholder="123 Main Street">
                    </div>
                    <div>
                        <label class="pf-label">City</label>
                        <input type="text" name="city" class="pf-input" value="{{ old('city', $profile->city) }}" placeholder="Lahore">
                    </div>
                    <div>
                        <label class="pf-label">State / Province</label>
                        <input type="text" name="state" class="pf-input" value="{{ old('state', $profile->state) }}" placeholder="Punjab">
                    </div>
                    <div>
                        <label class="pf-label">Country</label>
                        <input type="text" name="country" class="pf-input" value="{{ old('country', $profile->country) }}" placeholder="Pakistan">
                    </div>
                    <div>
                        <label class="pf-label">ZIP / Postal Code</label>
                        <input type="text" name="zip_code" class="pf-input" value="{{ old('zip_code', $profile->zip_code) }}" placeholder="54000">
                    </div>
                </div>
                <div style="margin-top: 24px;">
                    <button type="submit" class="pf-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- ═══════ Social Links Tab ═══════ --}}
        @if($tab === 'social')
        <div class="pf-card">
            <div class="pf-card-title">Social Links</div>
            <div class="pf-card-desc">Connect your social profiles for team visibility.</div>

            <form action="{{ route('app.admin.profile.update.social') }}" method="POST">
                @csrf @method('PATCH')
                <div class="pf-form-grid">
                    <div>
                        <label class="pf-label">LinkedIn</label>
                        <div class="pf-social-input-wrap">
                            <svg class="pf-social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                            <input type="url" name="linkedin" class="pf-input" value="{{ old('linkedin', $profile->social_links['linkedin'] ?? '') }}" placeholder="https://linkedin.com/in/yourname">
                        </div>
                    </div>
                    <div>
                        <label class="pf-label">Twitter / X</label>
                        <div class="pf-social-input-wrap">
                            <svg class="pf-social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
                            <input type="url" name="twitter" class="pf-input" value="{{ old('twitter', $profile->social_links['twitter'] ?? '') }}" placeholder="https://twitter.com/yourhandle">
                        </div>
                    </div>
                    <div>
                        <label class="pf-label">Facebook</label>
                        <div class="pf-social-input-wrap">
                            <svg class="pf-social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                            <input type="url" name="facebook" class="pf-input" value="{{ old('facebook', $profile->social_links['facebook'] ?? '') }}" placeholder="https://facebook.com/yourpage">
                        </div>
                    </div>
                    <div>
                        <label class="pf-label">Instagram</label>
                        <div class="pf-social-input-wrap">
                            <svg class="pf-social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                            <input type="url" name="instagram" class="pf-input" value="{{ old('instagram', $profile->social_links['instagram'] ?? '') }}" placeholder="https://instagram.com/yourhandle">
                        </div>
                    </div>
                </div>
                <div style="margin-top: 24px;">
                    <button type="submit" class="pf-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                        Save Social Links
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- ═══════ Notifications Tab ═══════ --}}
        @if($tab === 'notifications')
        <div class="pf-card">
            <div class="pf-card-title">Notification Preferences</div>
            <div class="pf-card-desc">Choose what email notifications you'd like to receive.</div>

            <form action="{{ route('app.admin.profile.update.notifications') }}" method="POST">
                @csrf @method('PATCH')
                <div class="pf-toggle-row">
                    <div class="pf-toggle-info">
                        <div class="pf-toggle-title">Booking Updates</div>
                        <div class="pf-toggle-desc">Get notified about new bookings, status changes, and cancellations.</div>
                    </div>
                    <label class="pf-switch">
                        <input type="checkbox" name="notify_email_bookings" value="1" {{ $profile->notify_email_bookings ? 'checked' : '' }}>
                        <span class="pf-switch-slider"></span>
                    </label>
                </div>
                <div class="pf-toggle-row">
                    <div class="pf-toggle-info">
                        <div class="pf-toggle-title">New Messages</div>
                        <div class="pf-toggle-desc">Receive email notifications for new chat messages.</div>
                    </div>
                    <label class="pf-switch">
                        <input type="checkbox" name="notify_email_messages" value="1" {{ $profile->notify_email_messages ? 'checked' : '' }}>
                        <span class="pf-switch-slider"></span>
                    </label>
                </div>
                <div class="pf-toggle-row">
                    <div class="pf-toggle-info">
                        <div class="pf-toggle-title">Event Alerts</div>
                        <div class="pf-toggle-desc">Get alerts when new events are created or important events are upcoming.</div>
                    </div>
                    <label class="pf-switch">
                        <input type="checkbox" name="notify_email_events" value="1" {{ $profile->notify_email_events ? 'checked' : '' }}>
                        <span class="pf-switch-slider"></span>
                    </label>
                </div>
                <div class="pf-toggle-row">
                    <div class="pf-toggle-info">
                        <div class="pf-toggle-title">System & Marketing</div>
                        <div class="pf-toggle-desc">Receive system announcements, feature updates, and promotional content.</div>
                    </div>
                    <label class="pf-switch">
                        <input type="checkbox" name="notify_email_marketing" value="1" {{ $profile->notify_email_marketing ? 'checked' : '' }}>
                        <span class="pf-switch-slider"></span>
                    </label>
                </div>
                <div style="margin-top: 24px;">
                    <button type="submit" class="pf-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                        Save Preferences
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- ═══════ Change Password Tab ═══════ --}}
        @if($tab === 'password')
        <div class="pf-card">
            <div class="pf-card-title">Change Password</div>
            <div class="pf-card-desc">Ensure your admin account is secure with a strong password.</div>

            <form action="{{ route('app.admin.profile.update.password') }}" method="POST">
                @csrf @method('PATCH')
                <div class="pf-form-grid">
                    <div class="pf-form-full">
                        <label class="pf-label">Current Password *</label>
                        <input type="password" name="current_password" class="pf-input" required>
                        @error('current_password') <div class="pf-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="pf-label">New Password *</label>
                        <input type="password" name="password" class="pf-input" required minlength="8">
                        @error('password') <div class="pf-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="pf-label">Confirm New Password *</label>
                        <input type="password" name="password_confirmation" class="pf-input" required>
                    </div>
                </div>
                <p style="font-size: 12px; color: var(--bs-secondary-color); margin-top: 12px;">
                    Password must be at least 8 characters long. Use a mix of letters, numbers, and symbols for better security.
                </p>
                <div style="margin-top: 20px;">
                    <button type="submit" class="pf-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Update Password
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
