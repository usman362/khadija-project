@extends('layouts.dashboard')

@section('title', 'Agreement Log')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-title mb-0">Agreement log (booking state changes)</h6>
        </div>
        <p class="text-muted small mb-3">Append-only log of booking status changes. Immutable record for auditing.</p>

        <div class="table-responsive">
            <table class="table table-hover w-100">
                <thead>
                <tr>
                    <th>Time</th>
                    <th>Booking</th>
                    <th>Event</th>
                    <th>From → To</th>
                    <th>Changed by</th>
                    <th>Notes</th>
                </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    @php $booking = $bookings->get($log->subject_id); @endphp
                    <tr>
                        <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        <td>#{{ $log->subject_id }}</td>
                        <td>{{ $booking?->event?->title ?? '—' }}</td>
                        <td>
                            <span class="text-muted">{{ $log->from_status ?? '—' }}</span>
                            <span class="mx-1">→</span>
                            <span class="fw-medium">{{ $log->to_status }}</span>
                        </td>
                        <td>{{ $log->changer?->name ?? 'System' }}</td>
                        <td>{{ Str::limit($log->notes, 40) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-muted">No agreement log entries yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </div>
</div>
@endsection
