@extends('layouts.dashboard')

@section('title', 'FAQ Management')

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1"><i data-lucide="help-circle" class="me-2" style="width:24px;height:24px;"></i> FAQ Management</h4>
            <p class="text-secondary mb-0">Manage frequently asked questions displayed on the landing page</p>
        </div>
        <button class="btn btn-primary btn-icon-text" data-bs-toggle="modal" data-bs-target="#addFaqModal">
            <i class="btn-icon-prepend" data-lucide="plus"></i> Add New FAQ
        </button>
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

    {{-- Quick Stats --}}
    <div class="row mb-4">
        <div class="col-md-4 col-6 mb-2">
            <div class="card bg-primary bg-opacity-10 border-0">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-primary">{{ $stats['total'] }}</h4>
                    <small class="text-secondary">Total FAQs</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6 mb-2">
            <div class="card bg-success bg-opacity-10 border-0">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-success">{{ $stats['active'] }}</h4>
                    <small class="text-secondary">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6 mb-2">
            <div class="card bg-danger bg-opacity-10 border-0">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-danger">{{ $stats['inactive'] }}</h4>
                    <small class="text-secondary">Inactive</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('app.admin.faqs.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Search FAQs...">
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill">Filter</button>
                    <a href="{{ route('app.admin.faqs.index') }}" class="btn btn-sm btn-outline-secondary flex-fill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- FAQ Table --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Question</th>
                        <th style="width:120px">Category</th>
                        <th style="width:80px" class="text-center">Order</th>
                        <th style="width:90px" class="text-center">Status</th>
                        <th style="width:150px" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($faqs as $faq)
                        <tr>
                            <td class="text-secondary">{{ $faq->id }}</td>
                            <td>
                                <strong>{{ $faq->question }}</strong>
                                <div class="text-secondary small mt-1" style="max-width:500px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ Str::limit(strip_tags($faq->answer), 100) }}
                                </div>
                            </td>
                            <td>
                                @if($faq->category)
                                    <span class="badge bg-info bg-opacity-10 text-info">{{ $faq->category }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $faq->sort_order }}</td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('app.admin.faqs.toggle', $faq) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="badge border-0 cursor-pointer {{ $faq->is_active ? 'bg-success' : 'bg-danger' }}" title="Click to toggle">
                                        {{ $faq->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editFaqModal{{ $faq->id }}" title="Edit">
                                    <i data-lucide="pencil" style="width:14px;height:14px;"></i>
                                </button>
                                <form method="POST" action="{{ route('app.admin.faqs.destroy', $faq) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this FAQ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-secondary">
                                    <i data-lucide="help-circle" style="width:48px;height:48px;opacity:0.3;"></i>
                                    <p class="mt-2 mb-1">No FAQs found</p>
                                    <small>Click "Add New FAQ" to create your first question.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($faqs->hasPages())
            <div class="card-footer">
                {{ $faqs->links() }}
            </div>
        @endif
    </div>

    {{-- Add FAQ Modal --}}
    <div class="modal fade" id="addFaqModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('app.admin.faqs.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i data-lucide="plus" class="me-2" style="width:18px;height:18px;"></i> Add New FAQ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Question <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="question" required placeholder="e.g. How does GIGS work?">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Answer <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="answer" rows="5" required placeholder="Write the answer here..."></textarea>
                            <small class="text-muted">HTML is supported for links and formatting.</small>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Category</label>
                                <input type="text" class="form-control" name="category" placeholder="e.g. General, Billing" list="category-list">
                                <datalist id="category-list">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" value="0" min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="addFaqActive">
                                    <label class="form-check-label" for="addFaqActive">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create FAQ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit FAQ Modals --}}
    @foreach($faqs as $faq)
        <div class="modal fade" id="editFaqModal{{ $faq->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('app.admin.faqs.update', $faq) }}">
                        @csrf @method('PATCH')
                        <div class="modal-header">
                            <h5 class="modal-title"><i data-lucide="pencil" class="me-2" style="width:18px;height:18px;"></i> Edit FAQ #{{ $faq->id }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Question <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="question" value="{{ $faq->question }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Answer <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="answer" rows="5" required>{{ $faq->answer }}</textarea>
                                <small class="text-muted">HTML is supported for links and formatting.</small>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Category</label>
                                    <input type="text" class="form-control" name="category" value="{{ $faq->category }}" placeholder="e.g. General, Billing" list="category-list">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" name="sort_order" value="{{ $faq->sort_order }}" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $faq->is_active ? 'checked' : '' }} id="editFaqActive{{ $faq->id }}">
                                        <label class="form-check-label" for="editFaqActive{{ $faq->id }}">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update FAQ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
