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
                    <label for="content" class="form-label">Page Content <small class="text-secondary">(HTML supported)</small></label>
                    <textarea class="form-control" id="content" name="content" rows="25" style="font-family: monospace; font-size: 0.85rem;" required>{{ old('content', $policy->content) }}</textarea>
                    <div class="form-text">
                        You can use HTML tags like <code>&lt;h2&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;li&gt;</code>, <code>&lt;strong&gt;</code>, etc.
                    </div>
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
