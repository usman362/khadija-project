@extends('layouts.dashboard')

@section('title', 'Account Deletion Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div>
        <h4 class="mb-1">
            <i data-lucide="user-x" class="me-2" style="width:22px;height:22px;"></i>
            Account Deletion & Reactivation
        </h4>
        <p class="text-secondary mb-0">Configure the 60-day grace period and reactivation fee for deleted accounts.</p>
    </div>
    <a href="{{ route('app.admin.deletion-requests.index') }}" class="btn btn-outline-primary">
        <i data-lucide="list" style="width:16px;height:16px;"></i> View Deletion Requests
    </a>
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

<form method="POST" action="{{ route('app.admin.settings.account-deletion.update') }}">
    @csrf

    {{-- Info banner --}}
    <div class="alert alert-info d-flex align-items-start" style="background:rgba(59,130,246,0.08); border-color:rgba(59,130,246,0.25);">
        <i data-lucide="info" style="width:18px;height:18px;" class="me-2 mt-1 flex-shrink-0"></i>
        <div class="small">
            When a user submits an account deletion request, they enter a <strong>60-day grace period</strong> before their data is permanently removed.
            During this period, they can <strong>reactivate their account</strong> — optionally by paying a fee configured below.
            Payment gateway credentials are inherited from <a href="{{ route('app.admin.settings.payments') }}">Payment Settings</a>.
        </div>
    </div>

    {{-- Reactivation Fee Settings --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">
                <i data-lucide="dollar-sign" style="width:20px;height:20px;" class="me-2"></i>
                Reactivation Fee
            </h5>
            @if($settings['enabled'])
                <span class="badge bg-success ms-auto">Fee Enabled</span>
            @else
                <span class="badge bg-secondary ms-auto">Free Restore</span>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Charge a Fee?</label>
                    <select name="enabled" class="form-select">
                        <option value="1" @selected($settings['enabled'])>Yes — require payment</option>
                        <option value="0" @selected(!$settings['enabled'])>No — free restoration</option>
                    </select>
                    @error('enabled') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fee Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ $settings['currency'] }}</span>
                        <input type="number" name="fee" step="0.01" min="0" max="9999"
                               class="form-control" value="{{ old('fee', number_format($settings['fee'], 2, '.', '')) }}">
                    </div>
                    @error('fee') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div class="form-text small text-secondary">One-time charge when a user reactivates.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" class="form-control text-uppercase"
                           value="{{ old('currency', $settings['currency']) }}" maxlength="3">
                    @error('currency') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div class="form-text small text-secondary">ISO 4217 code (e.g. USD, EUR).</div>
                </div>
            </div>

            <hr class="my-4">

            <div class="small text-secondary">
                <strong>Preview:</strong>
                @if($settings['enabled'])
                    Users will be charged <strong>{{ $settings['currency'] }} {{ number_format($settings['fee'], 2) }}</strong> to reactivate their account during the 60-day grace period.
                @else
                    Users can reactivate their account for <strong>free</strong> during the 60-day grace period.
                @endif
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save" style="width:16px;height:16px;" class="me-1"></i>
            Save Settings
        </button>
    </div>
</form>
@endsection
