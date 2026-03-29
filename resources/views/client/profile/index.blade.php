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
    </div>
</div>
@endsection
