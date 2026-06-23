@extends('layouts.auth-pro')

@section('apRole', 'influencer')
@section('title', 'Application Status')
@section('top-right')
    <form method="POST" action="{{ route('logout') }}" style="display:inline;">@csrf
        <button type="submit" style="background:none;border:none;color:inherit;font:inherit;cursor:pointer;text-decoration:underline;">Log Out</button>
    </form>
@endsection

@php
    $status = $influencer->status->value;
    $meta = [
        'pending'  => ['#f59e0b', '#fffbeb', 'Application Under Review', 'Thanks for applying! Our team is reviewing your details. We\'ll email you as soon as your affiliate account is approved.'],
        'approved' => ['#16a34a', '#ecfdf5', 'You\'re Approved! 🎉', 'Your affiliate account is active. Head to your dashboard to grab your referral link and start earning.'],
        'rejected' => ['#dc2626', '#fef2f2', 'Application Not Approved', 'Unfortunately your application wasn\'t approved at this time. If you think this is a mistake, please contact our support team.'],
    ];
    [$color, $bg, $heading, $message] = $meta[$status] ?? $meta['pending'];
@endphp

@section('auth_form')
    <div style="text-align:center;">
        <div style="width:84px;height:84px;border-radius:50%;background:{{ $bg }};display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            @if($status === 'approved')
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="{{ $color }}" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            @elseif($status === 'rejected')
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="{{ $color }}" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            @else
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="{{ $color }}" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            @endif
        </div>

        <h2 style="margin-bottom:8px;">{{ $heading }}</h2>
        <div class="apx-card-sub" style="margin-bottom:22px;">{{ $message }}</div>

        @if (session('status'))
            <div class="apx-alert apx-alert-success" style="text-align:left;">{{ session('status') }}</div>
        @endif

        @if($status === 'rejected' && $influencer->admin_notes)
            <div class="apx-alert apx-alert-error" style="text-align:left;">
                <strong>Reviewer note:</strong> {{ $influencer->admin_notes }}
            </div>
        @endif

        {{-- Application summary --}}
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;text-align:left;margin-bottom:22px;">
            <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13.5px;">
                <span style="color:#64748b;">Name</span><span style="color:#0f172a;font-weight:600;">{{ $influencer->full_name }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13.5px;border-top:1px solid #eef2f7;">
                <span style="color:#64748b;">Email</span><span style="color:#0f172a;font-weight:600;">{{ $influencer->email }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13.5px;border-top:1px solid #eef2f7;">
                <span style="color:#64748b;">Status</span>
                <span style="color:{{ $color }};font-weight:700;text-transform:capitalize;">{{ $status }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13.5px;border-top:1px solid #eef2f7;">
                <span style="color:#64748b;">Applied</span><span style="color:#0f172a;font-weight:600;">{{ $influencer->created_at->format('M j, Y') }}</span>
            </div>
        </div>

        @if($status === 'approved')
            <a href="{{ route('influencer.dashboard') }}" class="apx-submit" style="display:block;text-align:center;text-decoration:none;">Go to Dashboard →</a>
        @else
            <a href="{{ route('influencer.status') }}" class="apx-submit" style="display:block;text-align:center;text-decoration:none;background:#fff;color:{{ $color }};border:1.5px solid {{ $color }};">Refresh Status</a>
        @endif
    </div>
@endsection
