@extends('layouts.client')
@section('title', 'Post a Rush Request (ESR)')
@section('page-title', 'Emergency Service Request (ESR)')
@section('page-subtitle', 'For urgent, time-sensitive needs within 72 hours')

@push('styles')
<style>
    .esr { max-width: 900px; margin: 0 auto; }
    .esr-alert { display:flex; gap:12px; align-items:flex-start; background:#fef2f2; border:1px solid #fecaca; border-radius:14px; padding:14px 16px; margin-bottom:18px; }
    .esr-alert svg { width:22px; height:22px; color:#dc2626; flex-shrink:0; margin-top:1px; }
    .esr-alert b { color:#b91c1c; }
    .esr-alert p { margin:2px 0 0; font-size:13px; color:#7f1d1d; }
    .esr-card { background:var(--bg-card,#fff); border:1px solid var(--border-color,#e5e7eb); border-radius:16px; padding:22px; margin-bottom:16px; }
    .esr-sec-h { font-size:12px; font-weight:800; letter-spacing:.4px; text-transform:uppercase; color:#dc2626; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
    .esr-field { margin-bottom:14px; }
    .esr-field label { display:block; font-size:12.5px; font-weight:700; color:var(--text-primary,#111827); margin-bottom:6px; }
    .esr-req { color:#dc2626; }
    .esr-input, .esr-select, .esr-textarea { width:100%; border:1px solid var(--border-color,#e5e7eb); border-radius:10px; padding:11px 12px; font-size:14px; font-family:inherit; color:var(--text-primary,#111827); background:var(--bg-card,#fff); outline:none; }
    .esr-input:focus, .esr-select:focus, .esr-textarea:focus { border-color:#f97316; box-shadow:0 0 0 3px rgba(249,115,22,.12); }
    .esr-textarea { min-height:80px; resize:vertical; }
    .esr-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .esr-grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
    .esr-reasons { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .esr-reason { display:flex; gap:10px; align-items:flex-start; border:1.5px solid var(--border-color,#e5e7eb); border-radius:12px; padding:12px; cursor:pointer; font-size:13px; }
    .esr-reason:has(input:checked) { border-color:#dc2626; background:#fef2f2; }
    .esr-reason input { margin-top:2px; accent-color:#dc2626; }
    .esr-services { display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); gap:8px; max-height:230px; overflow-y:auto; padding:2px; }
    .esr-svc { display:flex; gap:8px; align-items:center; border:1.5px solid var(--border-color,#e5e7eb); border-radius:10px; padding:9px 11px; cursor:pointer; font-size:13px; }
    .esr-svc:has(input:checked) { border-color:#f97316; background:#fff7ed; }
    .esr-svc input { accent-color:#f97316; }
    .esr-foot { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .esr-fees { font-size:12.5px; color:var(--text-muted,#6b7280); }
    .esr-fees b { color:var(--text-primary,#111827); }
    .esr-btn { display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#f43f5e,#dc2626); color:#fff; border:none; border-radius:12px; padding:13px 26px; font-size:14.5px; font-weight:800; cursor:pointer; }
    .esr-btn:hover { filter:brightness(1.05); }
    @media (max-width:640px){ .esr-grid3,.esr-grid2,.esr-reasons{ grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="esr">
    @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:10px;padding:11px 15px;margin-bottom:16px;font-size:13.5px;font-weight:600;">{{ $errors->first() }}</div>
    @endif

    <div class="esr-alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
            <b>This is an Emergency Service Request.</b>
            <p>Use this only for genuine time-sensitive needs within the next 72 hours. Verified professionals are notified with priority, and you'll see responses on your Proposals page.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('client.esr.store') }}">
        @csrf

        {{-- 1. Emergency & timing --}}
        <div class="esr-card">
            <div class="esr-sec-h"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Emergency &amp; Timing</div>
            <div class="esr-field">
                <label>What do you need? <span class="esr-req">*</span></label>
                <input name="event_name" class="esr-input" required maxlength="200" value="{{ old('event_name') }}" placeholder="e.g. Replacement DJ for tonight's reception">
            </div>
            <div class="esr-field">
                <label>Why is this urgent? <span class="esr-req">*</span></label>
                <div class="esr-reasons">
                    @foreach($reasons as $key => $label)
                        <label class="esr-reason"><input type="radio" name="reason" value="{{ $key }}" @checked(old('reason')===$key) required><span>{{ $label }}</span></label>
                    @endforeach
                </div>
            </div>
            <div class="esr-grid3">
                <div class="esr-field"><label>Needed by <span class="esr-req">*</span></label><input type="datetime-local" name="needed_by" class="esr-input" required value="{{ old('needed_by') }}"></div>
                <div class="esr-field"><label>Location</label><input name="location" class="esr-input" value="{{ old('location') }}" placeholder="Venue or city"></div>
                <div class="esr-field"><label>Guest count</label><input type="number" name="guest_count" class="esr-input" value="{{ old('guest_count') }}" placeholder="e.g. 150"></div>
            </div>
        </div>

        {{-- 2. Services --}}
        <div class="esr-card">
            <div class="esr-sec-h"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Services You Need <span class="esr-req">*</span></div>
            <div class="esr-services">
                @foreach($categories as $cat)
                    <label class="esr-svc"><input type="checkbox" name="services[]" value="{{ $cat->id }}" @checked(is_array(old('services')) && in_array($cat->id, old('services')))><span>{{ $cat->name }}</span></label>
                @endforeach
            </div>
        </div>

        {{-- 3. Budget & details --}}
        <div class="esr-card">
            <div class="esr-sec-h"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>Budget &amp; Details</div>
            <div class="esr-grid2">
                <div class="esr-field"><label>Budget (visible to responders only)</label><input type="number" name="budget_min" class="esr-input" value="{{ old('budget_min') }}" placeholder="e.g. 2000"></div>
            </div>
            <div class="esr-field"><label>Anything else the pro should know?</label><textarea name="description" class="esr-textarea" maxlength="2000" placeholder="Scope, access, equipment, timing…">{{ old('description') }}</textarea></div>
        </div>

        {{-- Publish --}}
        <div class="esr-card esr-foot">
            <div class="esr-fees">
                <div><b>$0</b> to post — you only pay a single <b>$2.99</b> when you finalize with a professional.</div>
                <div style="margin-top:2px;">Nothing is charged to post, and nothing if the request goes unfilled.</div>
            </div>
            <button type="submit" class="esr-btn">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                Publish Rush Request
            </button>
        </div>
    </form>
</div>
@endsection
