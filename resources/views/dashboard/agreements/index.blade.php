@extends('layouts.dashboard')

@section('title', 'AI Agreements')

@section('content')
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-title mb-0">AI-Generated Agreements</h6>
        </div>

        <form method="GET" action="{{ route('app.agreements.index') }}" class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    @foreach(['draft', 'pending_review', 'client_accepted', 'supplier_accepted', 'fully_accepted', 'rejected'] as $status)
                        <option value="{{ $status }}" {{ $selectedStatus === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
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
                    <th>Event / Booking</th>
                    <th>Client</th>
                    <th>Supplier</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Generated</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($agreements as $agreement)
                    <tr>
                        <td>#{{ $agreement->id }}</td>
                        <td>
                            {{ $agreement->booking->event->title ?? '—' }}
                            <br><small class="text-muted">Booking #{{ $agreement->booking_id }}</small>
                        </td>
                        <td>
                            {{ $agreement->booking->client->name ?? 'N/A' }}
                            @if($agreement->clientAccepted())
                                <br><span class="badge bg-success" style="font-size: 0.65rem;">Accepted</span>
                            @endif
                        </td>
                        <td>
                            {{ $agreement->booking->supplier->name ?? 'N/A' }}
                            @if($agreement->supplierAccepted())
                                <br><span class="badge bg-success" style="font-size: 0.65rem;">Accepted</span>
                            @endif
                        </td>
                        <td>v{{ $agreement->version }}</td>
                        <td>
                            <span class="badge bg-{{ $agreement->statusColor() }}">{{ $agreement->statusLabel() }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $agreement->source === 'ai' ? 'info' : 'secondary' }}">
                                {{ ucfirst($agreement->source) }}
                            </span>
                        </td>
                        <td>{{ $agreement->created_at->format('M d, Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('app.agreements.show', $agreement) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-muted">No agreements found. Generate one from a booking's chat!</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $agreements->links() }}
    </div>
</div>
@endsection
