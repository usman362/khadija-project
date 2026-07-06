@extends('layouts.influencer-portal')
@section('title', 'Featured Articles')
@push('styles') @include('influencer.resources._styles') @endpush

@php $colors = ['#16a34a','#2563eb','#7c3aed','#16a34a','#db2777','#0891b2']; @endphp
@section('content')
<div class="rs-head" style="margin-bottom:18px;"><h1>Featured Articles</h1><p>Practical reads and expert tips to grow your influence and earnings.</p></div>

<div class="rs-articles">
    @foreach($articles as $i => $a)
        @php $c = $colors[$i % count($colors)]; @endphp        <div class="rs-article">
            <div class="rs-article-top" style="background:linear-gradient(135deg,{{ $c }},{{ $c }}cc);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div class="rs-article-body">
                <span class="rs-article-cat">{{ $a->category }}</span>
                <b>{{ $a->title }}</b>
                <p>{{ $a->description }}</p>
                <div class="rs-article-meta">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    {{ $a->duration_minutes }} min read · {{ $a->published_at?->format('M j, Y') }}
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($articles->isEmpty())
    <div class="rs-panel" style="text-align:center; color:var(--muted);">No articles yet — check back soon.</div>
@endif
@endsection
