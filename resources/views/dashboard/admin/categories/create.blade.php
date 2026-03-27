@extends('layouts.dashboard')

@section('title', 'Add Categories')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1"><i data-lucide="grid-3x3" class="me-2" style="width:24px;height:24px;"></i> Categories</h4>
            <p class="text-secondary mb-0">Add Categories</p>
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
                    <h6 class="card-title mb-4">Add New Category</h6>
                    <form method="POST" action="{{ route('app.admin.categories.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Cover Image --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Cover Image</label>
                            <input type="file" name="cover_image" class="form-control @error('cover_image') is-invalid @enderror"
                                   accept="image/*" onchange="previewImage(this, 'coverPreview')">
                            @error('cover_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Thumbnail Image --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Thumbnail Image</label>
                            <input type="file" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror"
                                   accept="image/*" onchange="previewImage(this, 'thumbPreview')">
                            @error('thumbnail')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Parent Category --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Parent Category</label>
                            <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                                <option value="">-- None --</option>
                                @foreach($parentCategories as $cat)
                                    <option value="{{ $cat['id'] }}" {{ old('parent_id') == $cat['id'] ? 'selected' : '' }}>
                                        {{ $cat['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Icon --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Icon (Lucide)</label>
                            <select name="icon" class="form-select @error('icon') is-invalid @enderror">
                                <option value="">-- Select an icon --</option>
                                @foreach(['layers','calendar','music','camera','utensils','palette','mic','gift','heart','star','award','briefcase','home','map-pin','users','truck','flower-2','cake','sparkles','party-popper','wine','tent','clapperboard','megaphone','lightbulb'] as $ico)
                                    <option value="{{ $ico }}" {{ old('icon') === $ico ? 'selected' : '' }}>{{ $ico }}</option>
                                @endforeach
                            </select>
                            <small class="text-secondary">Pick an icon for the category.</small>
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Title --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Title</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="Birthday Parties, Weddings, Corporate Event, Food Truck Festival"
                                   required oninput="updatePreviewTitle(this.value)">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Short Description --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Short Description</label>
                            <input type="text" name="short_description" class="form-control @error('short_description') is-invalid @enderror"
                                   value="{{ old('short_description') }}" placeholder="Find photographers, caterers, and planners for your special day.(Optional)"
                                   oninput="updatePreviewShort(this.value)">
                            @error('short_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Long Description --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Long Description</label>
                            <textarea name="long_description" class="form-control @error('long_description') is-invalid @enderror"
                                      rows="6" placeholder="Detailed description about this category..."
                                      oninput="updatePreviewLong(this.value)">{{ old('long_description') }}</textarea>
                            @error('long_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Sort Order & Active --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                           id="isActive" {{ old('is_active', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">Active</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save" class="icon-sm me-1"></i> Save Category
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: Live Preview --}}
        <div class="col-lg-5">
            <div class="card" style="position: sticky; top: 90px;">
                <div class="card-body">
                    <h6 class="card-title mb-3">Live Preview</h6>

                    {{-- Preview Cover --}}
                    <div id="coverPreview" style="height: 150px; border-radius: 10px; overflow: hidden; background: linear-gradient(135deg, #1e3a5f, #2d1b69); margin-bottom: 16px; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="image" style="width:40px;height:40px;opacity:0.3;"></i>
                    </div>

                    <h5 id="previewTitle" class="text-primary mb-1">Enter Category Title</h5>
                    <p id="previewShort" class="text-secondary mb-2" style="font-size: 0.85rem;">A Little Short Description would be fun.</p>
                    <p id="previewLong" class="text-secondary" style="font-size: 0.85rem; line-height: 1.6;">Long description to display details about the Category</p>
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
        document.getElementById('previewShort').textContent = val || 'A Little Short Description would be fun.';
    }

    function updatePreviewLong(val) {
        document.getElementById('previewLong').textContent = val || 'Long description to display details about the Category';
    }
</script>
@endpush
@endsection
