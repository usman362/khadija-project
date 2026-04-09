@extends('layouts.dashboard')
@section('title', 'Account Deletion Requests')
@section('content')

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="user-x" class="me-2" style="width:24px;height:24px;"></i> Account Deletion Requests</h4>
        <p class="text-secondary mb-0">Users who have requested account deletion. Grace period: 60 days.</p>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-warning" style="background: rgba(245,158,11,0.06);">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px; height:48px; background: rgba(245,158,11,0.15);">
                        <i data-lucide="clock" style="width:24px;height:24px; color:#f59e0b;"></i>
                    </div>
                    <div>
                        <div class="h3 mb-0" style="color:#f59e0b;">{{ $stats['pending'] }}</div>
                        <div class="text-secondary small">Pending Deletion (grace period active)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-danger" style="background: rgba(239,68,68,0.06);">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px; height:48px; background: rgba(239,68,68,0.15);">
                        <i data-lucide="alert-triangle" style="width:24px;height:24px; color:#ef4444;"></i>
                    </div>
                    <div>
                        <div class="h3 mb-0" style="color:#ef4444;">{{ $stats['expired'] }}</div>
                        <div class="text-secondary small">Expired (awaiting purge)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Search --}}
<form method="GET" class="mb-3">
    <div class="input-group">
        <span class="input-group-text"><i data-lucide="search" style="width:16px;height:16px;"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
        <button type="submit" class="btn btn-primary">Search</button>
        @if(request('search'))
            <a href="{{ route('app.admin.deletion-requests.index') }}" class="btn btn-outline-secondary">Clear</a>
        @endif
    </div>
</form>

{{-- Table --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Requested</th>
                    <th>Scheduled Purge</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $daysLeft = (int) ceil(now()->diffInHours($user->deletion_scheduled_at, false) / 24);
                        $expired  = $daysLeft <= 0;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ $user->avatar_url }}" alt="" class="rounded-circle me-2" style="width:36px; height:36px; object-fit:cover;">
                                <strong>{{ $user->name }}</strong>
                            </div>
                        </td>
                        <td class="text-secondary small">{{ $user->email }}</td>
                        <td>
                            @foreach($user->roles as $role)
                                <span class="badge bg-secondary">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td class="small">{{ $user->deletion_requested_at->format('M j, Y') }}</td>
                        <td class="small">{{ $user->deletion_scheduled_at->format('M j, Y') }}</td>
                        <td>
                            @if($expired)
                                <span class="badge bg-danger">Expired</span>
                            @elseif($daysLeft <= 7)
                                <span class="badge bg-warning">{{ $daysLeft }}d left</span>
                            @else
                                <span class="badge bg-info">{{ $daysLeft }}d left</span>
                            @endif
                        </td>
                        <td class="small text-secondary" style="max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $user->deletion_reason }}">
                            {{ $user->deletion_reason ?: '—' }}
                        </td>
                        <td class="text-end">
                            <form action="{{ route('app.admin.deletion-requests.cancel', $user) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Cancel the deletion request for {{ $user->name }}? The account will be fully restored.');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i> Restore
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-secondary">
                            <i data-lucide="inbox" style="width:48px;height:48px;opacity:0.3;"></i>
                            <div class="mt-3">No deletion requests found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $users->links() }}
</div>

@endsection
