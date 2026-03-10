@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1">Welcome back, {{ auth()->user()?->name }}!</h4>
            <p class="text-secondary mb-0">Here's what's happening with your account.</p>
        </div>
    </div>

    {{-- Admin Stats Cards --}}
    @if(auth()->user()?->hasRole('admin') && !empty($stats))
    <div class="row mb-4">
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="w-40px h-40px rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i data-lucide="users" class="text-primary" style="width:20px;height:20px"></i>
                        </div>
                        <div>
                            <p class="text-secondary mb-1">Total Users</p>
                            <h4 class="mb-0">{{ number_format($stats['total_users']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="w-40px h-40px rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i data-lucide="calendar-days" class="text-info" style="width:20px;height:20px"></i>
                        </div>
                        <div>
                            <p class="text-secondary mb-1">Total Events</p>
                            <h4 class="mb-0">{{ number_format($stats['total_events']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="w-40px h-40px rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i data-lucide="book-check" class="text-success" style="width:20px;height:20px"></i>
                        </div>
                        <div>
                            <p class="text-secondary mb-1">Total Bookings</p>
                            <h4 class="mb-0">{{ number_format($stats['total_bookings']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="w-40px h-40px rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                            <i data-lucide="crown" class="text-warning" style="width:20px;height:20px"></i>
                        </div>
                        <div>
                            <p class="text-secondary mb-1">Active Subscriptions</p>
                            <h4 class="mb-0">{{ number_format($stats['active_plans']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        {{-- Current Subscription --}}
        <div class="col-lg-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">My Subscription</h6>
                    @if($subscription)
                        <div class="d-flex align-items-center mb-3">
                            <div class="w-45px h-45px rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3">
                                <i data-lucide="crown" class="text-primary" style="width:22px;height:22px"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $subscription->membershipPlan->name ?? 'Plan' }}</h5>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                        <p class="text-secondary mb-1">Started: {{ $subscription->started_at?->format('M d, Y') ?? 'N/A' }}</p>
                        @if($subscription->expires_at)
                            <p class="text-secondary mb-0">Expires: {{ $subscription->expires_at->format('M d, Y') }}</p>
                        @else
                            <p class="text-secondary mb-0">No expiry date</p>
                        @endif
                    @else
                        <div class="text-center py-3">
                            <i data-lucide="crown" class="text-secondary mb-2" style="width:40px;height:40px"></i>
                            <p class="text-secondary">No active subscription</p>
                            @can('membership_plans.view_any')
                                <a href="{{ route('app.membership-plans.index') }}" class="btn btn-sm btn-primary">Browse Plans</a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Quick Actions</h6>
                    <div class="row g-3">
                        @can('events.view_any')
                        <div class="col-md-4">
                            <a href="{{ route('app.events.index') }}" class="card border text-decoration-none h-100">
                                <div class="card-body text-center py-4">
                                    <i data-lucide="calendar-days" class="text-primary mb-2" style="width:28px;height:28px"></i>
                                    <p class="mb-0 fw-bold">View Events</p>
                                </div>
                            </a>
                        </div>
                        @endcan
                        @can('bookings.view_any')
                        <div class="col-md-4">
                            <a href="{{ route('app.bookings.index') }}" class="card border text-decoration-none h-100">
                                <div class="card-body text-center py-4">
                                    <i data-lucide="book-check" class="text-success mb-2" style="width:28px;height:28px"></i>
                                    <p class="mb-0 fw-bold">My Bookings</p>
                                </div>
                            </a>
                        </div>
                        @endcan
                        @can('messages.view_any')
                        <div class="col-md-4">
                            <a href="{{ route('app.chat.index') }}" class="card border text-decoration-none h-100">
                                <div class="card-body text-center py-4">
                                    <i data-lucide="message-circle" class="text-info mb-2" style="width:28px;height:28px"></i>
                                    <p class="mb-0 fw-bold">Open Chat</p>
                                </div>
                            </a>
                        </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Recent Bookings --}}
        <div class="col-lg-7 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Recent Bookings</h6>
                    @if($myBookings->count())
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="pt-0">Event</th>
                                        <th class="pt-0">Status</th>
                                        <th class="pt-0">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($myBookings as $booking)
                                    <tr>
                                        <td>{{ $booking->event?->title ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $badgeClass = match($booking->status) {
                                                    'confirmed' => 'bg-success',
                                                    'pending' => 'bg-warning',
                                                    'cancelled' => 'bg-danger',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ ucfirst($booking->status) }}</span>
                                        </td>
                                        <td>{{ $booking->created_at?->format('M d, Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i data-lucide="book-check" class="text-secondary mb-2" style="width:36px;height:36px"></i>
                            <p class="text-secondary mb-0">No bookings yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Events --}}
        <div class="col-lg-5 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Latest Events</h6>
                    @if($recentEvents->count())
                        <div class="d-flex flex-column">
                            @foreach($recentEvents as $event)
                            <div class="d-flex align-items-center {{ !$loop->last ? 'border-bottom pb-3 mb-3' : '' }}">
                                <div class="w-40px h-40px rounded bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3 flex-shrink-0">
                                    <i data-lucide="calendar" class="text-primary" style="width:18px;height:18px"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="fw-bold mb-0">{{ $event->title }}</p>
                                    <p class="text-secondary fs-12px mb-0">{{ $event->created_at?->diffForHumans() }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i data-lucide="calendar-days" class="text-secondary mb-2" style="width:36px;height:36px"></i>
                            <p class="text-secondary mb-0">No events yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
