@extends('layouts.dashboard')
@section('title', 'Activity Log')
@section('content')

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1">
            <i data-lucide="activity" class="me-2" style="width:24px;height:24px;"></i>
            Activity Log
        </h4>
        <p class="text-secondary mb-0">Security-sensitive user actions (auth events & password changes)</p>
    </div>
</div>

{{-- Stats cards --}}
<div class="row g-3 mb-4">
    @php
        $cards = [
            ['label' => 'Total',            'value' => $stats['total'],            'color' => 'primary', 'icon' => 'list',           'key' => null],
            ['label' => 'Logins',           'value' => $stats['login'],            'color' => 'success', 'icon' => 'log-in',         'key' => 'login'],
            ['label' => 'Logouts',          'value' => $stats['logout'],           'color' => 'secondary','icon'=> 'log-out',        'key' => 'logout'],
            ['label' => 'Failed Logins',    'value' => $stats['login_failed'],     'color' => 'danger',  'icon' => 'shield-alert',   'key' => 'login_failed'],
            ['label' => 'Password Changed', 'value' => $stats['password_changed'], 'color' => 'info',    'icon' => 'key-round',      'key' => 'password_changed'],
            ['label' => 'Password Reset',   'value' => $stats['password_reset'],   'color' => 'warning', 'icon' => 'rotate-ccw-key', 'key' => 'password_reset'],
        ];
    @endphp
    @foreach($cards as $card)
    <div class="col-md-4 col-lg-2">
        <a href="{{ $card['key'] ? route('app.admin.activity-logs.index', ['action' => $card['key']]) : route('app.admin.activity-logs.index') }}" class="text-decoration-none">
            <div class="card h-100 border-{{ $card['color'] }}" style="background: rgba(0,0,0,0.02);">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <i data-lucide="{{ $card['icon'] }}" class="text-{{ $card['color'] }} me-2" style="width:20px;height:20px;"></i>
                        <div>
                            <div class="h5 mb-0 text-{{ $card['color'] }}">{{ number_format($card['value']) }}</div>
                            <div class="small text-secondary">{{ $card['label'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <label class="form-label small text-secondary">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Name or email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-secondary">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <option value="login"            @selected(request('action') === 'login')>Login</option>
                    <option value="logout"           @selected(request('action') === 'logout')>Logout</option>
                    <option value="login_failed"     @selected(request('action') === 'login_failed')>Failed Login</option>
                    <option value="password_changed" @selected(request('action') === 'password_changed')>Password Changed</option>
                    <option value="password_reset"   @selected(request('action') === 'password_reset')>Password Reset</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-secondary">IP Address</label>
                <input type="text" name="ip" class="form-control form-control-sm"
                       placeholder="192.168..." value="{{ request('ip') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-secondary">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-secondary">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i data-lucide="filter" style="width:14px;height:14px;"></i>
                </button>
            </div>
            @if(request()->hasAny(['search','action','ip','from','to']))
                <div class="col-12">
                    <a href="{{ route('app.admin.activity-logs.index') }}" class="btn btn-sm btn-link text-secondary">
                        <i data-lucide="x" style="width:14px;height:14px;"></i> Clear filters
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-secondary small">#{{ $log->id }}</td>
                        <td>
                            <div class="small"><strong>{{ $log->created_at->format('M j, Y') }}</strong></div>
                            <div class="small text-secondary">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td>
                            @if($log->user)
                                <div class="d-flex align-items-center">
                                    <img src="{{ $log->user->avatar_url }}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                                    <div>
                                        <div class="small"><strong>{{ $log->user->name }}</strong>
                                            @if($log->user->trashed())
                                                <span class="badge bg-secondary ms-1">deleted</span>
                                            @endif
                                        </div>
                                        <div class="small text-secondary">{{ $log->user->email }}</div>
                                    </div>
                                </div>
                            @else
                                <div class="small text-secondary">
                                    <i data-lucide="user-x" style="width:14px;height:14px;"></i>
                                    {{ $log->subject_identifier ?? '(unknown)' }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $log->actionColor() }}">{{ $log->actionLabel() }}</span>
                        </td>
                        <td>
                            <code class="small">{{ $log->ip_address ?? '—' }}</code>
                        </td>
                        <td class="small text-secondary" style="max-width:260px;">
                            <span class="text-truncate d-inline-block" style="max-width:250px;" title="{{ $log->user_agent }}">
                                {{ $log->user_agent ?? '—' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">
                            <i data-lucide="inbox" style="width:48px;height:48px;opacity:0.3;"></i>
                            <div class="mt-3">No activity logs found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $logs->links() }}
</div>

@endsection
