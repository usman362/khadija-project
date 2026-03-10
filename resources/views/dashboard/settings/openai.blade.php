@extends('layouts.dashboard')

@section('title', 'OpenAI Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <h4 class="mb-0">OpenAI Settings</h4>
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

<form method="POST" action="{{ route('app.admin.settings.openai.update') }}">
    @csrf

    {{-- API Configuration --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">
                <i data-lucide="brain" style="width:20px;height:20px" class="me-2"></i>
                API Configuration
            </h5>
            @if($isConfigured)
                <span class="badge bg-success ms-auto">Configured</span>
            @else
                <span class="badge bg-secondary ms-auto">Not Configured</span>
            @endif
        </div>
        <div class="card-body">
            @if($envFallback && empty($settings['api_key']))
                <div class="alert alert-info mb-3">
                    <i data-lucide="info" style="width:16px;height:16px" class="me-1"></i>
                    Currently using API key from <code>.env</code> file. Add a key below to override it from the dashboard.
                </div>
            @endif

            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">API Key</label>
                    <input type="password" name="api_key" class="form-control" value="{{ old('api_key', $settings['api_key'] ?? '') }}" placeholder="sk-...">
                    <small class="text-muted">Your OpenAI API key. Get one from <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>. Leave empty to use .env value.</small>
                    @error('api_key') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Model Settings --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i data-lucide="sliders-horizontal" style="width:20px;height:20px" class="me-2"></i>
                Model Settings
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Model</label>
                    <select name="model" class="form-select">
                        <option value="gpt-4o" @selected(($settings['model'] ?? 'gpt-4o-mini') === 'gpt-4o')>GPT-4o (Best quality)</option>
                        <option value="gpt-4o-mini" @selected(($settings['model'] ?? 'gpt-4o-mini') === 'gpt-4o-mini')>GPT-4o Mini (Faster, cheaper)</option>
                        <option value="gpt-4-turbo" @selected(($settings['model'] ?? '') === 'gpt-4-turbo')>GPT-4 Turbo</option>
                        <option value="gpt-3.5-turbo" @selected(($settings['model'] ?? '') === 'gpt-3.5-turbo')>GPT-3.5 Turbo (Cheapest)</option>
                    </select>
                    <small class="text-muted">The AI model used for generating agreements.</small>
                    @error('model') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Max Tokens</label>
                    <input type="number" name="max_tokens" class="form-control" value="{{ old('max_tokens', $settings['max_tokens'] ?? '4000') }}" min="100" max="16000" step="100">
                    <small class="text-muted">Maximum length of AI-generated content (100–16000).</small>
                    @error('max_tokens') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Temperature</label>
                    <input type="number" name="temperature" class="form-control" value="{{ old('temperature', $settings['temperature'] ?? '0.3') }}" min="0" max="2" step="0.1">
                    <small class="text-muted">Creativity level: 0 = precise, 1 = creative, 2 = very random.</small>
                    @error('temperature') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Usage Info --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i data-lucide="info" style="width:20px;height:20px" class="me-2"></i>
                How It Works
            </h5>
        </div>
        <div class="card-body">
            <p class="mb-2">OpenAI is used by the <strong>AI Agreement</strong> module to automatically generate professional service agreements between clients and suppliers based on their booking details and chat history.</p>
            <p class="mb-0 text-muted">If no API key is configured, the system will fall back to a built-in template generator that creates agreements without AI.</p>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save" style="width:16px;height:16px" class="me-1"></i>
            Save OpenAI Settings
        </button>
    </div>
</form>
@endsection
