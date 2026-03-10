@extends('layouts.dashboard')

@section('title', 'Bookings')

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
            <h6 class="card-title mb-0">Bookings</h6>
            @can('create', \App\Models\Booking::class)
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookingModal">Add Booking</button>
            @endcan
        </div>

        <form method="GET" action="{{ route('app.bookings.index') }}" class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small">Source (ownership)</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="">All sources</option>
                    <option value="user" {{ ($selectedSource ?? null) === 'user' ? 'selected' : '' }}>User</option>
                    <option value="ai" {{ ($selectedSource ?? null) === 'ai' ? 'selected' : '' }}>AI</option>
                    <option value="system" {{ ($selectedSource ?? null) === 'system' ? 'selected' : '' }}>System</option>
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
                    <th>ID</th>
                    <th>Event</th>
                    <th>Client</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($bookings as $booking)
                    <tr>
                        <td>#{{ $booking->id }}</td>
                        <td>{{ $booking->event?->title }}</td>
                        <td>{{ $booking->client?->name ?? 'N/A' }}</td>
                        <td>{{ $booking->supplier?->name ?? 'N/A' }}</td>
                        <td>{{ ucfirst($booking->status) }}</td>
                        <td>
                            @php $src = $booking->source ?? 'user'; @endphp
                            <span class="badge bg-{{ $src === 'user' ? 'primary' : ($src === 'ai' ? 'info' : 'secondary') }}">{{ ucfirst($src) }}</span>
                        </td>
                        <td class="text-end">
                            @can('update', $booking)
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editBookingModal{{ $booking->id }}">Edit</button>
                            @endcan
                            @if($booking->status !== 'completed' && $booking->status !== 'cancelled')
                                <form method="POST" action="{{ route('app.agreements.generate', $booking) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-info" title="Generate AI Agreement"
                                        onclick="return confirm('Generate an AI agreement from the chat for this booking?')">
                                        AI Agreement
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>

                    @can('update', $booking)
                    <div class="modal fade" id="editBookingModal{{ $booking->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-md modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('app.bookings.update-status', $booking) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Booking #{{ $booking->id }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select">
                                                @foreach(['requested', 'confirmed', 'cancelled', 'completed'] as $status)
                                                    <option value="{{ $status }}" {{ $booking->status === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
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
                        <td colspan="7" class="text-muted">No bookings yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $bookings->links() }}
    </div>
</div>

@can('create', \App\Models\Booking::class)
<div class="modal fade" id="addBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('app.bookings.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Published Event</label>
                        <select name="event_id" class="form-select" required>
                            <option value="">Select event</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}">{{ $event->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection
