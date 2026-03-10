@extends('layouts.dashboard')

@section('title', 'Payment Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <h4 class="mb-0">Payment Settings</h4>
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

<form method="POST" action="{{ route('app.admin.settings.payments.update') }}">
    @csrf

    {{-- General Payment Settings --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">General</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Active Gateway</label>
                    <select name="active_gateway" class="form-select">
                        <option value="stripe" @selected(($settings['active_gateway'] ?? 'stripe') === 'stripe')>Stripe</option>
                        <option value="paypal" @selected(($settings['active_gateway'] ?? '') === 'paypal')>PayPal</option>
                    </select>
                    @error('active_gateway') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mode</label>
                    <select name="mode" class="form-select">
                        <option value="test" @selected(($settings['mode'] ?? 'test') === 'test')>Test / Sandbox</option>
                        <option value="live" @selected(($settings['mode'] ?? '') === 'live')>Live</option>
                    </select>
                    @error('mode') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" class="form-control" value="{{ old('currency', $settings['currency'] ?? 'USD') }}" maxlength="3">
                    @error('currency') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            @if(($settings['mode'] ?? 'test') === 'live')
                <div class="alert alert-warning mt-3 mb-0">
                    <i data-lucide="alert-triangle" style="width:16px;height:16px" class="me-1"></i>
                    <strong>Live Mode Active</strong> — Real payments will be processed. Make sure your keys are correct.
                </div>
            @endif
        </div>
    </div>

    {{-- Stripe Settings --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                Stripe
            </h5>
            @if(!empty($settings['stripe_secret_key']))
                <span class="badge bg-success ms-auto">Configured</span>
            @else
                <span class="badge bg-secondary ms-auto">Not Configured</span>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Publishable Key</label>
                    <input type="text" name="stripe_public_key" class="form-control" value="{{ old('stripe_public_key', $settings['stripe_public_key'] ?? '') }}" placeholder="pk_test_...">
                    @error('stripe_public_key') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Secret Key</label>
                    <input type="password" name="stripe_secret_key" class="form-control" value="{{ old('stripe_secret_key', $settings['stripe_secret_key'] ?? '') }}" placeholder="sk_test_...">
                    <small class="text-muted">Leave empty to keep current value</small>
                    @error('stripe_secret_key') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Webhook Secret</label>
                    <input type="password" name="stripe_webhook_secret" class="form-control" value="{{ old('stripe_webhook_secret', $settings['stripe_webhook_secret'] ?? '') }}" placeholder="whsec_...">
                    <small class="text-muted">Required for payment confirmations. Webhook URL: <code>{{ url('/webhooks/stripe') }}</code></small>
                    @error('stripe_webhook_secret') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- PayPal Settings --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                PayPal
            </h5>
            @if(!empty($settings['paypal_client_id']))
                <span class="badge bg-success ms-auto">Configured</span>
            @else
                <span class="badge bg-secondary ms-auto">Not Configured</span>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Client ID</label>
                    <input type="text" name="paypal_client_id" class="form-control" value="{{ old('paypal_client_id', $settings['paypal_client_id'] ?? '') }}" placeholder="AX...">
                    @error('paypal_client_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Secret</label>
                    <input type="password" name="paypal_secret" class="form-control" value="{{ old('paypal_secret', $settings['paypal_secret'] ?? '') }}" placeholder="EL...">
                    @error('paypal_secret') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Webhook ID</label>
                    <input type="text" name="paypal_webhook_id" class="form-control" value="{{ old('paypal_webhook_id', $settings['paypal_webhook_id'] ?? '') }}">
                    <small class="text-muted">Webhook URL: <code>{{ url('/webhooks/paypal') }}</code></small>
                    @error('paypal_webhook_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save" style="width:16px;height:16px" class="me-1"></i>
            Save Payment Settings
        </button>
    </div>
</form>
@endsection
