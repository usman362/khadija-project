@extends('layouts.dashboard')

@section('title', 'Subscription History')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-title mb-0">Subscription History</h6>
            <a href="{{ route('app.membership-plans.index') }}" class="btn btn-sm btn-outline-primary">Back to Plans</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover w-100">
                <thead>
                <tr>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Started</th>
                    <th>Expires</th>
                    <th>Cancelled</th>
                </tr>
                </thead>
                <tbody>
                @forelse($subscriptions as $sub)
                    <tr>
                        <td>{{ $sub->plan->name }}</td>
                        <td>
                            @php
                                $statusColor = match($sub->status) {
                                    'active' => 'success',
                                    'cancelled' => 'warning',
                                    'expired' => 'secondary',
                                    'pending' => 'info',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $statusColor }}">{{ ucfirst($sub->status) }}</span>
                        </td>
                        <td>${{ number_format($sub->amount_paid, 2) }}</td>
                        <td>{{ $sub->starts_at->format('M d, Y') }}</td>
                        <td>{{ $sub->expires_at?->format('M d, Y') ?? 'Never' }}</td>
                        <td>
                            @if($sub->cancelled_at)
                                {{ $sub->cancelled_at->format('M d, Y') }}
                                @if($sub->cancellation_reason)
                                    <br><small class="text-muted">{{ $sub->cancellation_reason }}</small>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-muted">No subscription history found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $subscriptions->links() }}
    </div>
</div>
@endsection
