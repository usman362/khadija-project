@extends('layouts.dashboard')
@section('title', 'Blog Posts')
@section('content')

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="file-text" class="me-2" style="width:24px;height:24px;"></i> Blog Posts</h4>
        <p class="text-secondary mb-0">Create and manage blog articles.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('app.admin.blog.categories.index') }}" class="btn btn-outline-primary">
            <i data-lucide="folder" style="width:16px;height:16px;"></i> Categories
        </a>
        <a href="{{ route('app.admin.blog.posts.create') }}" class="btn btn-primary">
            <i data-lucide="plus" style="width:16px;height:16px;"></i> New Post
        </a>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['label'=>'Total',     'value'=>$stats['total'],     'color'=>'primary',  'key'=>null],
        ['label'=>'Published', 'value'=>$stats['published'], 'color'=>'success',  'key'=>'published'],
        ['label'=>'Drafts',    'value'=>$stats['draft'],     'color'=>'secondary','key'=>'draft'],
        ['label'=>'Archived',  'value'=>$stats['archived'],  'color'=>'dark',     'key'=>'archived'],
    ] as $card)
    <div class="col-6 col-md-3">
        <a href="{{ $card['key'] ? route('app.admin.blog.posts.index', ['status'=>$card['key']]) : route('app.admin.blog.posts.index') }}" class="text-decoration-none">
            <div class="card border-{{ $card['color'] }}" style="background:rgba(0,0,0,0.02);">
                <div class="card-body">
                    <div class="h4 mb-0 text-{{ $card['color'] }}">{{ $card['value'] }}</div>
                    <div class="text-secondary small">{{ $card['label'] }}</div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <label class="form-label small text-secondary">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Title or excerpt..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-secondary">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="published" @selected(request('status') === 'published')>Published</option>
                    <option value="draft"     @selected(request('status') === 'draft')>Draft</option>
                    <option value="archived"  @selected(request('status') === 'archived')>Archived</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-secondary">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int)request('category') === $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width:80px;">Image</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Published</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($posts as $post)
                <tr>
                    <td>
                        <img src="{{ $post->featuredImageUrl() }}" alt="" style="width:60px;height:40px;object-fit:cover;border-radius:6px;">
                    </td>
                    <td>
                        <div><strong>{{ $post->title }}</strong></div>
                        <div class="small text-secondary">/{{ $post->slug }}</div>
                    </td>
                    <td>
                        @if($post->category)
                            <span class="badge bg-light text-dark">{{ $post->category->name }}</span>
                        @else
                            <span class="text-secondary small">—</span>
                        @endif
                    </td>
                    <td class="small">{{ $post->author?->name ?? '—' }}</td>
                    <td><span class="badge bg-{{ $post->statusColor() }}">{{ $post->statusLabel() }}</span></td>
                    <td class="small">{{ number_format($post->views_count) }}</td>
                    <td class="small">{{ $post->published_at?->format('M j, Y') ?? '—' }}</td>
                    <td class="text-end">
                        @if($post->isPublished())
                            <a href="{{ route('blog.show', $post) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="View">
                                <i data-lucide="external-link" style="width:14px;height:14px;"></i>
                            </a>
                        @endif
                        <a href="{{ route('app.admin.blog.posts.edit', $post) }}" class="btn btn-sm btn-outline-primary">
                            <i data-lucide="edit" style="width:14px;height:14px;"></i>
                        </a>
                        <form action="{{ route('app.admin.blog.posts.destroy', $post) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this post permanently?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-secondary">
                        <i data-lucide="file-text" style="width:48px;height:48px;opacity:0.3;"></i>
                        <div class="mt-3">No blog posts found. <a href="{{ route('app.admin.blog.posts.create') }}">Create your first post</a>.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $posts->links() }}</div>

@endsection
