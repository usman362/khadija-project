@extends('layouts.client')

@section('title', 'Verify Your Identity')
@section('page-title', 'Verify Your Identity')
@section('page-subtitle', 'Get the Verified Client badge on your account.')

@push('styles')
<style>
    .vf { max-width: 760px; }
    .vf-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 22px; margin-bottom: 16px; }
    .vf-head { display: flex; align-items: center; gap: 16px; margin-bottom: 14px; }
    .vf-head h3 { font-size: 17px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; }
    .vf-head p { font-size: 13px; color: var(--text-muted); margin: 0; line-height: 1.55; }
    .vf-state { display: inline-block; font-size: 12px; font-weight: 800; padding: 3px 10px; border-radius: 999px; }
    .vf-state.none { background: #f3f4f6; color: #4b5563; }
    .vf-state.pending { background: #fef3c7; color: #92400e; }
    .vf-state.verified { background: #dcfce7; color: #166534; }
    .vf-state.rejected { background: #fee2e2; color: #991b1b; }
    .vf-steps { margin: 0; padding-left: 18px; font-size: 13px; color: var(--text-secondary); line-height: 1.7; }
    .vf-field { margin-bottom: 14px; }
    .vf-field label { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .vf-field select, .vf-field input[type=file] { width: 100%; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 10px 12px; font: inherit; font-size: 13.5px; background: var(--bg-card); color: var(--text-primary); }
    .vf-check { display: flex; gap: 9px; align-items: flex-start; font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin: 6px 0 16px; }
    .vf-check input { margin-top: 3px; }
    .vf-btn { border: 0; background: #f97316; color: #fff; font-weight: 800; font-size: 14px; padding: 11px 20px; border-radius: 11px; cursor: pointer; }
    .vf-btn:hover { background: #ea580c; }
    .vf-err { color: #b91c1c; font-size: 12.5px; font-weight: 600; margin: 6px 0 0; }
    .vf-note { font-size: 12.5px; color: var(--text-muted); line-height: 1.6; margin: 12px 0 0; }
    .vf-flash { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 12px; padding: 11px 14px; font-size: 13px; margin-bottom: 16px; }
</style>
@endpush

@section('content')
@php $vc = collect(config('badges.client'))->firstWhere('key', 'verified-client'); @endphp
<div class="vf">
    @if(session('status'))
        <div class="vf-flash">{{ session('status') }}</div>
    @endif

    <div class="vf-card">
        <div class="vf-head">
            @if($vc)
                <x-hex-badge :icon="$vc['icon']" :colour="$vc['colour']" :earned="$status === 'verified'" :size="52" />
            @endif
            <div>
                <h3>Verified Client</h3>
                <p>A verified account shows professionals that you are who you say you are.</p>
                <span class="vf-state {{ $status }}" style="margin-top:8px;">
                    {{ ['none' => 'Not verified yet', 'pending' => 'Being reviewed', 'verified' => 'Verified', 'rejected' => 'Not approved'][$status] }}
                </span>
            </div>
        </div>

        @if($status === 'verified')
            <p class="vf-note" style="margin-top:0;">
                Your account was verified on {{ $profile->identity_verified_at->format('M j, Y') }}.
                The Verified Client badge is on your profile.
            </p>
        @elseif($status === 'pending')
            <p class="vf-note" style="margin-top:0;">
                Your {{ strtolower($profile->identity_number ?? 'ID') }} was sent on
                {{ $profile->identity_submitted_at?->format('M j, Y') }} and our team is reviewing it.
                You can send a different one below if you need to.
            </p>
        @else
            @if($status === 'rejected')
                <p class="vf-err" style="margin:0 0 12px;">{{ $profile->identity_rejected_note }}</p>
            @endif
            <ol class="vf-steps">
                <li>Choose the type of ID you have.</li>
                <li>Upload a clear photo or scan where your name and photo are easy to read.</li>
                <li>Our team checks it. When it is approved, the badge appears on your profile.</li>
            </ol>
        @endif
    </div>

    @if($status !== 'verified')
        <div class="vf-card">
            <form method="POST" action="{{ route('client.verification.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="vf-field">
                    <label for="document_type">Type of ID</label>
                    <select name="document_type" id="document_type" required>
                        <option value="">Choose one…</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected(old('document_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('document_type')<p class="vf-err">{{ $message }}</p>@enderror
                </div>

                <div class="vf-field">
                    <label for="document">Photo or scan of your ID</label>
                    <input type="file" name="document" id="document" accept=".jpg,.jpeg,.png,.pdf" required>
                    @error('document')<p class="vf-err">{{ $message }}</p>@enderror
                </div>

                <label class="vf-check">
                    <input type="checkbox" name="certify" value="1" required>
                    <span>This is my own, valid ID, and the name on it is the name on my account.</span>
                </label>
                @error('certify')<p class="vf-err">{{ $message }}</p>@enderror

                <button type="submit" class="vf-btn">{{ $status === 'pending' ? 'Send a different ID' : 'Send for verification' }}</button>

                <p class="vf-note">
                    Your ID is stored privately. Only the GigResource team reviewing it can see it,
                    and it is never shown to professionals.
                </p>
            </form>
        </div>
    @endif
</div>
@endsection
