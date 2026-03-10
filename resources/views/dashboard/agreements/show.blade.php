@extends('layouts.dashboard')

@section('title', 'Agreement: ' . $agreement->title)

@section('content')
<style>
    :root, [data-bs-theme="light"] {
        --agr-bg: #ffffff;
        --agr-text: #212529;
        --agr-muted: #6c757d;
        --agr-border: #dee2e6;
        --agr-doc-bg: #fff;
        --agr-doc-shadow: rgba(0,0,0,0.08);
    }
    [data-bs-theme="dark"] {
        --agr-bg: #111a2e;
        --agr-text: #e1e4e8;
        --agr-muted: #8b949e;
        --agr-border: #1e2d4a;
        --agr-doc-bg: #0d1525;
        --agr-doc-shadow: rgba(0,0,0,0.3);
    }

    .agreement-wrapper {
        max-width: 900px;
        margin: 0 auto;
    }

    .agreement-header-card {
        background: var(--agr-bg);
        border: 1px solid var(--agr-border);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .agreement-doc {
        background: var(--agr-doc-bg);
        border: 1px solid var(--agr-border);
        border-radius: 12px;
        padding: 40px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px var(--agr-doc-shadow);
        color: var(--agr-text);
        line-height: 1.7;
    }

    .agreement-doc h1, .agreement-doc h2, .agreement-doc h3, .agreement-doc h4 {
        color: var(--agr-text);
        margin-top: 1.5em;
        margin-bottom: 0.5em;
    }

    .agreement-doc h1 { font-size: 1.5rem; }
    .agreement-doc h3 { font-size: 1.15rem; font-weight: 600; }
    .agreement-doc ul { padding-left: 1.5rem; }
    .agreement-doc li { margin-bottom: 6px; }

    .terms-card {
        background: var(--agr-bg);
        border: 1px solid var(--agr-border);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .terms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }

    .term-item {
        padding: 12px;
        background: rgba(59,130,246,0.05);
        border-radius: 8px;
        border: 1px solid var(--agr-border);
    }

    .term-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--agr-muted);
        font-weight: 600;
        margin-bottom: 4px;
    }

    .term-value {
        font-size: 0.9rem;
        color: var(--agr-text);
        font-weight: 500;
    }

    .acceptance-card {
        background: var(--agr-bg);
        border: 1px solid var(--agr-border);
        border-radius: 12px;
        padding: 24px;
    }

    .acceptance-party {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border-radius: 8px;
        border: 1px solid var(--agr-border);
    }

    .acceptance-party.accepted {
        border-color: #198754;
        background: rgba(25, 135, 84, 0.05);
    }

    .acceptance-party.pending {
        border-color: var(--agr-border);
    }

    .acceptance-check {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .acceptance-check.done {
        background: #198754;
        color: #fff;
    }

    .acceptance-check.waiting {
        background: var(--agr-border);
        color: var(--agr-muted);
    }
</style>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="agreement-wrapper">
    {{-- Header Card --}}
    <div class="agreement-header-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5 class="mb-1">{{ $agreement->title }}</h5>
                <p class="text-muted mb-0">
                    Version {{ $agreement->version }} &middot;
                    Generated {{ $agreement->created_at->format('M d, Y h:i A') }}
                    by {{ $agreement->generator->name }}
                </p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-{{ $agreement->statusColor() }} fs-6">{{ $agreement->statusLabel() }}</span>
                <span class="badge bg-{{ $agreement->source === 'ai' ? 'info' : 'secondary' }}">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -1px; margin-right: 2px;"><path d="M12 2a4 4 0 0 0-4 4v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2h-2V6a4 4 0 0 0-4-4z"/></svg>
                    {{ ucfirst($agreement->source) }} Generated
                </span>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3 flex-wrap">
            <a href="{{ route('app.agreements.index') }}" class="btn btn-sm btn-outline-secondary">Back to Agreements</a>
            @if($agreement->booking->conversation)
                <a href="{{ route('app.chat.show', $agreement->booking->conversation) }}" class="btn btn-sm btn-outline-primary">View Chat</a>
            @endif
        </div>
    </div>

    {{-- Extracted Terms --}}
    @if($agreement->extracted_terms && count($agreement->extracted_terms))
        <div class="terms-card">
            <h6 class="mb-3">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px; margin-right: 4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Extracted Terms
            </h6>
            <div class="terms-grid">
                @foreach($agreement->extracted_terms as $key => $value)
                    <div class="term-item">
                        <div class="term-label">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                        <div class="term-value">{{ is_array($value) ? implode(', ', $value) : $value }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Agreement Document --}}
    <div class="agreement-doc">
        {!! $agreement->content !!}
    </div>

    {{-- Acceptance Status --}}
    <div class="acceptance-card">
        <h6 class="mb-3">Agreement Acceptance</h6>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="acceptance-party {{ $agreement->clientAccepted() ? 'accepted' : 'pending' }}">
                    <div class="acceptance-check {{ $agreement->clientAccepted() ? 'done' : 'waiting' }}">
                        @if($agreement->clientAccepted())
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        @else
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                        @endif
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $agreement->booking->client->name ?? 'Client' }}</div>
                        @if($agreement->clientAccepted())
                            <small class="text-success">Accepted {{ $agreement->client_accepted_at->format('M d, Y h:i A') }}</small>
                        @else
                            <small class="text-muted">Awaiting acceptance</small>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="acceptance-party {{ $agreement->supplierAccepted() ? 'accepted' : 'pending' }}">
                    <div class="acceptance-check {{ $agreement->supplierAccepted() ? 'done' : 'waiting' }}">
                        @if($agreement->supplierAccepted())
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        @else
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                        @endif
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $agreement->booking->supplier->name ?? 'Vendor' }}</div>
                        @if($agreement->supplierAccepted())
                            <small class="text-success">Accepted {{ $agreement->supplier_accepted_at->format('M d, Y h:i A') }}</small>
                        @else
                            <small class="text-muted">Awaiting acceptance</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        @if(!$agreement->isFullyAccepted() && !$agreement->isRejected())
            <div class="d-flex gap-2 flex-wrap">
                {{-- Accept button --}}
                @if(($isClient && !$agreement->clientAccepted()) || ($isSupplier && !$agreement->supplierAccepted()) || $isAdmin)
                    <form method="POST" action="{{ route('app.agreements.accept', $agreement) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success"
                            onclick="return confirm('By accepting, you agree to all terms in this agreement. Proceed?')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px; margin-right: 4px;"><polyline points="20 6 9 17 4 12"/></svg>
                            Accept Agreement
                        </button>
                    </form>
                @endif

                {{-- Reject button --}}
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    Reject & Request Changes
                </button>

                {{-- Regenerate --}}
                <form method="POST" action="{{ route('app.agreements.regenerate', $agreement->booking) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary"
                        onclick="return confirm('Generate a new version of the agreement?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px; margin-right: 4px;"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                        Regenerate
                    </button>
                </form>
            </div>
        @endif

        @if($agreement->isFullyAccepted())
            <div class="alert alert-success mt-3 mb-0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align: -3px; margin-right: 6px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <strong>Agreement fully accepted by both parties!</strong> The booking has been confirmed.
            </div>
        @endif

        @if($agreement->isRejected())
            <div class="alert alert-danger mt-3 mb-0">
                <strong>Agreement Rejected</strong>
                @if($agreement->rejection_reason)
                    <br>Reason: {{ $agreement->rejection_reason }}
                @endif
                <br>
                <form method="POST" action="{{ route('app.agreements.regenerate', $agreement->booking) }}" class="d-inline mt-2">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger mt-2">Generate New Version</button>
                </form>
            </div>
        @endif

        @if($agreement->ai_prompt_summary)
            <div class="mt-3">
                <small class="text-muted">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    {{ $agreement->ai_prompt_summary }}
                </small>
            </div>
        @endif
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('app.agreements.reject', $agreement) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Agreement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please describe what changes you'd like. A new version can be generated based on your feedback.</p>
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection</label>
                        <textarea name="rejection_reason" class="form-control" rows="3" placeholder="e.g. The pricing doesn't match what we discussed..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Agreement</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
