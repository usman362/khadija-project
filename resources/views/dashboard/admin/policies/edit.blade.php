@extends('layouts.dashboard')

@section('title', 'Edit ' . $policy->title)

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1"><i data-lucide="file-text" class="me-2" style="width:24px;height:24px;"></i> Edit {{ $policy->title }}</h4>
            <p class="text-secondary mb-0">Update the content displayed on the <code>/{{ $policy->slug }}</code> page</p>
        </div>
        <a href="{{ route('app.admin.policies.index') }}" class="btn btn-outline-secondary btn-sm btn-icon-text">
            <i class="btn-icon-prepend" data-lucide="arrow-left"></i> Back to Policies
        </a>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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

    {{-- Summernote CSS --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-bs5.min.css" rel="stylesheet">
    <style>
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
        /* Dark mode support for NobleUI */
        [data-bs-theme="dark"] .note-editor .note-toolbar {
            background: #1a1d21;
        }
        [data-bs-theme="dark"] .note-editor .note-editing-area .note-editable {
            background: #212529;
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .note-editor .note-statusbar {
            background: #1a1d21;
        }
        [data-bs-theme="dark"] .note-editor.note-frame {
            border-color: #373b3e;
        }
        [data-bs-theme="dark"] .note-editor .note-toolbar {
            border-bottom-color: #373b3e;
        }
        [data-bs-theme="dark"] .note-btn {
            background: #2b3035;
            border-color: #373b3e;
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .note-btn:hover {
            background: #353a40;
        }
        [data-bs-theme="dark"] .note-dropdown-menu {
            background: #212529;
            border-color: #373b3e;
        }
        [data-bs-theme="dark"] .note-dropdown-item {
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .note-dropdown-item:hover {
            background: #2b3035;
        }
        [data-bs-theme="dark"] .note-modal-content {
            background: #212529;
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .note-modal-content .form-control {
            background: #2b3035;
            border-color: #373b3e;
            color: #e9ecef;
        }
    </style>

    <form action="{{ route('app.admin.policies.update', $policy) }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Page Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $policy->title) }}" required>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Page Content</label>
                    <textarea class="form-control" id="content" name="content" required>{{ old('content', $policy->content) }}</textarea>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $policy->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active (visible on website)</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary btn-icon-text">
                <i class="btn-icon-prepend" data-lucide="save"></i> Save Changes
            </button>
            <a href="{{ route($policy->slug) }}" target="_blank" class="btn btn-outline-secondary btn-icon-text">
                <i class="btn-icon-prepend" data-lucide="external-link"></i> Preview Page
            </a>
        </div>
    </form>

@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-bs5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#content').summernote({
                height: 450,
                placeholder: 'Start writing your policy content here...',
                tabsize: 2,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'hr']],
                    ['view', ['fullscreen', 'codeview', 'help']],
                ],
                styleTags: [
                    'p',
                    { title: 'Heading 2', tag: 'h2', className: '', value: 'h2' },
                    { title: 'Heading 3', tag: 'h3', className: '', value: 'h3' },
                    { title: 'Heading 4', tag: 'h4', className: '', value: 'h4' },
                    { title: 'Blockquote', tag: 'blockquote', className: '', value: 'blockquote' },
                ],
                fontSizes: ['12', '14', '16', '18', '20', '24', '28', '32', '36'],
                callbacks: {
                    onInit: function() {
                        $('.note-editable').css({
                            'font-family': "'Inter', -apple-system, BlinkMacSystemFont, sans-serif"
                        });
                    }
                }
            });
        });
    </script>
@endpush
