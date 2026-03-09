@extends('layouts.dashboard')

@section('title', 'Manage Membership Plans')

@section('content')
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
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
            <h6 class="card-title mb-0">Membership Plans</h6>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlanModal">Add Plan</button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover w-100">
                <thead>
                <tr>
                    <th>Order</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Billing</th>
                    <th>Limits</th>
                    <th>Status</th>
                    <th>Subscribers</th>
                    <th>Features</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td>{{ $plan->sort_order }}</td>
                        <td>
                            <strong>{{ $plan->name }}</strong>
                            @if($plan->badge_text)
                                <br><span class="badge bg-{{ $plan->badge_color ?? 'primary' }} mt-1">{{ $plan->badge_text }}</span>
                            @endif
                            @if($plan->is_featured)
                                <span class="badge bg-info mt-1">Featured</span>
                            @endif
                        </td>
                        <td>{{ $plan->formattedPrice() }}</td>
                        <td>{{ ucfirst($plan->billing_cycle) }}</td>
                        <td>
                            <small>
                                Events: {{ $plan->max_events ?? '∞' }}<br>
                                Bookings: {{ $plan->max_bookings ?? '∞' }}<br>
                                Chat: {{ $plan->has_chat ? 'Yes' : 'No' }}
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $plan->is_active ? 'success' : 'secondary' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $plan->active_subscribers_count }}</td>
                        <td>
                            <small>
                            @foreach($plan->features as $f)
                                {{ $f->is_included ? '✓' : '✗' }} {{ $f->feature }}<br>
                            @endforeach
                            </small>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPlanModal{{ $plan->id }}">Edit</button>
                            @if($plan->active_subscribers_count === 0)
                                <form method="POST" action="{{ route('app.admin.membership-plans.destroy', $plan) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this plan?')">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>

                    {{-- Edit Modal --}}
                    <div class="modal fade" id="editPlanModal{{ $plan->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('app.admin.membership-plans.update', $plan) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Plan: {{ $plan->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        @include('dashboard.membership-plans._form', ['plan' => $plan])
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="9" class="text-muted">No membership plans found. Create one to get started.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add Plan Modal --}}
<div class="modal fade" id="addPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('app.admin.membership-plans.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Membership Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('dashboard.membership-plans._form', ['plan' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Dynamic feature fields
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.add-feature-btn').forEach(function(btn) {
            btn.addEventListener('click', function () {
                const container = this.closest('.features-container');
                const list = container.querySelector('.features-list');
                const index = list.children.length;
                const div = document.createElement('div');
                div.className = 'd-flex gap-2 mb-2 align-items-center';
                div.innerHTML = '<input type="text" name="features[]" class="form-control form-control-sm" placeholder="Feature description">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-feature-btn">&times;</button>';
                list.appendChild(div);
                div.querySelector('.remove-feature-btn').addEventListener('click', function() {
                    div.remove();
                });
            });
        });

        document.querySelectorAll('.remove-feature-btn').forEach(function(btn) {
            btn.addEventListener('click', function () {
                this.closest('.d-flex').remove();
            });
        });
    });
</script>
@endpush
