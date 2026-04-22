@extends('layouts.dashboard')
@section('title', 'AI Chatbot Settings')
@section('content')

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="message-circle" class="me-2" style="width:22px;height:22px;"></i> AI Chatbot Settings</h4>
        <p class="text-secondary mb-0">Configure the AI assistant that helps users across the platform.</p>
    </div>
    <a href="{{ route('app.admin.chatbot-logs.index') }}" class="btn btn-outline-primary">
        <i data-lucide="list" style="width:16px;height:16px;"></i> View Chat Logs
    </a>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@if(!$openai_configured)
    <div class="alert alert-warning d-flex align-items-start">
        <i data-lucide="alert-triangle" style="width:18px;height:18px;" class="me-2 mt-1"></i>
        <div>
            <strong>OpenAI not configured.</strong> The chatbot requires an OpenAI API key. Configure it in
            <a href="{{ route('app.admin.settings.openai') }}">OpenAI Settings</a> first.
        </div>
    </div>
@endif

<form method="POST" action="{{ route('app.admin.settings.chatbot.update') }}">
    @csrf

    {{-- Status & core settings --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Core Settings</h5>
            @if($settings['enabled'])
                <span class="badge bg-success ms-auto">Enabled</span>
            @else
                <span class="badge bg-secondary ms-auto">Disabled</span>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Enabled</label>
                    <select name="enabled" class="form-select">
                        <option value="1" @selected($settings['enabled'])>Yes — show chatbot widget</option>
                        <option value="0" @selected(!$settings['enabled'])>No — hide chatbot</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Model</label>
                    <select name="model" class="form-select">
                        <option value="gpt-4o-mini" @selected($settings['model'] === 'gpt-4o-mini')>GPT-4o Mini (fast, cheap)</option>
                        <option value="gpt-4o"      @selected($settings['model'] === 'gpt-4o')>GPT-4o (smart, expensive)</option>
                        <option value="gpt-4-turbo" @selected($settings['model'] === 'gpt-4-turbo')>GPT-4 Turbo</option>
                        <option value="gpt-3.5-turbo" @selected($settings['model'] === 'gpt-3.5-turbo')>GPT-3.5 Turbo (fastest)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Daily Message Limit per User</label>
                    <input type="number" name="daily_limit" class="form-control" value="{{ old('daily_limit', $settings['daily_limit']) }}" min="0" max="1000">
                    <div class="form-text small">0 = unlimited</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Max Response Tokens</label>
                    <input type="number" name="max_tokens" class="form-control" value="{{ old('max_tokens', $settings['max_tokens']) }}" min="100" max="4000">
                    <div class="form-text small">Longer responses cost more. Recommended: 600-1000.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Temperature (Creativity)</label>
                    <input type="number" name="temperature" step="0.1" class="form-control" value="{{ old('temperature', $settings['temperature']) }}" min="0" max="2">
                    <div class="form-text small">0 = factual, 1 = balanced, 2 = creative. Recommended: 0.3-0.7.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- System prompt --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">System Prompt</h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('system_prompt').value = {{ json_encode($default_prompt) }};">
                Reset to Default
            </button>
        </div>
        <div class="card-body">
            <label class="form-label">Instructions for the AI</label>
            <textarea name="system_prompt" id="system_prompt" class="form-control" rows="14" style="font-family: ui-monospace, monospace; font-size: 13px;">{{ old('system_prompt', $settings['system_prompt']) }}</textarea>
            <div class="form-text small mt-2">This controls the AI's behavior and knowledge. The user's name, email, roles, and current date are automatically appended.</div>
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
