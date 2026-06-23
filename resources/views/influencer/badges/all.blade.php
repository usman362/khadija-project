@extends('layouts.influencer-portal')
@section('title', 'All Badges')
@push('styles') @include('influencer.badges._styles') @endpush

@php
    $earned = collect($badges)->where('earned', true)->count();
    $badgeGlyph = [
        'gift'   => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M5 12v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8"/><path d="M12 8S10.5 3 8 3a2.5 2.5 0 0 0 0 5zM12 8s1.5-5 4-5a2.5 2.5 0 0 1 0 5z"/>',
        'users'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'zap'    => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'dollar' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'wallet' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
        'trophy' => '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M5 9a2 2 0 0 1-2-2V5h4M19 9a2 2 0 0 0 2-2V5h-4"/>',
        'badge'  => '<polygon points="12 2 15 9 22 9.3 16.5 14 18.5 21 12 17 5.5 21 7.5 14 2 9.3 9 9"/>',
        'crown'  => '<path d="M5 17h14l1-9-4 3-4-6-4 6-4-3z"/>',
    ];
@endphp

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.badges.current') }}">Badges &amp; Tiers</a> <span class="sep">›</span> All Badges</div>
<div class="bt-head" style="display:flex; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; gap:10px;">
    <div><h1>All Badges</h1><p>Hit milestones to unlock achievement badges and show off your progress.</p></div>
    <div style="background:var(--orange-soft); border:1px solid #ffe2cd; border-radius:12px; padding:10px 16px; font-family:var(--ff); font-weight:700; color:var(--orange-dark); font-size:14px;">{{ $earned }} / {{ count($badges) }} earned</div>
</div>

<div class="bt-badges" style="margin-top:22px;">
    @foreach($badges as $b)
        <div class="bt-badge {{ $b['earned'] ? '' : 'locked' }}">
            <div class="bt-badge-ic" style="background:{{ $b['earned'] ? $b['color'] : '#cdd6e4' }};">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $badgeGlyph[$b['icon']] ?? $badgeGlyph['badge'] !!}</svg>
            </div>
            <b>{{ $b['label'] }}</b>
            <span>{{ $b['desc'] }}</span>
            <span class="bt-badge-state {{ $b['earned'] ? 'bt-state-earned' : 'bt-state-locked' }}">{{ $b['earned'] ? '✓ Earned' : 'Locked' }}</span>
        </div>
    @endforeach
</div>
@endsection
