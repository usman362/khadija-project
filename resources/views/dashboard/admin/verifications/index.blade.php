@extends('layouts.dashboard')
@section('title', 'Pro Verifications')
@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="shield-check" class="me-2" style="width:24px;height:24px;"></i> Professional Verifications</h4>
        <p class="text-secondary mb-0">Review trade license, insurance, and workers' comp submissions from professionals.</p>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="mb-3">
    <a href="{{ route('app.admin.verifications.index', ['filter' => 'pending']) }}"
       class="btn btn-sm {{ $filter === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">
        Pending Review ({{ $counts['pending'] }})
    </a>
    <a href="{{ route('app.admin.verifications.index', ['filter' => 'verified']) }}"
       class="btn btn-sm {{ $filter === 'verified' ? 'btn-success' : 'btn-outline-success' }}">
        Verified ({{ $counts['verified'] }})
    </a>
    <a href="{{ route('app.admin.verifications.index', ['filter' => 'all']) }}"
       class="btn btn-sm {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
        All With Submissions
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th>Professional</th>
                    <th>Trade License</th>
                    <th>Liability Insurance</th>
                    <th>Workers' Comp</th>
                    <th>Last Update</th>
                </tr>
                </thead>
                <tbody>
                @forelse($profiles as $profile)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $profile->user?->name ?? 'Unknown' }}</div>
                            <div class="text-muted small">{{ $profile->user?->email }}</div>
                        </td>
                        @foreach(['trade_license', 'liability_insurance', 'workers_comp'] as $badge)
                            @php
                                $status = $profile->badgeStatus($badge);
                                $doc = $profile->{"{$badge}_doc"};
                                $number = $profile->{"{$badge}_number"};
                                $verifiedAt = $profile->{"{$badge}_verified_at"};
                            @endphp
                            <td style="min-width:220px;">
                                @if($status === 'verified')
                                    <div class="mb-1">
                                        <span class="badge bg-success">✓ Verified</span>
                                        <small class="text-muted">{{ $verifiedAt->format('M d, Y') }}</small>
                                    </div>
                                    @if($number)<div class="small text-muted">#{{ $number }}</div>@endif
                                    <a href="{{ asset('storage/' . $doc) }}" target="_blank" class="small">View doc</a>
                                    <form method="POST" action="{{ route('app.admin.verifications.reject', $profile) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="badge" value="{{ $badge }}">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 ms-2"
                                                onclick="return confirm('Revoke this verification and remove the document?')">Revoke</button>
                                    </form>
                                @elseif($status === 'pending')
                                    <div class="mb-1"><span class="badge bg-warning text-dark">Pending</span></div>
                                    @if($number)<div class="small text-muted">#{{ $number }}</div>@endif
                                    <a href="{{ asset('storage/' . $doc) }}" target="_blank" class="small d-block mb-2">View document →</a>
                                    <div class="d-flex gap-1">
                                        <form method="POST" action="{{ route('app.admin.verifications.approve', $profile) }}">
                                            @csrf
                                            <input type="hidden" name="badge" value="{{ $badge }}">
                                            <button class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('app.admin.verifications.reject', $profile) }}">
                                            @csrf
                                            <input type="hidden" name="badge" value="{{ $badge }}">
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Reject this submission and delete the document?')">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-muted small">{{ $profile->updated_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No submissions in this filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $profiles->links() }}</div>
@endsection
