@extends('layouts.influencer-portal')
@section('title', 'Academy')
@push('styles') @include('influencer.resources._styles') @endpush

@php
    $lvlColor = ['beginner'=>'#16a34a','intermediate'=>'#2563eb','advanced'=>'#ea580c'];
    $catIcons = [
        'Getting Started'       => ['#16a34a','#dcfce7','<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>'],
        'Account & Profile'     => ['#2563eb','#dbeafe','<circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/>'],
        'Marketing & Promotion' => ['#f97316','#fff3ea','<path d="M3 11l16-5v12L3 13z"/>'],
        'Analytics & Insights'  => ['#7c3aed','#ede9fe','<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
        'Earnings & Payouts'    => ['#d97706','#fef3c7','<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
        'Tools & Resources'     => ['#0891b2','#cffafe','<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2-2z"/>'],
    ];
@endphp

@section('content')
<div class="rs-head" style="margin-bottom:18px;"><h1>Academy</h1><p>Learn proven strategies, explore best practices, and grow your influence and earnings.</p></div>

<div class="rs-hero">
    <div>
        <h2>Grow Your Influence. Master Your Earnings.</h2>
        <p>Step-by-step training and expert insights to help you succeed on GigResource.</p>
        <a href="#courses" class="cta">Start Learning <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
    <div style="width:130px;height:100px;display:flex;align-items:center;justify-content:center;background:var(--orange-soft);border-radius:16px;"><svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="1.6"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div>
</div>

<div class="rs-stats">
    <div class="rs-stat"><span class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span><div><div class="v">{{ $courses->count() }}</div><div class="l">Courses</div></div></div>
    <div class="rs-stat"><span class="ic" style="background:var(--blue-soft);color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg></span><div><div class="v">{{ $totalLessons }}</div><div class="l">Lessons</div></div></div>
    <div class="rs-stat"><span class="ic" style="background:var(--orange-soft);color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><div><div class="v">{{ round($totalMinutes/60,1) }} hrs</div><div class="l">Total Content</div></div></div>
    <div class="rs-stat"><span class="ic" style="background:#ede9fe;color:#7c3aed;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span><div><div class="v">8,932</div><div class="l">Learners</div></div></div>
</div>

<div class="rs-section-head"><h3>Browse by Category</h3></div>
<div class="rs-cats" style="margin-bottom:24px;">
    @foreach($categories as $cat => $count)
        @php [$c,$bg,$glyph] = $catIcons[$cat] ?? ['#f97316','#fff3ea','<circle cx="12" cy="12" r="9"/>']; @endphp        <div class="rs-cat"><div class="ic" style="background:{{ $bg }};color:{{ $c }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $glyph !!}</svg></div><b>{{ $cat }}</b><span>{{ $count }} {{ Str::plural('Course',$count) }}</span></div>
    @endforeach
</div>

<div class="rs-section-head" id="courses"><h3>Popular Courses</h3></div>
<div class="rs-courses">
    @foreach($courses as $crs)
        @php $lc = $lvlColor[$crs->level] ?? '#f97316'; @endphp        <div class="rs-course">
            <div class="rs-course-top" style="background:linear-gradient(135deg,{{ $lc }},{{ $lc }}cc);">
                <span class="rs-course-lvl">{{ $crs->level }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            </div>
            <div class="rs-course-body">
                <b>{{ $crs->title }}</b>
                <div class="rs-course-meta">
                    <span>📚 {{ $crs->lessons }} lessons</span>
                    <span>⏱ {{ $crs->duration_minutes }} min</span>
                </div>
                <a href="{{ route('influencer.resources.library') }}" class="rs-course-cta">Start Course</a>
            </div>
        </div>
    @endforeach
</div>
@endsection
