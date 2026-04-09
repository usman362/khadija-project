@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-bs5.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
    /* ── Summernote base ── */
    .note-editor.note-frame {
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-radius: 8px;
        overflow: hidden;
    }
    .note-editor .note-toolbar {
        background: var(--bs-tertiary-bg, #f8f9fa);
        border-bottom: 1px solid var(--bs-border-color, #dee2e6);
        padding: 8px 12px;
    }
    .note-editor .note-editing-area .note-editable {
        background: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
        min-height: 400px;
        padding: 20px;
        font-size: 0.95rem;
        line-height: 1.7;
    }
    .note-editor .note-statusbar {
        background: var(--bs-tertiary-bg, #f8f9fa);
        border-top: 1px solid var(--bs-border-color, #dee2e6);
    }

    /* ── Dark theme support ── */
    [data-bs-theme="dark"] .note-editor .note-toolbar { background: #1a1d21; }
    [data-bs-theme="dark"] .note-editor .note-editing-area .note-editable { background: #212529; color: #e9ecef; }
    [data-bs-theme="dark"] .note-editor .note-statusbar { background: #1a1d21; }
    [data-bs-theme="dark"] .note-editor.note-frame { border-color: #373b3e; }
    [data-bs-theme="dark"] .note-editor .note-toolbar { border-bottom-color: #373b3e; }
    [data-bs-theme="dark"] .note-btn { background: #2b3035; border-color: #373b3e; color: #e9ecef; }
    [data-bs-theme="dark"] .note-btn:hover { background: #353a40; }
    [data-bs-theme="dark"] .note-dropdown-menu { background: #212529; border-color: #373b3e; }
    [data-bs-theme="dark"] .note-dropdown-item { color: #e9ecef; }
    [data-bs-theme="dark"] .note-dropdown-item:hover { background: #2b3035; }
    [data-bs-theme="dark"] .note-modal-content { background: #212529; color: #e9ecef; }
    [data-bs-theme="dark"] .note-modal-content .form-control { background: #2b3035; border-color: #373b3e; color: #e9ecef; }

    /* ── Featured image preview ── */
    .featured-preview {
        width: 100%;
        height: 220px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px dashed var(--bs-border-color, #dee2e6);
    }
    .featured-placeholder {
        width: 100%;
        height: 220px;
        border: 2px dashed var(--bs-border-color, #dee2e6);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bs-secondary-color, #6c757d);
    }
</style>
@endpush

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        {{-- Main --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control form-control-lg" required maxlength="255"
                           value="{{ old('title', $post->title ?? '') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" maxlength="255" placeholder="(auto-generated from title if empty)"
                           value="{{ old('slug', $post->slug ?? '') }}">
                    <div class="form-text small">Leave blank to auto-generate. Use only lowercase letters, numbers, and hyphens.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Excerpt</label>
                    <textarea name="excerpt" class="form-control" rows="2" maxlength="500" placeholder="Short summary for listings...">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
                </div>

                <div class="mb-0">
                    <label class="form-label">Content *</label>
                    <textarea name="content" id="content" class="form-control" rows="12">{{ old('content', $post->content ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- SEO --}}
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">SEO</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" maxlength="255"
                           value="{{ old('meta_title', $post->meta_title ?? '') }}">
                    <div class="form-text small">Leave blank to use post title.</div>
                </div>
                <div class="mb-0">
                    <label class="form-label">Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="2" maxlength="300">{{ old('meta_description', $post->meta_description ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Status --}}
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Publishing</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select">
                        <option value="draft"     @selected(old('status', $post->status ?? 'draft') === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $post->status ?? '') === 'published')>Published</option>
                        <option value="archived"  @selected(old('status', $post->status ?? '') === 'archived')>Archived</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label">Publish Date</label>
                    <input type="datetime-local" name="published_at" class="form-control"
                           value="{{ old('published_at', isset($post, $post->published_at) ? $post->published_at->format('Y-m-d\TH:i') : '') }}">
                    <div class="form-text small">Leave blank to publish now.</div>
                </div>
            </div>
        </div>

        {{-- Category --}}
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Category</h6></div>
            <div class="card-body">
                <select name="blog_category_id" class="form-select">
                    <option value="">— Uncategorized —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int)old('blog_category_id', $post->blog_category_id ?? 0) === $cat->id)>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @if($categories->isEmpty())
                    <div class="form-text small text-warning mt-2">
                        No categories yet. <a href="{{ route('app.admin.blog.categories.index') }}">Create one</a>.
                    </div>
                @endif
            </div>
        </div>

        {{-- Featured Image --}}
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Featured Image</h6></div>
            <div class="card-body">
                @if(isset($post) && $post->featured_image)
                    <img src="{{ $post->featuredImageUrl() }}" class="featured-preview mb-2" id="imgPreview">
                    <div class="form-check mb-2">
                        <input type="checkbox" name="remove_image" value="1" class="form-check-input" id="removeImg">
                        <label class="form-check-label small" for="removeImg">Remove current image</label>
                    </div>
                @else
                    <div class="featured-placeholder mb-2" id="imgPlaceholder">
                        <div class="text-center">
                            <i data-lucide="image" style="width:32px;height:32px;"></i>
                            <div class="small mt-1">No image</div>
                        </div>
                    </div>
                @endif
                <input type="file" name="featured_image" class="form-control" accept="image/*" onchange="previewImage(this)">
                <div class="form-text small">JPG, PNG, WebP. Max 4MB.</div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="card">
            <div class="card-body d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i data-lucide="save" style="width:16px;height:16px;"></i>
                    {{ isset($post) ? 'Update Post' : 'Create Post' }}
                </button>
                <a href="{{ route('app.admin.blog.posts.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-bs5.min.js"></script>
<script>
    $(document).ready(function () {
        $('#content').summernote({
            height: 400,
            placeholder: 'Write your post content here...',
            tabsize: 2,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'hr']],
                ['view', ['fullscreen', 'codeview', 'help']],
            ],
            styleTags: [
                'p',
                { title: 'Heading 2',  tag: 'h2', className: '', value: 'h2' },
                { title: 'Heading 3',  tag: 'h3', className: '', value: 'h3' },
                { title: 'Heading 4',  tag: 'h4', className: '', value: 'h4' },
                { title: 'Blockquote', tag: 'blockquote', className: '', value: 'blockquote' },
            ],
            fontSizes: ['12', '14', '16', '18', '20', '24', '28', '32', '36'],
        });
    });

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                const preview = document.getElementById('imgPreview');
                const placeholder = document.getElementById('imgPlaceholder');
                if (preview) {
                    preview.src = e.target.result;
                } else if (placeholder) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'featured-preview mb-2';
                    img.id = 'imgPreview';
                    placeholder.replaceWith(img);
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush
