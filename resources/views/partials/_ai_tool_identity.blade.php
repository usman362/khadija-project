{{-- AI-tool identity strip (Peter): under each AI tool show WHO the user is —
     their name/business, their membership tier (if any), and the AI level they
     get on this tool (Do It Myself / Help Me Plan / Coordinate It For Me).
     Self-gating: renders only on the ai-tools/* pages. --}}
@php
    $aiId_show = request()->is('ai-tools/*') || request()->is('ai-tools');
@endphp
@if($aiId_show && auth()->check())
    @php
        $aiId_user = auth()->user();
        $aiId_name = $aiId_user->profile?->company_name ?: $aiId_user->name;

        $aiId_plan = $aiId_user->activeSubscription()?->plan?->name;
        // Clients & influencers get AI free at launch — label them clearly
        // instead of leaving the tier blank.
        $aiId_tier = $aiId_plan ?: ($aiId_user->activeRole() === 'supplier' ? 'Free plan' : 'Free access');

        $aiId_key   = (string) request()->segment(2);
        $aiId_level = $aiId_key ? \App\Domain\AiFeatures\AiAccess::level($aiId_user, $aiId_key) : 'maximum';
        $aiId_lvlLabel = \App\Domain\AiFeatures\AiAccess::label($aiId_level);
        $aiId_lvlColor = ['manual' => '#64748b', 'semi' => '#2563eb', 'maximum' => '#16a34a', 'none' => '#94a3b8'][$aiId_level] ?? '#64748b';
    @endphp
    @if($aiId_key && $aiId_key !== '' && $aiId_level !== 'none')
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin:0 0 14px;">
            <span style="display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; color:var(--text-primary,#1e293b); background:var(--bg-card,#f8fafc); border:1px solid var(--border-color,#e2e8f0); border-radius:999px; padding:5px 12px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                {{ $aiId_name }}
            </span>
            <span style="display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; color:#a16207; background:rgba(234,179,8,.12); border:1px solid rgba(234,179,8,.3); border-radius:999px; padding:5px 12px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.6 4.4 9l4.3 3.8L12 6l3.3 6.8L19.6 9 21 17.6a1 1 0 0 1-1 1.2H4a1 1 0 0 1-1-1.2z"/></svg>
                {{ $aiId_tier }}
            </span>
            <span style="display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:800; color:#fff; background:{{ $aiId_lvlColor }}; border-radius:999px; padding:5px 12px;">
                ✨ {{ $aiId_lvlLabel }}
            </span>
        </div>
    @endif
@endif
