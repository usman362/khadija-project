@extends('layouts.public')

@section('title', ($post->meta_title ?: $post->title) . ' - ' . config('app.name', 'Khadija'))

@push('styles')
<style>
    .post-hero {
        padding: 120px 0 40px;
        max-width: 820px;
        margin: 0 auto;
        text-align: center;
    }
    .post-cat {
        display: inline-block;
        padding: 4px 14px;
        background: rgba(99,102,241,0.15);
        color: #a5b4fc;
        border-radius: 30px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
    }
    .post-hero h1 {
        font-size: 2.5rem;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -1px;
        color: var(--text-white);
        margin-bottom: 20px;
    }
    .post-meta {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
        font-size: 0.85rem;
        color: var(--text-muted);
    }
    .post-meta-item { display: flex; align-items: center; gap: 6px; }
    .post-meta-item svg { width: 14px; height: 14px; }
    .post-author-img {
        width: 28px; height: 28px;
        border-radius: 50%;
        object-fit: cover;
    }

    .post-featured {
        max-width: 1000px;
        margin: 40px auto 0;
        padding: 0 24px;
    }
    .post-featured img {
        width: 100%;
        height: auto;
        max-height: 500px;
        object-fit: cover;
        border-radius: 20px;
        border: 1px solid var(--border-color);
    }

    .post-body {
        max-width: 780px;
        margin: 0 auto;
        padding: 60px 24px 80px;
        font-size: 1.05rem;
        line-height: 1.85;
        color: var(--text-light);
    }
    .post-body h2 { color: var(--text-white); font-size: 1.6rem; font-weight: 700; margin: 40px 0 16px; }
    .post-body h3 { color: var(--text-white); font-size: 1.25rem; font-weight: 700; margin: 30px 0 12px; }
    .post-body h4 { color: var(--text-white); font-size: 1.1rem; font-weight: 600; margin: 24px 0 10px; }
    .post-body p { margin-bottom: 18px; }
    .post-body ul, .post-body ol { margin: 16px 0 16px 28px; }
    .post-body li { margin-bottom: 8px; }
    .post-body a { color: var(--primary); text-decoration: underline; }
    .post-body a:hover { color: var(--accent); }
    .post-body strong { color: var(--text-white); font-weight: 600; }
    .post-body img {
        max-width: 100%;
        border-radius: 12px;
        margin: 24px 0;
        border: 1px solid var(--border-color);
    }
    .post-body blockquote {
        border-left: 4px solid var(--primary);
        padding: 14px 24px;
        margin: 28px 0;
        background: rgba(59,130,246,0.05);
        border-radius: 8px;
        color: var(--text-white);
        font-style: italic;
    }
    .post-body code {
        background: rgba(255,255,255,0.06);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.9em;
        color: #a5b4fc;
    }
    .post-body pre {
        background: #0f1629;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 18px 22px;
        overflow-x: auto;
        margin: 24px 0;
    }

    /* Share bar */
    .share-bar {
        max-width: 780px;
        margin: 0 auto 60px;
        padding: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
        flex-wrap: wrap;
    }
    .share-bar-label {
        font-size: 0.88rem;
        color: var(--text-muted);
        font-weight: 600;
    }
    .share-buttons { display: flex; gap: 10px; }
    .share-btn {
        width: 38px; height: 38px;
        border-radius: 50%;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-light);
        transition: all 0.2s;
    }
    .share-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); transform: translateY(-2px); }
    .share-btn svg { width: 16px; height: 16px; }

    /* Related */
    .related-section {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px 80px;
    }
    .related-section h2 {
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: 28px;
        color: var(--text-white);
    }
    .related-section h2 .gradient-text {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .related-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }
    .related-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        overflow: hidden;
        transition: all 0.3s;
    }
    .related-card:hover { border-color: rgba(59,130,246,0.3); transform: translateY(-4px); }
    .related-card img {
        width: 100%; height: 160px;
        object-fit: cover;
    }
    .related-card-body { padding: 18px 20px; }
    .related-card h4 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-white);
        line-height: 1.4;
        margin-bottom: 8px;
    }
    .related-card .meta {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    @media (max-width: 768px) {
        .post-hero     { padding: 100px 20px 30px; }
        .post-hero h1  { font-size: 1.75rem; }
        .post-body     { padding: 40px 20px 60px; font-size: 0.95rem; }
        .related-grid  { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

<section class="post-hero">
    <div class="container">
        @if($post->category)
            <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="post-cat">{{ $post->category->name }}</a>
        @endif
        <h1>{{ $post->title }}</h1>
        <div class="post-meta">
            @if($post->author)
                <div class="post-meta-item">
                    <img src="{{ $post->author->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($post->author->name) }}" class="post-author-img">
                    <span>{{ $post->author->name }}</span>
                </div>
                <span>·</span>
            @endif
            <div class="post-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>{{ $post->published_at?->format('F j, Y') }}</span>
            </div>
            <span>·</span>
            <div class="post-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>{{ $post->readingMinutes() }} min read</span>
            </div>
            <span>·</span>
            <div class="post-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <span>{{ number_format($post->views_count) }} views</span>
            </div>
        </div>
    </div>
</section>

@if($post->featured_image)
<div class="post-featured">
    <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}">
</div>
@endif

<article class="post-body">
    {!! $post->content !!}
</article>

{{-- Share bar --}}
<div class="share-bar">
    <div class="share-bar-label">Share this article</div>
    <div class="share-buttons">
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="share-btn" title="Facebook">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
        </a>
        <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($post->title) }}" target="_blank" class="share-btn" title="Twitter / X">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
        </a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}" target="_blank" class="share-btn" title="LinkedIn">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
        </a>
        <button type="button" class="share-btn" title="Copy link" onclick="navigator.clipboard.writeText(window.location.href);this.innerHTML='<svg viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;3&quot;><polyline points=&quot;20 6 9 17 4 12&quot;/></svg>';setTimeout(()=>location.reload(),1000);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        </button>
    </div>
</div>

{{-- Related posts --}}
@if($related->isNotEmpty())
<section class="related-section">
    <h2>Related <span class="gradient-text">Articles</span></h2>
    <div class="related-grid">
        @foreach($related as $rel)
            <a href="{{ route('blog.show', $rel) }}" class="related-card" style="text-decoration:none;">
                <img src="{{ $rel->featuredImageUrl() }}" alt="">
                <div class="related-card-body">
                    @if($rel->category)
                        <div class="meta" style="color:#a5b4fc;margin-bottom:6px;">{{ $rel->category->name }}</div>
                    @endif
                    <h4>{{ \Illuminate\Support\Str::limit($rel->title, 70) }}</h4>
                    <div class="meta">{{ $rel->published_at?->format('M j, Y') }} · {{ $rel->readingMinutes() }} min read</div>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif

@endsection
