@extends('layouts.dashboard')
@section('title', 'Edit Blog Post')
@section('content')

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div class="d-flex align-items-center">
        <a href="{{ route('app.admin.blog.posts.index') }}" class="btn btn-sm btn-outline-secondary me-3">
            <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back
        </a>
        <h4 class="mb-0"><i data-lucide="edit" class="me-2" style="width:22px;height:22px;"></i> Edit Blog Post</h4>
    </div>

    <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-{{ $post->statusColor() }}">{{ $post->statusLabel() }}</span>
        @if($post->isPublished())
            <a href="{{ route('blog.show', $post) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i data-lucide="external-link" style="width:14px;height:14px;"></i> View
            </a>
        @endif
    </div>
</div>

<form action="{{ route('app.admin.blog.posts.update', $post) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PATCH')
    @include('dashboard.admin.blog.posts._form')
</form>

@endsection
