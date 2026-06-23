@extends('layouts.influencer-portal')
@section('title', 'Getting Started')
@push('styles') @include('influencer.resources._styles') @endpush

@php
    $tm = [
        'guide'    => ['#2563eb','<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>'],
        'video'    => ['#ef4444','<polygon points="5 3 19 12 5 21 5 3"/>'],
        'article'  => ['#d97706','<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>'],
        'course'   => ['#16a34a','<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>'],
        'template' => ['#7c3aed','<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/>'],
    ];
@endphp

@section('content')
<div class="rs-hero">
    <div>
        <h2>Getting Started 🚀</h2>
        <p>New to the program? These hand-picked resources will get you earning in no time.</p>
        <a href="{{ route('influencer.invite.tools') }}" class="cta">Get Your Link <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
</div>

<div class="rs-panel">
    <h3>Start Here</h3>
    @forelse($items as $r)
        @php [$c,$glyph] = $tm[$r->type] ?? $tm['guide']; @endphp        <a href="{{ route('influencer.resources.library') }}" class="rs-list-row" style="text-decoration:none;">
            <span class="rs-list-ic" style="background:{{ $c }}1a; color:{{ $c }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $glyph !!}</svg></span>
            <div class="m"><b>{{ $r->title }}</b><span>{{ $r->type }} · {{ $r->category }}</span></div>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
    @empty
        <div style="text-align:center; color:var(--muted); padding:18px;">Getting-started resources coming soon.</div>
    @endforelse
</div>

<div class="rs-grid" style="grid-template-columns:1fr 1fr 1fr;">
    @foreach([['Browse the Library','Find guides, templates & more','influencer.resources.library','#2563eb','#dbeafe'],['Take a Course','Structured learning in the Academy','influencer.resources.academy','#16a34a','#dcfce7'],['Read Articles','Quick tips from top creators','influencer.resources.articles','#d97706','#fef3c7']] as [$t,$d,$route,$c,$bg])
        <a href="{{ route($route) }}" class="rs-panel" style="text-decoration:none; text-align:center;">
            <div style="width:46px;height:46px;border-radius:13px;background:{{ $bg }};color:{{ $c }};display:flex;align-items:center;justify-content:center;margin:0 auto 10px;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
            <b style="font-family:var(--ff); color:var(--ink); font-size:14.5px;">{{ $t }}</b>
            <p style="font-size:12.5px; color:var(--muted); margin-top:5px;">{{ $d }}</p>
        </a>
    @endforeach
</div>
@endsection
