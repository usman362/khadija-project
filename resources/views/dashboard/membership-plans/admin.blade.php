@extends('layouts.dashboard')

@section('title', 'Manage Membership Plans')

@section('content')
<style>
    /* Plan modals are tall (all fields + every feature row). This admin theme
       breaks Bootstrap's modal-dialog-scrollable height chain (it relies on a
       100% height cascade), so the modal overflowed the viewport with no
       internal scroll and the footer (Save/Cancel) became unreachable. Cap the
       body with viewport units so it scrolls and the footer stays visible. */
    #addPlanModal .modal-body,
    [id^="editPlanModal"] .modal-body {
        max-height: calc(100vh - 210px);
        overflow-y: auto;
    }
    #addPlanModal .modal-content,
    [id^="editPlanModal"] .modal-content {
        max-height: calc(100vh - 2rem);
    }
</style>
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
                        <td>{{ $plan->contractTermLabel() }}</td>
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

{{-- Edit Plan Modals — rendered OUTSIDE the <table>. A <form> placed inside
     <table>/<tbody> is foster-parented out by the HTML parser, which orphans
     the form's inputs (input.form === null) so the form submits empty and
     nothing saves. Keeping the modals at page level (like the Add modal) fixes
     the form-control association. --}}
@foreach($plans as $plan)
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
@endforeach

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
    /*
     * Dynamic plan-feature rows — ONE delegated handler for every modal
     * (each plan's Edit modal + the Add modal). Using delegation on document
     * avoids the previous bugs: a per-include script that double-bound and a
     * singular querySelector that only wired the first modal. New rows are full
     * clones of an existing .feature-row (description + feature_code select +
     * quota), so the parallel features[]/feature_codes[]/feature_quotas[] arrays
     * the server receives always stay the same length and aligned.
     */
    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.add-feature-btn');
        if (addBtn) {
            var container = addBtn.closest('.features-container');
            if (!container) return;
            var list = container.querySelector('.features-list');
            var rows = list.querySelectorAll('.feature-row');
            var template = rows[rows.length - 1];
            if (!template) return;
            var clone = template.cloneNode(true);
            clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
            clone.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
            list.appendChild(clone);
            return;
        }

        var rmBtn = e.target.closest('.remove-feature-btn');
        if (rmBtn) {
            var c = rmBtn.closest('.features-container');
            var list2 = c ? c.querySelector('.features-list') : null;
            var row = rmBtn.closest('.feature-row');
            if (!row || !list2) return;
            if (list2.querySelectorAll('.feature-row').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
                var sel = row.querySelector('select'); if (sel) { sel.selectedIndex = 0; }
            }
        }
    });
</script>
@endpush
