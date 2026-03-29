@extends('layouts.dashboard')

@section('title', 'Policy Pages')

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1"><i data-lucide="file-text" class="me-2" style="width:24px;height:24px;"></i> Policy Pages</h4>
            <p class="text-secondary mb-0">Manage your website's policy pages (Privacy, Payment, Cancellation)</p>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        @foreach($policies as $policy)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="card-title mb-0">{{ $policy->title }}</h5>
                            @if($policy->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </div>
                        <p class="text-secondary small mb-3">
                            Slug: <code>/{{ $policy->slug }}</code>
                        </p>
                        <p class="text-secondary small mb-3">
                            Last updated: {{ $policy->updated_at->format('M d, Y h:i A') }}
                        </p>
                        <div class="d-flex gap-2">
                            <a href="{{ route('app.admin.policies.edit', $policy) }}" class="btn btn-primary btn-sm btn-icon-text">
                                <i class="btn-icon-prepend" data-lucide="edit-3"></i> Edit Content
                            </a>
                            <a href="{{ route($policy->slug) }}" target="_blank" class="btn btn-outline-secondary btn-sm btn-icon-text">
                                <i class="btn-icon-prepend" data-lucide="external-link"></i> View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
