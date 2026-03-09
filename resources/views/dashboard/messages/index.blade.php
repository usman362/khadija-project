@extends('layouts.dashboard')

@section('title', 'Messages')

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
            <h6 class="card-title mb-0">Messages</h6>
            @can('create', \App\Models\Message::class)
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMessageModal">Add Message</button>
            @endcan
        </div>

        <form method="GET" action="{{ route('app.messages.index') }}" class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small">Booking</label>
                <select name="booking_id" class="form-select form-select-sm">
                    <option value="">All bookings</option>
                    @foreach($bookings as $booking)
                        <option value="{{ $booking->id }}" {{ (int)$selectedBookingId === $booking->id ? 'selected' : '' }}>#{{ $booking->id }} - {{ $booking->event?->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Source</label>
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
                    <th>Booking</th>
                    <th>Sender</th>
                    <th>Recipient</th>
                    <th>Message</th>
                    <th>Source</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse($messages as $message)
                    <tr>
                        <td>#{{ $message->id }}</td>
                        <td>#{{ $message->booking_id }}</td>
                        <td>{{ $message->sender?->name }}</td>
                        <td>{{ $message->recipient?->name ?? 'N/A' }}</td>
                        <td>{{ Str::limit($message->body, 50) }}</td>
                        <td>
                            @php $src = $message->source ?? 'user'; @endphp
                            <span class="badge bg-{{ $src === 'user' ? 'primary' : ($src === 'ai' ? 'info' : 'secondary') }}">{{ ucfirst($src) }}</span>
                        </td>
                        <td>{{ $message->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-muted">No messages found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $messages->links() }}
    </div>
</div>

@can('create', \App\Models\Message::class)
<div class="modal fade" id="addMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('app.messages.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Booking</label>
                        <select name="booking_id" class="form-select" required>
                            <option value="">Select booking</option>
                            @foreach($bookings as $booking)
                                <option value="{{ $booking->id }}" {{ (int)$selectedBookingId === $booking->id ? 'selected' : '' }}>#{{ $booking->id }} - {{ $booking->event?->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="body" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection
