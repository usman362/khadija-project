@extends('layouts.influencer-portal')
@section('title', 'Tutorials')
@push('styles') @include('influencer.resources._styles') @endpush

@section('content')
<div class="rs-head" style="margin-bottom:18px;"><h1>Video Tutorials</h1><p>Short, practical walkthroughs to help you get the most out of GigResource.</p></div>

<div class="rs-featured" style="grid-template-columns:repeat(3,1fr);">
    @foreach($videos as $v)
        <div class="rs-card">
            <div class="rs-card-top" style="height:120px; background:linear-gradient(135deg,#ef4444,#dc2626);">
                <svg viewBox="0 0 24 24" fill="#fff" stroke="none" style="width:46px;height:46px;"><circle cx="12" cy="12" r="11" opacity=".25"/><polygon points="10 8 16 12 10 16"/></svg>
                <span class="rs-card-badge" style="background:rgba(0,0,0,.35); right:10px; left:auto;">{{ $v->duration_minutes }}:00</span>
            </div>
            <div class="rs-card-body">
                <b>{{ $v->title }}</b>
                <p>{{ $v->description }}</p>
                <div style="display:flex; align-items:center; justify-content:space-between; font-size:11.5px; color:var(--muted);">
                    <span>{{ $v->category }}</span>
                    <span>▶ {{ $v->downloads >= 1000 ? round($v->downloads/1000,1).'K' : $v->downloads }} views</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($videos->isEmpty())
    <div class="rs-panel" style="text-align:center; color:var(--muted);">No tutorials yet — check back soon.</div>
@endif
@endsection
