@extends('layouts.dashboard')
@section('title', 'Create Blog Post')
@section('content')

<div class="d-flex align-items-center mb-4">
    <a href="{{ route('app.admin.blog.posts.index') }}" class="btn btn-sm btn-outline-secondary me-3">
        <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back
    </a>
    <h4 class="mb-0"><i data-lucide="plus-circle" class="me-2" style="width:22px;height:22px;"></i> Create Blog Post</h4>
</div>

<form action="{{ route('app.admin.blog.posts.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('dashboard.admin.blog.posts._form')
</form>

@endsection
