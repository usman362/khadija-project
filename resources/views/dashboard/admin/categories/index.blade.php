@extends('layouts.dashboard')

@section('title', 'Categories')

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1"><i data-lucide="layers" class="me-2" style="width:24px;height:24px;"></i> Categories</h4>
            <p class="text-secondary mb-0">Manage Categories</p>
        </div>
        <a href="{{ route('app.admin.categories.create') }}" class="btn btn-primary btn-icon-text">
            <i class="btn-icon-prepend" data-lucide="plus"></i> Add Categories
        </a>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Left: Category Tree --}}
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Categories</h6>
                    @if($treeCategories->count())
                        <div class="category-tree" style="max-height: 500px; overflow-y: auto;">
                            @include('dashboard.admin.categories._tree_item', ['categories' => $treeCategories, 'depth' => 0])
                        </div>
                    @else
                        <p class="text-secondary mb-0">No categories yet.</p>
                    @endif
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Quick Stats</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Total Categories</span>
                        <span class="badge bg-primary">{{ $stats['total'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Active</span>
                        <span class="badge bg-success">{{ $stats['active'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Inactive</span>
                        <span class="badge bg-danger">{{ $stats['inactive'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Main Categories</span>
                        <span class="badge bg-info">{{ $stats['parents'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">Subcategories</span>
                        <span class="badge bg-warning">{{ $stats['subcategories'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Category Cards Grid --}}
        <div class="col-lg-8">
            @if($categories->count())
                <div class="row">
                    @foreach($categories as $category)
                    <div class="col-md-6 col-xl-4 mb-4">
                        <div class="card h-100">
                            {{-- Cover Image --}}
                            <div style="height: 160px; overflow: hidden; position: relative; background: linear-gradient(135deg, #1e3a5f, #2d1b69); border-radius: 0.5rem 0.5rem 0 0;">
                                @if($category->cover_image)
                                    <img src="{{ asset('storage/' . $category->cover_image) }}" alt="{{ $category->name }}"
                                         style="width:100%; height:100%; object-fit:cover;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center h-100">
                                        <i data-lucide="{{ $category->icon ?: 'layers' }}" style="width:48px;height:48px;opacity:0.3;"></i>
                                    </div>
                                @endif
                                {{-- Status Badge --}}
                                <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-danger' }}"
                                      style="position:absolute; bottom:8px; right:8px;">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            <div class="card-body pb-2">
                                {{-- Parent Name --}}
                                @if($category->parent)
                                    <small class="text-secondary">{{ $category->parent->name }}</small>
                                @else
                                    <small class="text-secondary">Main Category</small>
                                @endif

                                <h6 class="fw-bold mt-1 mb-1">{{ $category->name }}</h6>

                                @if($category->short_description)
                                    <p class="text-secondary mb-2" style="font-size: 0.8rem; line-height: 1.5;">
                                        {{ Str::limit($category->short_description, 80) }}
                                    </p>
                                @endif

                                {{-- Action Buttons Row 1 --}}
                                <div class="d-flex gap-1 flex-wrap mb-2">
                                    <a href="{{ route('app.admin.categories.edit', $category) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i data-lucide="edit-2" style="width:12px;height:12px;"></i> Edit
                                    </a>
                                </div>

                                {{-- Action Buttons Row 2 --}}
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($category->is_active)
                                        <form method="POST" action="{{ route('app.admin.categories.update', $category) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="name" value="{{ $category->name }}">
                                            <input type="hidden" name="is_active" value="0">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <i data-lucide="pause-circle" style="width:12px;height:12px;"></i> Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('app.admin.categories.update', $category) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="name" value="{{ $category->name }}">
                                            <input type="hidden" name="is_active" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i data-lucide="play-circle" style="width:12px;height:12px;"></i> Activate
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('app.admin.categories.destroy', $category) }}"
                                          onsubmit="return confirm('Delete this category?');" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i data-lucide="trash-2" style="width:12px;height:12px;"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-2">{{ $categories->links() }}</div>
            @else
                <div class="alert alert-warning">
                    <i data-lucide="info" class="icon-sm me-1"></i>
                    No categories found matching your criteria.
                </div>
            @endif
        </div>
    </div>
@endsection
