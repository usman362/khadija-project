@extends('layouts.dashboard')

@section('title', 'Event Detail')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ $event->title }}</h4>
    <a href="{{ route('app.events.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Event Info</h6>
                <p><strong>Status:</strong> {{ $event->status }}</p>
                <p><strong>Published:</strong> {{ $event->is_published ? 'Yes' : 'No' }}</p>
                <p><strong>Source:</strong> @php $src = $event->source ?? 'user'; @endphp<span class="badge bg-{{ $src === 'user' ? 'primary' : ($src === 'ai' ? 'info' : 'secondary') }}">{{ ucfirst($src) }}</span></p>
                <p><strong>Client:</strong> {{ $event->client?->name }}</p>
                <p><strong>Supplier:</strong> {{ $event->supplier?->name ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Description:</strong> {{ $event->description ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Bookings</h6>
                <ul class="list-group">
                    @forelse($event->bookings as $booking)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            #{{ $booking->id }} - {{ $booking->status }}
                            <span>{{ $booking->client?->name }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No bookings yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="card-title">Messages</h6>
        <ul class="list-group">
            @forelse($event->messages as $message)
                <li class="list-group-item">
                    <strong>{{ $message->sender?->name }}:</strong> {{ $message->body }}
                </li>
            @empty
                <li class="list-group-item text-muted">No messages yet.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
