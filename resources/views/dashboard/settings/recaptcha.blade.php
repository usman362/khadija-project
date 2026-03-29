@extends('layouts.dashboard')

@section('title', 'reCAPTCHA Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <h4 class="mb-0">reCAPTCHA Settings</h4>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('app.admin.settings.recaptcha.update') }}">
    @csrf

    {{-- Enable / Disable --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">
                <i data-lucide="shield-check" style="width:20px;height:20px" class="me-2"></i>
                reCAPTCHA Status
            </h5>
            @if($isConfigured)
                <span class="badge bg-success ms-auto">Active</span>
            @else
                <span class="badge bg-secondary ms-auto">Inactive</span>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label class="form-label">Enable reCAPTCHA</label>
                    <select name="enabled" class="form-select" id="recaptcha-enabled">
                        <option value="1" @selected(($settings['enabled'] ?? '0') === '1')>Enabled</option>
                        <option value="0" @selected(($settings['enabled'] ?? '0') === '0')>Disabled</option>
                    </select>
                    <small class="text-muted">Enable or disable reCAPTCHA protection globally.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">reCAPTCHA Version</label>
                    <select name="version" class="form-select">
                        <option value="v2" @selected(($settings['version'] ?? 'v2') === 'v2')>v2 (Checkbox - "I'm not a robot")</option>
                        <option value="v3" @selected(($settings['version'] ?? 'v2') === 'v3')>v3 (Invisible / Score-based)</option>
                    </select>
                    <small class="text-muted">v2 shows a checkbox, v3 runs invisibly in the background.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- API Keys --}}
    <div class="card mb-4" id="keys-card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i data-lucide="key-round" style="width:20px;height:20px" class="me-2"></i>
                API Keys
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <i data-lucide="info" style="width:16px;height:16px" class="me-1"></i>
                Get your reCAPTCHA keys from <a href="https://www.google.com/recaptcha/admin" target="_blank" class="alert-link">Google reCAPTCHA Admin Console</a>.
                Make sure you select the correct version (v2 checkbox or v3) when creating the site.
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Site Key</label>
                    <input type="text" name="site_key" class="form-control" value="{{ old('site_key', $settings['site_key'] ?? '') }}" placeholder="6Lc...">
                    <small class="text-muted">The public site key used in the frontend widget.</small>
                    @error('site_key') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Secret Key</label>
                    <input type="password" name="secret_key" class="form-control" value="{{ old('secret_key', $settings['secret_key'] ?? '') }}" placeholder="6Lc...">
                    <small class="text-muted">The secret key used for server-side verification.</small>
                    @error('secret_key') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Form Protection --}}
    <div class="card mb-4" id="forms-card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i data-lucide="file-lock-2" style="width:20px;height:20px" class="me-2"></i>
                Form Protection
            </h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">Choose which forms should be protected by reCAPTCHA.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enable_login"
                            {{ ($settings['enable_login'] ?? '1') === '1' ? 'checked' : '' }}
                            onchange="document.getElementById('enable_login_val').value = this.checked ? '1' : '0'">
                        <label class="form-check-label" for="enable_login">
                            <strong>Login Page</strong>
                            <br><small class="text-muted">Protect the sign-in form from brute force attacks.</small>
                        </label>
                    </div>
                    <input type="hidden" name="enable_login" id="enable_login_val" value="{{ $settings['enable_login'] ?? '1' }}">
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enable_register"
                            {{ ($settings['enable_register'] ?? '1') === '1' ? 'checked' : '' }}
                            onchange="document.getElementById('enable_register_val').value = this.checked ? '1' : '0'">
                        <label class="form-check-label" for="enable_register">
                            <strong>Registration Page</strong>
                            <br><small class="text-muted">Prevent spam and bot registrations.</small>
                        </label>
                    </div>
                    <input type="hidden" name="enable_register" id="enable_register_val" value="{{ $settings['enable_register'] ?? '1' }}">
                </div>
            </div>
        </div>
    </div>

    {{-- How It Works --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i data-lucide="info" style="width:20px;height:20px" class="me-2"></i>
                How It Works
            </h5>
        </div>
        <div class="card-body">
            <p class="mb-2"><strong>reCAPTCHA v2 (Checkbox):</strong> Users click "I'm not a robot" before submitting the form. Simple and effective against bots.</p>
            <p class="mb-2"><strong>reCAPTCHA v3 (Invisible):</strong> Runs in the background without user interaction. Assigns a score (0.0 to 1.0) — scores below 0.5 are blocked as likely bots.</p>
            <p class="mb-0 text-muted">When disabled, forms will work normally without any captcha protection. Keys are stored encrypted in the database.</p>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save" style="width:16px;height:16px" class="me-1"></i>
            Save reCAPTCHA Settings
        </button>
    </div>
</form>
@endsection
