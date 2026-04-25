@extends('layouts.client')

@section('title', 'Account Settings')
@section('page-title', 'Account Settings')

@push('styles')
<style>
    /* ── Profile Layout ── */
    .pf-container { display: flex; gap: 24px; }
    .pf-sidebar { width: 260px; flex-shrink: 0; }
    .pf-main { flex: 1; min-width: 0; }

    @media (max-width: 768px) {
        .pf-container { flex-direction: column; }
        .pf-sidebar { width: 100%; }
    }

    /* ── Avatar Card ── */
    .pf-avatar-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 24px;
        text-align: center;
        margin-bottom: 16px;
    }
    .pf-avatar-wrap { position: relative; display: inline-block; margin-bottom: 16px; }
    .pf-avatar-img {
        width: 120px; height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--accent-blue);
    }
    .pf-avatar-upload {
        position: absolute; bottom: 4px; right: 4px;
        width: 34px; height: 34px;
        border-radius: 50%;
        background: var(--accent-blue);
        color: #fff;
        border: 2px solid var(--bg-secondary);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: var(--transition);
    }
    .pf-avatar-upload:hover { transform: scale(1.1); }
    .pf-avatar-upload input { display: none; }
    .pf-avatar-name { font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
    .pf-avatar-email { font-size: 13px; color: var(--text-muted); margin-bottom: 12px; }
    .pf-avatar-role {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: var(--accent-blue-soft);
        color: var(--accent-blue);
    }
    .pf-avatar-actions { margin-top: 12px; display: flex; gap: 8px; justify-content: center; }
    .pf-avatar-remove {
        font-size: 12px; color: var(--text-muted); cursor: pointer;
        background: none; border: none;
        transition: var(--transition);
    }
    .pf-avatar-remove:hover { color: #ef4444; }

    /* ── Sidebar Tabs ── */
    .pf-tabs {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        overflow: hidden;
    }
    .pf-tab-link {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 20px;
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 14px; font-weight: 500;
        border-bottom: 1px solid var(--border-color);
        transition: var(--transition);
    }
    .pf-tab-link:last-child { border-bottom: none; }
    .pf-tab-link:hover { background: rgba(99,102,241,0.05); color: var(--text-primary); }
    .pf-tab-link.active {
        background: rgba(99,102,241,0.1);
        color: var(--accent-blue);
        border-left: 3px solid var(--accent-blue);
    }
    .pf-tab-link svg { width: 18px; height: 18px; flex-shrink: 0; }

    /* ── Form Cards ── */
    .pf-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 24px;
        margin-bottom: 20px;
    }
    .pf-card-title {
        font-size: 18px; font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 4px;
    }
    .pf-card-desc {
        font-size: 13px; color: var(--text-muted);
        margin-bottom: 20px;
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
        color: var(--text-secondary);
        margin-bottom: 6px;
    }
    .pf-input, .pf-select, .pf-textarea {
        width: 100%;
        padding: 10px 14px;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        color: var(--text-primary);
        font-size: 14px;
        transition: var(--transition);
    }
    .pf-input:focus, .pf-select:focus, .pf-textarea:focus {
        outline: none;
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
    }
    .pf-textarea { resize: vertical; min-height: 100px; }
    .pf-select { appearance: auto; }
    .pf-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 24px;
        background: var(--accent-blue);
        color: #fff;
        border: none;
        border-radius: var(--radius-sm);
        font-size: 14px; font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }
    .pf-btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .pf-btn-outline {
        background: transparent;
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
    }
    .pf-btn-outline:hover { border-color: var(--accent-blue); color: var(--accent-blue); }
    .pf-error { color: #ef4444; font-size: 12px; margin-top: 4px; }
    .pf-success {
        padding: 12px 16px;
        background: rgba(16,185,129,0.1);
        border: 1px solid rgba(16,185,129,0.3);
        border-radius: var(--radius-sm);
        color: #10b981;
        font-size: 14px;
        margin-bottom: 16px;
    }

    /* ── Toggle Switch ── */
    .pf-toggle-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .pf-toggle-row:last-child { border-bottom: none; }
    .pf-toggle-info { flex: 1; }
    .pf-toggle-title { font-size: 14px; font-weight: 600; color: var(--text-primary); }
    .pf-toggle-desc { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .pf-switch {
        position: relative;
        width: 44px; height: 24px;
    }
    .pf-switch input { opacity: 0; width: 0; height: 0; }
    .pf-switch-slider {
        position: absolute; inset: 0;
        background: var(--border-color);
        border-radius: 12px;
        cursor: pointer;
        transition: var(--transition);
    }
    .pf-switch-slider::before {
        content: '';
        position: absolute;
        width: 18px; height: 18px;
        left: 3px; top: 3px;
        background: #fff;
        border-radius: 50%;
        transition: var(--transition);
    }
    .pf-switch input:checked + .pf-switch-slider { background: var(--accent-blue); }
    .pf-switch input:checked + .pf-switch-slider::before { transform: translateX(20px); }
</style>
@endpush

@section('content')
<div class="pf-container">
    {{-- ── Sidebar ── --}}
    <div class="pf-sidebar">
        {{-- Avatar Card --}}
        <div class="pf-avatar-card">
            <div class="pf-avatar-wrap">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="pf-avatar-img" id="avatarPreview">
                <form action="{{ route('client.profile.avatar') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
                    @csrf
                    <label class="pf-avatar-upload" title="Change Photo">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        <input type="file" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                    </label>
                </form>
            </div>
            <div class="pf-avatar-name">{{ $user->name }}</div>
            <div class="pf-avatar-email">{{ $user->email }}</div>
            <span class="pf-avatar-role">Client</span>
            @if($user->avatar)
                <div class="pf-avatar-actions">
                    <form action="{{ route('client.profile.avatar.remove') }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="pf-avatar-remove">Remove photo</button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Tab Nav --}}
        <div class="pf-tabs">
            <a href="{{ route('client.profile.index', ['tab' => 'general']) }}" class="pf-tab-link {{ $tab === 'general' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                General Info
            </a>
            <a href="{{ route('client.profile.index', ['tab' => 'company']) }}" class="pf-tab-link {{ $tab === 'company' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Company Info
            </a>
            <a href="{{ route('client.profile.index', ['tab' => 'social']) }}" class="pf-tab-link {{ $tab === 'social' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                Social Links
            </a>
            <a href="{{ route('client.profile.index', ['tab' => 'notifications']) }}" class="pf-tab-link {{ $tab === 'notifications' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                Notifications
            </a>
            <a href="{{ route('client.profile.index', ['tab' => 'password']) }}" class="pf-tab-link {{ $tab === 'password' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Change Password
            </a>
            <a href="{{ route('client.profile.index', ['tab' => 'modes']) }}" class="pf-tab-link {{ $tab === 'modes' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                Account Modes
            </a>
            <a href="{{ route('client.profile.index', ['tab' => 'danger']) }}" class="pf-tab-link {{ $tab === 'danger' ? 'active' : '' }}" style="color:#f87171;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Danger Zone
            </a>
        </div>
    </div>

    {{-- ── Main Content ── --}}
    <div class="pf-main">
        @if(session('status'))
            <div class="pf-success">{{ session('status') }}</div>
        @endif

        {{-- General Info --}}
        @if($tab === 'general')
        <div class="pf-card">
            <div class="pf-card-title">General Information</div>
            <div class="pf-card-desc">Update your personal details and contact information.</div>

            <form action="{{ route('client.profile.update.general') }}" method="POST">
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
                        <input type="text" name="phone" class="pf-input" value="{{ old('phone', $user->phone) }}" placeholder="+1 (555) 123-4567">
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
                        <textarea name="bio" class="pf-textarea" placeholder="Tell us about yourself...">{{ old('bio', $profile->bio) }}</textarea>
                    </div>

                    <div class="pf-form-full" style="border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 4px;">
                        <div class="pf-card-title" style="font-size:15px;">Address</div>
                    </div>
                    <div class="pf-form-full">
                        <label class="pf-label">Street Address</label>
                        <input type="text" name="address" class="pf-input" value="{{ old('address', $profile->address) }}">
                    </div>
                    <div>
                        <label class="pf-label">City</label>
                        <input type="text" name="city" class="pf-input" value="{{ old('city', $profile->city) }}">
                    </div>
                    <div>
                        <label class="pf-label">State / Province</label>
                        <input type="text" name="state" class="pf-input" value="{{ old('state', $profile->state) }}">
                    </div>
                    <div>
                        <label class="pf-label">Country</label>
                        <input type="text" name="country" class="pf-input" value="{{ old('country', $profile->country) }}">
                    </div>
                    <div>
                        <label class="pf-label">ZIP / Postal Code</label>
                        <input type="text" name="zip_code" class="pf-input" value="{{ old('zip_code', $profile->zip_code) }}">
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 12px;">
                    <button type="submit" class="pf-btn">Save Changes</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Company Info --}}
        @if($tab === 'company')
        <div class="pf-card">
            <div class="pf-card-title">Company Information</div>
            <div class="pf-card-desc">Your business details for event bookings and invoicing.</div>

            <form action="{{ route('client.profile.update.company') }}" method="POST">
                @csrf @method('PATCH')
                <div class="pf-form-grid">
                    <div>
                        <label class="pf-label">Company Name</label>
                        <input type="text" name="company_name" class="pf-input" value="{{ old('company_name', $profile->company_name) }}" placeholder="Your Company LLC">
                    </div>
                    <div>
                        <label class="pf-label">Industry</label>
                        <input type="text" name="industry" class="pf-input" value="{{ old('industry', $profile->industry) }}" placeholder="e.g. Technology, Healthcare">
                    </div>
                    <div class="pf-form-full">
                        <label class="pf-label">Company Website</label>
                        <input type="url" name="company_website" class="pf-input" value="{{ old('company_website', $profile->company_website) }}" placeholder="https://yourcompany.com">
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="pf-btn">Save Company Info</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Social Links --}}
        @if($tab === 'social')
        <div class="pf-card">
            <div class="pf-card-title">Social Links</div>
            <div class="pf-card-desc">Connect your social profiles to build trust with professionals.</div>

            <form action="{{ route('client.profile.update.social') }}" method="POST">
                @csrf @method('PATCH')
                <div class="pf-form-grid">
                    <div>
                        <label class="pf-label">LinkedIn</label>
                        <input type="url" name="linkedin" class="pf-input" value="{{ old('linkedin', $profile->social_links['linkedin'] ?? '') }}" placeholder="https://linkedin.com/in/yourname">
                    </div>
                    <div>
                        <label class="pf-label">Twitter / X</label>
                        <input type="url" name="twitter" class="pf-input" value="{{ old('twitter', $profile->social_links['twitter'] ?? '') }}" placeholder="https://twitter.com/yourhandle">
                    </div>
                    <div>
                        <label class="pf-label">Facebook</label>
                        <input type="url" name="facebook" class="pf-input" value="{{ old('facebook', $profile->social_links['facebook'] ?? '') }}" placeholder="https://facebook.com/yourpage">
                    </div>
                    <div>
                        <label class="pf-label">Instagram</label>
                        <input type="url" name="instagram" class="pf-input" value="{{ old('instagram', $profile->social_links['instagram'] ?? '') }}" placeholder="https://instagram.com/yourhandle">
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="pf-btn">Save Social Links</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Notifications --}}
        @if($tab === 'notifications')
        <div class="pf-card">
            <div class="pf-card-title">Notification Preferences</div>
            <div class="pf-card-desc">Choose what email notifications you'd like to receive.</div>

            <form action="{{ route('client.profile.update.notifications') }}" method="POST">
                @csrf @method('PATCH')
                <div class="pf-toggle-row">
                    <div class="pf-toggle-info">
                        <div class="pf-toggle-title">Booking Updates</div>
                        <div class="pf-toggle-desc">Get notified about new proposals, confirmations, and status changes.</div>
                    </div>
                    <label class="pf-switch">
                        <input type="checkbox" name="notify_email_bookings" value="1" {{ $profile->notify_email_bookings ? 'checked' : '' }}>
                        <span class="pf-switch-slider"></span>
                    </label>
                </div>
                <div class="pf-toggle-row">
                    <div class="pf-toggle-info">
                        <div class="pf-toggle-title">New Messages</div>
                        <div class="pf-toggle-desc">Receive email when someone sends you a message.</div>
                    </div>
                    <label class="pf-switch">
                        <input type="checkbox" name="notify_email_messages" value="1" {{ $profile->notify_email_messages ? 'checked' : '' }}>
                        <span class="pf-switch-slider"></span>
                    </label>
                </div>
                <div class="pf-toggle-row">
                    <div class="pf-toggle-info">
                        <div class="pf-toggle-title">Event Reminders</div>
                        <div class="pf-toggle-desc">Get reminders about upcoming events and deadlines.</div>
                    </div>
                    <label class="pf-switch">
                        <input type="checkbox" name="notify_email_events" value="1" {{ $profile->notify_email_events ? 'checked' : '' }}>
                        <span class="pf-switch-slider"></span>
                    </label>
                </div>
                <div class="pf-toggle-row">
                    <div class="pf-toggle-info">
                        <div class="pf-toggle-title">Marketing & Offers</div>
                        <div class="pf-toggle-desc">Receive promotions, tips, and platform updates.</div>
                    </div>
                    <label class="pf-switch">
                        <input type="checkbox" name="notify_email_marketing" value="1" {{ $profile->notify_email_marketing ? 'checked' : '' }}>
                        <span class="pf-switch-slider"></span>
                    </label>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="pf-btn">Save Preferences</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Change Password --}}
        @if($tab === 'password')
        <div class="pf-card">
            <div class="pf-card-title">Change Password</div>
            <div class="pf-card-desc">Ensure your account is secure with a strong password.</div>

            <form action="{{ route('client.profile.update.password') }}" method="POST">
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
                <div style="margin-top: 20px;">
                    <button type="submit" class="pf-btn">Update Password</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Account Modes --}}
        @if($tab === 'modes')
        @php
            $hasClient    = $user->hasRole(\App\Domain\Auth\Enums\RoleName::CLIENT->value);
            $hasSupplier  = $user->hasRole(\App\Domain\Auth\Enums\RoleName::SUPPLIER->value);
            $activeMode   = $user->activeRole();
        @endphp
        <div class="pf-card">
            <div class="pf-card-title">Account Modes</div>
            <div class="pf-card-desc">
                Enable dual-mode on your account — act as a Client to post events, or as a Professional to offer your services.
                You can switch between modes anytime from the top navigation bar.
            </div>

            @if(session('error'))
                <div style="padding:12px 16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#ef4444;border-radius:var(--radius-sm);font-size:13px;margin-bottom:16px;">{{ session('error') }}</div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                {{-- Client mode card --}}
                <div style="background:{{ $hasClient ? 'rgba(59,130,246,0.06)' : 'var(--bg-primary)' }};border:1.5px solid {{ $hasClient ? 'rgba(59,130,246,0.3)' : 'var(--border-color)' }};border-radius:var(--radius);padding:22px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#3b82f6,#06b6d4);display:flex;align-items:center;justify-content:center;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <div>
                                <div style="font-size:15px;font-weight:700;color:var(--text-primary);">Client Mode</div>
                                <div style="font-size:12px;color:var(--text-muted);">Post events, hire professionals</div>
                            </div>
                        </div>
                        @if($activeMode === 'client')
                            <span style="padding:3px 10px;background:rgba(16,185,129,0.15);color:#10b981;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;">Active</span>
                        @endif
                    </div>
                    @if($hasClient)
                        <div style="font-size:12.5px;color:var(--text-secondary);padding:10px 0;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3" style="display:inline-block;vertical-align:middle;margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg>
                            Enabled
                        </div>
                        @if($activeMode !== 'client')
                            <form action="{{ route('role.switch') }}" method="POST">
                                @csrf
                                <input type="hidden" name="role" value="client">
                                <button type="submit" class="pf-btn" style="width:100%;">Switch to Client Mode</button>
                            </form>
                        @endif
                    @else
                        <form action="{{ route('role.enable') }}" method="POST">
                            @csrf
                            <input type="hidden" name="role" value="client">
                            <button type="submit" class="pf-btn" style="width:100%;background:linear-gradient(135deg,#3b82f6,#06b6d4);">Enable Client Mode</button>
                        </form>
                    @endif
                </div>

                {{-- Professional mode card --}}
                <div style="background:{{ $hasSupplier ? 'rgba(16,185,129,0.06)' : 'var(--bg-primary)' }};border:1.5px solid {{ $hasSupplier ? 'rgba(16,185,129,0.3)' : 'var(--border-color)' }};border-radius:var(--radius);padding:22px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            </div>
                            <div>
                                <div style="font-size:15px;font-weight:700;color:var(--text-primary);">Professional Mode</div>
                                <div style="font-size:12px;color:var(--text-muted);">Offer services, get hired</div>
                            </div>
                        </div>
                        @if($activeMode === 'supplier')
                            <span style="padding:3px 10px;background:rgba(16,185,129,0.15);color:#10b981;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;">Active</span>
                        @endif
                    </div>
                    @if($hasSupplier)
                        <div style="font-size:12.5px;color:var(--text-secondary);padding:10px 0;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3" style="display:inline-block;vertical-align:middle;margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg>
                            Enabled
                        </div>
                        @if($activeMode !== 'supplier')
                            <form action="{{ route('role.switch') }}" method="POST">
                                @csrf
                                <input type="hidden" name="role" value="supplier">
                                <button type="submit" class="pf-btn" style="width:100%;">Switch to Professional Mode</button>
                            </form>
                        @endif
                    @else
                        <button type="button" class="pf-btn" data-role-enable="supplier"
                                style="width:100%;background:linear-gradient(135deg,#10b981,#059669);">
                            Become a Professional
                        </button>
                    @endif
                </div>
            </div>

            <div style="margin-top:20px;padding:14px 18px;background:rgba(99,102,241,0.06);border:1px solid rgba(99,102,241,0.2);border-radius:var(--radius-sm);">
                <div style="font-size:12.5px;color:var(--text-secondary);line-height:1.6;">
                    <strong style="color:var(--text-primary);">💡 How it works:</strong>
                    Once you enable both modes, a quick-switch button appears in your top navigation bar.
                    Your data, messages, and bookings stay separate between modes — just like Freelancer or Upwork.
                </div>
            </div>
        </div>
        @endif

        {{-- Danger Zone --}}
        @if($tab === 'danger')
        <div class="pf-card" style="border-color: rgba(239,68,68,0.3);">
            <div class="pf-card-title" style="color:#ef4444;">Delete Account</div>
            <div class="pf-card-desc">
                Once you submit a deletion request, your account will be locked and scheduled for permanent removal in
                <strong style="color:var(--text-primary);">60 days</strong>. You can cancel the request anytime during the grace period by
                logging back in.
            </div>

            <div style="background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2); border-radius: 10px; padding: 16px 20px; margin-bottom: 24px;">
                <div style="font-size:13px; font-weight:600; color:#f87171; margin-bottom:8px;">What happens next?</div>
                <ul style="font-size:12.5px; color:var(--text-secondary); line-height:1.8; padding-left:18px; margin:0;">
                    <li>Your account is immediately locked — no further actions possible.</li>
                    <li>You will be signed out on your next request.</li>
                    <li>You have 60 days to restore the account by simply logging in.</li>
                    <li>After 60 days, your personal data is permanently anonymized.</li>
                    <li>Bookings and transaction records are kept for legal/audit purposes but anonymized.</li>
                </ul>
            </div>

            @if(session('error'))
                <div style="padding:12px 16px; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#ef4444; border-radius:var(--radius-sm); font-size:13px; margin-bottom:16px;">{{ session('error') }}</div>
            @endif

            <form action="{{ route('account.deletion.request') }}" method="POST" onsubmit="return confirm('Are you absolutely sure? This will schedule your account for deletion.');">
                @csrf
                <div class="pf-form-grid">
                    <div class="pf-form-full">
                        <label class="pf-label">Reason for leaving (optional)</label>
                        <textarea name="reason" class="pf-textarea" placeholder="Help us improve — why are you deleting your account?" maxlength="1000">{{ old('reason') }}</textarea>
                    </div>
                    <div class="pf-form-full">
                        <label class="pf-label">Current Password *</label>
                        <input type="password" name="current_password" class="pf-input" required>
                        @error('current_password') <div class="pf-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="pf-form-full">
                        <label class="pf-label">Type <strong style="color:#ef4444;">DELETE</strong> to confirm *</label>
                        <input type="text" name="confirm_text" class="pf-input" required placeholder="DELETE" autocomplete="off">
                        @error('confirm_text') <div class="pf-error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="pf-btn" style="background:#ef4444;">
                        Request Account Deletion
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
