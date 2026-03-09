@extends('layouts.dashboard')

@section('title', 'Events')

@section('content')
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-title mb-0">Events</h6>
            @can('create', \App\Models\Event::class)
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">Add Event</button>
            @endcan
        </div>

        <form method="GET" action="{{ route('app.events.index') }}" class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small">Source (ownership)</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="">All sources</option>
                    <option value="user" {{ $selectedSource === 'user' ? 'selected' : '' }}>User</option>
                    <option value="ai" {{ $selectedSource === 'ai' ? 'selected' : '' }}>AI</option>
                    <option value="system" {{ $selectedSource === 'system' ? 'selected' : '' }}>System</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-primary btn-sm">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover w-100">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th>Source</th>
                    <th>Supplier</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($events as $event)
                    <tr>
                        <td>{{ $event->title }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $event->status)) }}</td>
                        <td>{{ $event->is_published ? 'Yes' : 'No' }}</td>
                        <td>
                            @php $src = $event->source ?? 'user'; @endphp
                            <span class="badge bg-{{ $src === 'user' ? 'primary' : ($src === 'ai' ? 'info' : 'secondary') }}">{{ ucfirst($src) }}</span>
                        </td>
                        <td>{{ $event->supplier?->name ?? 'N/A' }}</td>
                        <td class="text-end">
                            @can('view', $event)
                                <a href="{{ route('app.events.show', $event) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            @endcan
                            @can('update', $event)
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editEventModal{{ $event->id }}">Edit</button>
                            @endcan
                            @can('publish', $event)
                            @if(!$event->is_published)
                                <form method="POST" action="{{ route('app.events.publish', $event) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-primary">Publish</button>
                                </form>
                            @endif
                            @endcan
                        </td>
                    </tr>

                    @can('update', $event)
                    <div class="modal fade" id="editEventModal{{ $event->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('app.events.update', $event) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Event</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Title</label>
                                                <input type="text" name="title" class="form-control" value="{{ $event->title }}" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select" required>
                                                    @foreach(['pending', 'published', 'confirmed', 'in_progress', 'completed', 'cancelled'] as $status)
                                                        <option value="{{ $status }}" {{ $event->status === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control" rows="3">{{ $event->description }}</textarea>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Starts At</label>
                                                <input type="datetime-local" name="starts_at" class="form-control" value="{{ optional($event->starts_at)->format('Y-m-d\\TH:i') }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Ends At</label>
                                                <input type="datetime-local" name="ends_at" class="form-control" value="{{ optional($event->ends_at)->format('Y-m-d\\TH:i') }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Supplier</label>
                                                <select name="supplier_id" class="form-select">
                                                    <option value="">None</option>
                                                    @foreach($suppliers as $supplier)
                                                        <option value="{{ $supplier->id }}" {{ $event->supplier_id === $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endcan
                @empty
                    <tr>
                        <td colspan="6" class="text-muted">No events found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $events->links() }}
    </div>
</div>

@can('create', \App\Models\Event::class)
<div class="modal fade" id="addEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('app.events.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">None</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Starts At</label>
                            <input type="datetime-local" name="starts_at" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ends At</label>
                            <input type="datetime-local" name="ends_at" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Event</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection
