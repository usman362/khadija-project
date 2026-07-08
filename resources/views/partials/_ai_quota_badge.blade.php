{{-- Shared AI-feature plan status bar (Developer Feedback v1.1 §8.3).
     Expects $status from AiFeatureGate::status(). Optional $tool (display name).
     Surfaces the tier/quota state; hard enforcement is server-side in the controller. --}}
@php
    $aiTool    = $tool ?? 'This AI tool';
    $enabled   = $status['enabled'] ?? true;
    $unlimited = $status['unlimited'] ?? false;
    $remaining = (int) ($status['remaining'] ?? 0);
    $quota     = (int) ($status['quota'] ?? 0);
    $low       = $remaining > 0 && $remaining <= 3;
@endphp
@if(!$enabled)
    {{-- Locked — not in the current plan --}}
    <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap; padding:14px 18px; margin-bottom:18px; border:1px solid rgba(245,158,11,0.35); background:rgba(245,158,11,0.10); border-radius:14px;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" style="flex-shrink:0;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <div style="flex:1; min-width:200px;">
            <div style="font-size:14px; font-weight:700; color:var(--text-primary, #1e293b);">Premium AI tool</div>
            <div style="font-size:12.5px; color:var(--text-muted, #64748b); margin-top:2px;">{{ $aiTool }} isn't included in your current plan. Upgrade to unlock it.</div>
        </div>
        <a href="{{ route('app.membership-plans.index') }}" style="display:inline-flex; align-items:center; gap:7px; padding:9px 18px; background:#f59e0b; color:#fff; font-size:13px; font-weight:700; border-radius:10px; white-space:nowrap;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="17 11 12 6 7 11"/><polyline points="17 18 12 13 7 18"/></svg>
            Upgrade Plan
        </a>
    </div>
@elseif($unlimited)
    {{-- Intentionally no badge. Peter: stop marketing the tool as simply
         "Unlimited" — the AI level banner (Do It Myself / Help Me Plan /
         Coordinate It For Me) already states the user's entitlement, so a
         second "Unlimited on your plan" pill was redundant. --}}
@elseif($remaining <= 0)
    <div style="display:inline-flex; align-items:center; gap:7px; padding:6px 14px; margin-bottom:18px; border-radius:999px; background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.3); color:#ef4444; font-size:12.5px; font-weight:700;">
        Monthly limit reached — resets on the 1st
    </div>
@else
    <div style="display:inline-flex; align-items:center; gap:7px; padding:6px 14px; margin-bottom:18px; border-radius:999px; background:{{ $low ? 'rgba(245,158,11,0.12)' : 'rgba(37,99,235,0.10)' }}; border:1px solid {{ $low ? 'rgba(245,158,11,0.3)' : 'rgba(37,99,235,0.25)' }}; color:{{ $low ? '#f59e0b' : '#2563eb' }}; font-size:12.5px; font-weight:700;">
        {{ $remaining }} / {{ $quota }} left this month
    </div>
@endif
