@extends('layouts.dashboard')

@section('title', 'Edit Category')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1"><i data-lucide="edit" class="me-2" style="width:24px;height:24px;"></i> Edit Category</h4>
            <p class="text-secondary mb-0">{{ $category->name }}</p>
        </div>
        <a href="{{ route('app.admin.categories.index') }}" class="btn btn-outline-primary btn-icon-text">
            View All Categories
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Left: Form --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-4">Edit Category</h6>
                    <form method="POST" action="{{ route('app.admin.categories.update', $category) }}" enctype="multipart/form-data">
                        @csrf @method('PATCH')

                        {{-- Cover Image --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Cover Image</label>
                            @if($category->cover_image)
                                <div class="mb-2" style="height:100px; width:200px; border-radius:8px; overflow:hidden;">
                                    <img src="{{ asset('storage/' . $category->cover_image) }}" style="width:100%;height:100%;object-fit:cover;">
                                </div>
                            @endif
                            <input type="file" name="cover_image" class="form-control @error('cover_image') is-invalid @enderror"
                                   accept="image/*" onchange="previewImage(this, 'coverPreview')">
                            @error('cover_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Thumbnail Image --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Thumbnail Image</label>
                            @if($category->thumbnail)
                                <div class="mb-2" style="height:80px; width:80px; border-radius:8px; overflow:hidden;">
                                    <img src="{{ asset('storage/' . $category->thumbnail) }}" style="width:100%;height:100%;object-fit:cover;">
                                </div>
                            @endif
                            <input type="file" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror"
                                   accept="image/*">
                            @error('thumbnail')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Parent Category --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Parent Category</label>
                            <select name="parent_id" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($parentCategories as $cat)
                                    <option value="{{ $cat['id'] }}" {{ old('parent_id', $category->parent_id) == $cat['id'] ? 'selected' : '' }}>
                                        {{ $cat['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Icon --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Icon (Lucide)</label>
                            <select name="icon" class="form-select">
                                <option value="">-- Select an icon --</option>
                                @foreach(['layers','calendar','music','camera','utensils','palette','mic','gift','heart','star','award','briefcase','home','map-pin','users','truck','flower-2','cake','sparkles','party-popper','wine','tent','clapperboard','megaphone','lightbulb'] as $ico)
                                    <option value="{{ $ico }}" {{ old('icon', $category->icon) === $ico ? 'selected' : '' }}>{{ $ico }}</option>
                                @endforeach
                            </select>
                            <small class="text-secondary">Pick an icon for the category.</small>
                        </div>

                        {{-- Title --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Title</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $category->name) }}" required
                                   oninput="updatePreviewTitle(this.value)">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Short Description --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Short Description</label>
                            <input type="text" name="short_description" class="form-control"
                                   value="{{ old('short_description', $category->short_description) }}"
                                   oninput="updatePreviewShort(this.value)">
                        </div>

                        {{-- Long Description --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Long Description</label>
                            <textarea name="long_description" class="form-control" rows="6"
                                      oninput="updatePreviewLong(this.value)">{{ old('long_description', $category->long_description) }}</textarea>
                        </div>

                        {{-- Sort Order & Active --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control"
                                       value="{{ old('sort_order', $category->sort_order) }}" min="0">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                           id="isActive" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">Active</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save" class="icon-sm me-1"></i> Update Category
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: Live Preview + Info --}}
        <div class="col-lg-5">
            <div class="card" style="position: sticky; top: 90px;">
                <div class="card-body">
                    <h6 class="card-title mb-3">Live Preview</h6>

                    <div id="coverPreview" style="height: 150px; border-radius: 10px; overflow: hidden; background: linear-gradient(135deg, #1e3a5f, #2d1b69); margin-bottom: 16px;">
                        @if($category->cover_image)
                            <img src="{{ asset('storage/' . $category->cover_image) }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i data-lucide="image" style="width:40px;height:40px;opacity:0.3;"></i>
                            </div>
                        @endif
                    </div>

                    <h5 id="previewTitle" class="text-primary mb-1">{{ $category->name }}</h5>
                    <p id="previewShort" class="text-secondary mb-2" style="font-size: 0.85rem;">{{ $category->short_description ?: 'No short description' }}</p>
                    <p id="previewLong" class="text-secondary" style="font-size: 0.85rem; line-height: 1.6;">{{ $category->long_description ?: 'No long description' }}</p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Category Info</h6>
                    <table class="table table-sm mb-0">
                        <tr><td class="text-secondary">ID</td><td>{{ $category->id }}</td></tr>
                        <tr><td class="text-secondary">Slug</td><td><code>{{ $category->slug }}</code></td></tr>
                        <tr><td class="text-secondary">Created</td><td>{{ $category->created_at->format('M d, Y') }}</td></tr>
                        <tr><td class="text-secondary">Events</td><td>{{ $category->events()->count() }}</td></tr>
                        <tr><td class="text-secondary">Subcategories</td><td>{{ $category->children()->count() }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    function previewImage(input, targetId) {
        const target = document.getElementById(targetId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                target.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function updatePreviewTitle(val) {
        document.getElementById('previewTitle').textContent = val || 'Enter Category Title';
    }

    function updatePreviewShort(val) {
        document.getElementById('previewShort').textContent = val || 'No short description';
    }

    function updatePreviewLong(val) {
        document.getElementById('previewLong').textContent = val || 'No long description';
    }
</script>
@endpush
@endsection
