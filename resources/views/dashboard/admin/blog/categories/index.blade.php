@extends('layouts.dashboard')
@section('title', 'Blog Categories')
@section('content')

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="folder" class="me-2" style="width:24px;height:24px;"></i> Blog Categories</h4>
        <p class="text-secondary mb-0">Organize your blog posts into categories.</p>
    </div>
    <a href="{{ route('app.admin.blog.posts.index') }}" class="btn btn-outline-primary">
        <i data-lucide="file-text" style="width:16px;height:16px;"></i> View Posts
    </a>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">
    {{-- Create Form --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Add Category</h5></div>
            <div class="card-body">
                <form action="{{ route('app.admin.blog.categories.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required maxlength="120" value="{{ old('name') }}">
                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" maxlength="500">{{ old('description') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0" max="9999">
                    </div>
                    <div class="form-check mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i data-lucide="plus" style="width:16px;height:16px;"></i> Add Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- List --}}
    <div class="col-md-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Posts</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                        <tr>
                            <td>
                                <strong>{{ $category->name }}</strong>
                                @if($category->description)
                                    <div class="small text-secondary">{{ Str::limit($category->description, 60) }}</div>
                                @endif
                            </td>
                            <td><code class="small">{{ $category->slug }}</code></td>
                            <td>{{ $category->posts_count }}</td>
                            <td>{{ $category->sort_order }}</td>
                            <td>
                                @if($category->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editCat{{ $category->id }}">
                                    <i data-lucide="edit" style="width:14px;height:14px;"></i>
                                </button>
                                <form action="{{ route('app.admin.blog.categories.destroy', $category) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this category? Posts will become uncategorized.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        {{-- Edit modal --}}
                        <div class="modal fade" id="editCat{{ $category->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <form action="{{ route('app.admin.blog.categories.update', $category) }}" method="POST" class="modal-content">
                                    @csrf @method('PATCH')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Category</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Name *</label>
                                            <input type="text" name="name" class="form-control" required value="{{ $category->name }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3">{{ $category->description }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Sort Order</label>
                                            <input type="number" name="sort_order" class="form-control" value="{{ $category->sort_order }}" min="0">
                                        </div>
                                        <div class="form-check">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active_{{ $category->id }}" @checked($category->is_active)>
                                            <label class="form-check-label" for="is_active_{{ $category->id }}">Active</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">No categories yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $categories->links() }}</div>
    </div>
</div>
@endsection
