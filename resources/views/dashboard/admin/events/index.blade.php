@extends('layouts.dashboard')

@section('title', 'All Events')

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1"><i data-lucide="calendar-days" class="me-2" style="width:24px;height:24px;"></i> All Events</h4>
            <p class="text-secondary mb-0">Manage all events on the platform</p>
        </div>
        <button class="btn btn-primary btn-icon-text" data-bs-toggle="modal" data-bs-target="#addEventModal">
            <i class="btn-icon-prepend" data-lucide="plus"></i> Add New Event
        </button>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Quick Stats --}}
    <div class="row mb-4">
        <div class="col-md-2 col-6 mb-2">
            <div class="card bg-primary bg-opacity-10 border-0">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-primary">{{ $stats['total'] }}</h4>
                    <small class="text-secondary">Total</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <div class="card bg-warning bg-opacity-10 border-0">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-warning">{{ $stats['pending'] }}</h4>
                    <small class="text-secondary">Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <div class="card bg-info bg-opacity-10 border-0">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-info">{{ $stats['published'] }}</h4>
                    <small class="text-secondary">Published</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <div class="card bg-success bg-opacity-10 border-0">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-success">{{ $stats['confirmed'] }}</h4>
                    <small class="text-secondary">Confirmed</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <div class="card" style="background: rgba(16,185,129,0.1); border:0;">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0" style="color:#10b981;">{{ $stats['completed'] }}</h4>
                    <small class="text-secondary">Completed</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <div class="card bg-danger bg-opacity-10 border-0">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-danger">{{ $stats['cancelled'] }}</h4>
                    <small class="text-secondary">Cancelled</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Filter Sidebar --}}
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Filter Events</h6>
                    <form method="GET" action="{{ route('app.admin.events.index') }}">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Search by Title</label>
                            <input type="text" name="search" class="form-control" placeholder="e.g. wedding, conference..."
                                   value="{{ $filters['search'] ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="all">All Status</option>
                                @foreach(['pending','published','confirmed','in_progress','completed','cancelled'] as $s)
                                    <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $s)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Source</label>
                            <select name="source" class="form-select">
                                <option value="">All Sources</option>
                                @foreach(['user','ai','system'] as $src)
                                    <option value="{{ $src }}" {{ ($filters['source'] ?? '') === $src ? 'selected' : '' }}>
                                        {{ ucfirst($src) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Published</label>
                            <select name="published" class="form-select">
                                <option value="">All</option>
                                <option value="yes" {{ ($filters['published'] ?? '') === 'yes' ? 'selected' : '' }}>Published</option>
                                <option value="no" {{ ($filters['published'] ?? '') === 'no' ? 'selected' : '' }}>Unpublished</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Event Date Range</label>
                            <input type="date" name="date_from" class="form-control mb-2" value="{{ $filters['date_from'] ?? '' }}">
                            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="latest" {{ ($filters['sort'] ?? '') === 'latest' ? 'selected' : '' }}>Newest First</option>
                                <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                <option value="title_asc" {{ ($filters['sort'] ?? '') === 'title_asc' ? 'selected' : '' }}>Title A-Z</option>
                                <option value="title_desc" {{ ($filters['sort'] ?? '') === 'title_desc' ? 'selected' : '' }}>Title Z-A</option>
                                <option value="starts_at" {{ ($filters['sort'] ?? '') === 'starts_at' ? 'selected' : '' }}>Event Date</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i data-lucide="filter" class="icon-sm me-1"></i> Apply Filters
                        </button>
                        <a href="{{ route('app.admin.events.index') }}" class="btn btn-secondary w-100">
                            <i data-lucide="refresh-cw" class="icon-sm me-1"></i> Reset Filters
                        </a>
                    </form>
                </div>
            </div>
        </div>

        {{-- Events Table --}}
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    @if($events->count())
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Client</th>
                                        <th>Supplier</th>
                                        <th>Status</th>
                                        <th>Published</th>
                                        <th>Event Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($events as $event)
                                    <tr>
                                        <td>{{ $event->id }}</td>
                                        <td>
                                            <strong>{{ Str::limit($event->title, 30) }}</strong>
                                            <br><small class="text-secondary">{{ ucfirst($event->source) }}</small>
                                        </td>
                                        <td>{{ $event->client?->name ?? '—' }}</td>
                                        <td>{{ $event->supplier?->name ?? '—' }}</td>
                                        <td>
                                            @php
                                                $badge = match($event->status) {
                                                    'pending' => 'bg-warning',
                                                    'published' => 'bg-info',
                                                    'confirmed' => 'bg-success',
                                                    'in_progress' => 'bg-primary',
                                                    'completed' => 'bg-success',
                                                    'cancelled' => 'bg-danger',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                                        </td>
                                        <td>
                                            @if($event->is_published)
                                                <span class="badge bg-success">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($event->starts_at)
                                                {{ $event->starts_at->format('M d, Y') }}
                                            @else
                                                <span class="text-secondary">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal" data-bs-target="#editEventModal{{ $event->id }}"
                                                    title="Edit">
                                                    <i data-lucide="edit-2" style="width:14px;height:14px;"></i>
                                                </button>
                                                <form method="POST" action="{{ route('app.admin.events.destroy', $event) }}"
                                                    onsubmit="return confirm('Are you sure you want to delete this event?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Edit Modal --}}
                                    <div class="modal fade" id="editEventModal{{ $event->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('app.admin.events.update', $event) }}">
                                                    @csrf @method('PATCH')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Event: {{ Str::limit($event->title, 40) }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-12 mb-3">
                                                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                                                <input type="text" name="title" class="form-control" value="{{ $event->title }}" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Client <span class="text-danger">*</span></label>
                                                                <select name="client_id" class="form-select" required>
                                                                    <option value="">Select Client</option>
                                                                    @foreach($clients as $client)
                                                                        <option value="{{ $client->id }}" {{ $event->client_id == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Supplier</label>
                                                                <select name="supplier_id" class="form-select">
                                                                    <option value="">No Supplier</option>
                                                                    @foreach($suppliers as $supplier)
                                                                        <option value="{{ $supplier->id }}" {{ $event->supplier_id == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Categories</label>
                                                                <select name="category_ids[]" class="form-select" multiple size="4">
                                                                    @foreach($categories as $cat)
                                                                        <option value="{{ $cat->id }}" {{ $event->categories->contains('id', $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                                                <select name="status" class="form-select" required>
                                                                    @foreach(['pending','published','confirmed','in_progress','completed','cancelled'] as $s)
                                                                        <option value="{{ $s }}" {{ $event->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Start Date</label>
                                                                <input type="datetime-local" name="starts_at" class="form-control"
                                                                    value="{{ $event->starts_at?->format('Y-m-d\TH:i') }}">
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">End Date</label>
                                                                <input type="datetime-local" name="ends_at" class="form-control"
                                                                    value="{{ $event->ends_at?->format('Y-m-d\TH:i') }}">
                                                            </div>
                                                            <div class="col-md-12 mb-3">
                                                                <label class="form-label">Description</label>
                                                                <textarea name="description" class="form-control" rows="3">{{ $event->description }}</textarea>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <div class="form-check">
                                                                    <input type="hidden" name="is_published" value="0">
                                                                    <input type="checkbox" name="is_published" value="1" class="form-check-input"
                                                                        id="published{{ $event->id }}" {{ $event->is_published ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="published{{ $event->id }}">Published</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Update Event</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $events->links() }}
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i data-lucide="info" class="icon-sm me-1"></i>
                            No events found matching your criteria.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Add Event Modal --}}
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('app.admin.events.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="Enter event title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Client <span class="text-danger">*</span></label>
                                <select name="client_id" class="form-select" required>
                                    <option value="">Select Client</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">No Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Categories</label>
                                <select name="category_ids[]" class="form-select" multiple size="4">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    @foreach(['pending','published','confirmed','in_progress','completed','cancelled'] as $s)
                                        <option value="{{ $s }}">{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="datetime-local" name="starts_at" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="datetime-local" name="ends_at" class="form-control">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Event description..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input type="hidden" name="is_published" value="0">
                                    <input type="checkbox" name="is_published" value="1" class="form-check-input" id="newPublished">
                                    <label class="form-check-label" for="newPublished">Publish immediately</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
