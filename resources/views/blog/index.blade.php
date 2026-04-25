@extends('layouts.public')

@section('title', 'Blog - ' . config('app.name', 'Khadija'))

@push('styles')
<style>
    /* ── Blog list page ── */
    .blog-hero {
        padding: 180px 0 70px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .blog-hero-bg {
        position: absolute;
        inset: 0;
        z-index: 0;
    }
    .blog-hero-bg img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.22;
    }
    .blog-hero-bg::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(11,15,26,0.65) 0%, rgba(11,15,26,0.92) 80%, var(--bg-dark) 100%);
    }
    .blog-hero .container { position: relative; z-index: 1; }
    .blog-hero::before {
        content: '';
        position: absolute;
        top: -40%; left: 50%; transform: translateX(-50%);
        width: 700px; height: 700px;
        background: radial-gradient(circle, rgba(59,130,246,0.12), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
        z-index: 1;
    }
    .blog-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        background: rgba(59,130,246,0.12);
        border: 1px solid rgba(59,130,246,0.25);
        border-radius: 50px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #a5b4fc;
        margin-bottom: 20px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .blog-hero-badge svg { width: 14px; height: 14px; }
    .blog-hero h1 {
        font-size: 3rem;
        font-weight: 800;
        letter-spacing: -1px;
        margin-bottom: 12px;
    }
    .blog-hero h1 .gradient-text {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end), #c084fc);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .blog-hero p {
        color: var(--text-muted);
        font-size: 1.1rem;
        max-width: 580px;
        margin: 0 auto 32px;
    }
    .blog-search {
        max-width: 620px;
        margin: 0 auto;
        position: relative;
    }
    .blog-search input {
        width: 100%;
        padding: 14px 20px 14px 50px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        color: var(--text-white);
        font-size: 1rem;
        font-family: inherit;
        outline: none;
    }
    .blog-search input::placeholder { color: var(--text-muted); }
    .blog-search input:focus { border-color: var(--primary); }
    .blog-search-icon {
        position: absolute;
        left: 18px; top: 50%; transform: translateY(-50%);
        color: var(--text-muted);
    }
    .blog-search-icon svg { width: 20px; height: 20px; }

    /* Category pills */
    .blog-categories {
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
        margin: 28px auto 48px;
    }
    .blog-cat-pill {
        padding: 8px 18px;
        border-radius: 50px;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text-muted);
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        transition: all 0.2s;
    }
    .blog-cat-pill:hover { border-color: rgba(59,130,246,0.3); color: var(--text-white); }
    .blog-cat-pill.active {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        border-color: transparent;
    }
    .blog-cat-count {
        display: inline-block;
        margin-left: 4px;
        font-size: 0.72rem;
        opacity: 0.7;
    }

    /* Grid */
    .blog-section { padding: 0 0 80px; }
    .blog-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 28px;
    }

    .blog-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s;
        display: flex;
        flex-direction: column;
    }
    .blog-card:hover {
        border-color: rgba(59,130,246,0.3);
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.3);
    }
    .blog-card-img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        display: block;
    }
    .blog-card-body {
        padding: 22px 24px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .blog-card-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-bottom: 12px;
    }
    .blog-card-cat {
        display: inline-block;
        padding: 3px 10px;
        background: rgba(99,102,241,0.15);
        color: #a5b4fc;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .blog-card h3 {
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1.4;
        margin-bottom: 10px;
        color: var(--text-white);
    }
    .blog-card p {
        color: var(--text-muted);
        font-size: 0.88rem;
        line-height: 1.6;
        margin-bottom: 16px;
        flex: 1;
    }
    .blog-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 16px;
        border-top: 1px solid var(--border-color);
        font-size: 0.78rem;
        color: var(--text-muted);
    }
    .blog-card-read {
        color: var(--primary);
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .blog-card-read svg { width: 14px; height: 14px; }

    /* Featured strip */
    .featured-strip {
        max-width: 1200px;
        margin: 0 auto 56px;
        padding: 0 24px;
    }
    .featured-strip h2 {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-white);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .featured-strip h2 svg { width: 18px; height: 18px; color: #f59e0b; }
    .featured-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .featured-item {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        padding: 14px;
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        transition: all 0.2s;
    }
    .featured-item:hover { border-color: rgba(59,130,246,0.3); }
    .featured-item img {
        width: 80px;
        height: 70px;
        object-fit: cover;
        border-radius: 8px;
        flex-shrink: 0;
    }
    .featured-item h4 {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--text-white);
        line-height: 1.4;
        margin-bottom: 4px;
    }
    .featured-item .meta {
        font-size: 0.72rem;
        color: var(--text-muted);
    }

    /* Pagination style */
    .blog-pagination {
        margin-top: 60px;
        display: flex;
        justify-content: center;
    }
    .blog-pagination nav > div { display: flex; gap: 6px; }
    .blog-pagination a, .blog-pagination span {
        padding: 8px 14px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        color: var(--text-light);
        border-radius: 8px;
        font-size: 0.85rem;
        text-decoration: none;
    }
    .blog-pagination .active > span {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        border-color: transparent;
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: var(--text-muted);
    }
    .empty-state svg {
        width: 64px;
        height: 64px;
        opacity: 0.3;
        margin-bottom: 16px;
    }

    @media (max-width: 992px) {
        .blog-grid     { grid-template-columns: repeat(2, 1fr); }
        .featured-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .blog-hero h1  { font-size: 2rem; }
        .blog-hero     { padding: 140px 0 40px; }
        .blog-grid     { grid-template-columns: 1fr; }
        .featured-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

<!-- ─── HERO ───────────────────────────────── -->
<section class="blog-hero">
    <div class="blog-hero-bg">
        <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=1600&q=80&auto=format&fit=crop" alt="Writing on laptop" loading="eager">
    </div>
    <div class="container">
        <span class="blog-hero-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            The GigResource Blog
        </span>
        <h1>Latest <span class="gradient-text">Insights</span></h1>
        <p>Stories, tips, and updates from the {{ config('app.name', 'Khadija') }} community.</p>

        <form method="GET" action="{{ route('blog.index') }}" class="blog-search">
            <span class="blog-search-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" name="q" placeholder="Search articles..." value="{{ request('q') }}">
        </form>

        {{-- Categories --}}
        @if($categories->isNotEmpty())
        <div class="blog-categories">
            <a href="{{ route('blog.index') }}" class="blog-cat-pill {{ !request('category') ? 'active' : '' }}">All Posts</a>
            @foreach($categories as $cat)
                <a href="{{ route('blog.index', ['category' => $cat->slug]) }}"
                   class="blog-cat-pill {{ request('category') === $cat->slug ? 'active' : '' }}">
                    {{ $cat->name }}
                    @if($cat->posts_count > 0)
                        <span class="blog-cat-count">({{ $cat->posts_count }})</span>
                    @endif
                </a>
            @endforeach
        </div>
        @endif
    </div>
</section>

<!-- ─── FEATURED (most viewed) ───────────────────────────────── -->
@if($featured->isNotEmpty() && !request('q') && !request('category'))
<section class="featured-strip">
    <h2>
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        Most Popular
    </h2>
    <div class="featured-grid">
        @foreach($featured as $f)
            <a href="{{ route('blog.show', $f) }}" class="featured-item">
                <img src="{{ $f->featuredImageUrl() }}" alt="">
                <div>
                    <h4>{{ \Illuminate\Support\Str::limit($f->title, 65) }}</h4>
                    <div class="meta">{{ number_format($f->views_count) }} views · {{ $f->published_at?->format('M j, Y') }}</div>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif

<!-- ─── POSTS GRID ───────────────────────────────── -->
<section class="blog-section">
    <div class="container">
        @if($posts->isNotEmpty())
            <div class="blog-grid">
                @foreach($posts as $post)
                    <article class="blog-card">
                        <a href="{{ route('blog.show', $post) }}">
                            <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="blog-card-img">
                        </a>
                        <div class="blog-card-body">
                            <div class="blog-card-meta">
                                @if($post->category)
                                    <span class="blog-card-cat">{{ $post->category->name }}</span>
                                @endif
                                <span>·</span>
                                <span>{{ $post->readingMinutes() }} min read</span>
                            </div>
                            <h3><a href="{{ route('blog.show', $post) }}" style="color:inherit;">{{ $post->title }}</a></h3>
                            <p>{{ \Illuminate\Support\Str::limit($post->excerpt ?? strip_tags($post->content), 130) }}</p>
                            <div class="blog-card-footer">
                                <span>{{ $post->published_at?->format('M j, Y') ?? $post->created_at->format('M j, Y') }}</span>
                                <a href="{{ route('blog.show', $post) }}" class="blog-card-read">
                                    Read more
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="blog-pagination">
                {{ $posts->links() }}
            </div>
        @else
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <h3 style="color: var(--text-white); margin-bottom: 8px;">No articles found</h3>
                <p>
                    @if(request('q') || request('category'))
                        Try adjusting your filters or <a href="{{ route('blog.index') }}" style="color: var(--primary);">view all posts</a>.
                    @else
                        Check back soon for new content.
                    @endif
                </p>
            </div>
        @endif
    </div>
</section>

@endsection
