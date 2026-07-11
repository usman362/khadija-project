@extends('layouts.dashboard')

@section('title', 'Category detail')

@section('content')
    {{-- Header + breadcrumb --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1"><i data-lucide="layers" class="me-2" style="width:24px;height:24px;"></i> Category detail</h4>
            <p class="text-secondary mb-0">
                <a href="{{ route('app.admin.categories.index') }}" class="text-secondary text-decoration-none">Categories</a>
                <span class="mx-1">/</span>
                <span class="fw-bold">{{ $category->name }}</span>
            </p>
        </div>
        <a href="{{ route('app.admin.categories.index') }}" class="btn btn-outline-primary">View All Categories</a>
    </div>

    <div class="card">
        <div class="card-body">
            {{-- Cover banner --}}
            <div class="mb-4" style="height:220px; border-radius:12px; overflow:hidden; background:linear-gradient(135deg,#1e3a5f,#2d1b69); display:flex; align-items:center; justify-content:center;">
                @if($category->cover_image)
                    <img src="{{ asset('storage/' . $category->cover_image) }}" alt="{{ $category->name }}" style="width:100%; height:100%; object-fit:cover;">
                @else
                    <i data-lucide="image" style="width:56px; height:56px; opacity:.35;"></i>
                @endif
            </div>

            <div class="row g-4">
                {{-- Left: thumbnail --}}
                <div class="col-lg-5">
                    <div style="border:1px solid rgba(128,128,128,.2); border-radius:12px; overflow:hidden;">
                        @php($thumb = $category->thumbnail ?: $category->cover_image)
                        @if($thumb)
                            <img src="{{ asset('storage/' . $thumb) }}" alt="{{ $category->name }}" style="width:100%; height:320px; object-fit:cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center" style="height:320px;">
                                <i data-lucide="{{ $category->icon ?: 'folder' }}" style="width:56px;height:56px;opacity:.3;"></i>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right: info --}}
                <div class="col-lg-7">
                    @if($category->parent)
                        <a href="{{ route('app.admin.categories.show', $category->parent) }}" class="text-secondary small text-decoration-none">{{ $category->parent->name }}</a>
                    @else
                        <span class="text-secondary small">Main Category</span>
                    @endif
                    <h3 class="fw-bold mt-1 mb-2">{{ $category->name }}</h3>
                    @if($category->short_description)
                        <p class="text-secondary">{{ $category->short_description }}</p>
                    @endif

                    {{-- Stats --}}
                    <div class="d-flex gap-4 my-4 flex-wrap">
                        <div>
                            <div class="h3 fw-bold mb-0">{{ $stats['gigs'] }}</div>
                            <small class="text-secondary">Active Gigs</small>
                        </div>
                        <div>
                            <div class="h3 fw-bold mb-0">{{ $stats['events'] }}</div>
                            <small class="text-secondary">Active Events</small>
                        </div>
                        <div>
                            <div class="h3 fw-bold mb-0">{{ $stats['subcategories'] }}</div>
                            <small class="text-secondary">Subcategories</small>
                        </div>
                    </div>

                    @if($category->long_description)
                        <hr>
                        <h6 class="fw-bold">Category Description</h6>
                        <p class="text-secondary" style="line-height:1.7;">{{ $category->long_description }}</p>
                    @endif

                    {{-- Category info --}}
                    <hr>
                    <h6 class="fw-bold mb-3">Category Info</h6>
                    <div class="row mb-2">
                        <div class="col-4 text-secondary">Status:</div>
                        <div class="col-8">
                            <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-danger' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 text-secondary">Slug:</div>
                        <div class="col-8"><code>{{ $category->slug }}</code></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 text-secondary">Created:</div>
                        <div class="col-8">{{ $category->created_at?->format('M d, Y') ?? '—' }}</div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex gap-2 flex-wrap mt-4">
                        @if($category->children->count())
                            <a href="{{ route('app.admin.categories.index') }}" class="btn btn-outline-info btn-sm">
                                <i data-lucide="git-branch" class="me-1" style="width:15px;height:15px;"></i> {{ $category->children->count() }} Subcategories
                            </a>
                        @endif
                        <a href="{{ route('app.admin.categories.edit', $category) }}" class="btn btn-warning btn-sm">
                            <i data-lucide="edit" class="me-1" style="width:15px;height:15px;"></i> Edit Category
                        </a>
                        <form method="POST" action="{{ route('app.admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?');" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i data-lucide="trash-2" class="me-1" style="width:15px;height:15px;"></i> Delete Category
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
