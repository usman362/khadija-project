<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Plan Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ $plan?->name }}" required placeholder="e.g. Basic, Professional, Enterprise">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Price <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="price" class="form-control" value="{{ $plan?->price ?? '0' }}" required min="0" step="0.01">
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Billing Cycle <span class="text-danger">*</span></label>
        <select name="billing_cycle" class="form-select" required>
            @foreach(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly', 'one_time' => 'One Time'] as $val => $label)
                <option value="{{ $val }}" {{ ($plan?->billing_cycle ?? 'monthly') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2" placeholder="Short description of the plan">{{ $plan?->description }}</textarea>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Max Events</label>
        <input type="number" name="max_events" class="form-control" value="{{ $plan?->max_events }}" min="1" placeholder="Leave empty for unlimited">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Max Bookings</label>
        <input type="number" name="max_bookings" class="form-control" value="{{ $plan?->max_bookings }}" min="1" placeholder="Leave empty for unlimited">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Duration (days)</label>
        <input type="number" name="duration_days" class="form-control" value="{{ $plan?->duration_days }}" min="1" placeholder="Leave empty for unlimited">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Sort Order</label>
        <input type="number" name="sort_order" class="form-control" value="{{ $plan?->sort_order ?? 0 }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Badge Text</label>
        <input type="text" name="badge_text" class="form-control" value="{{ $plan?->badge_text }}" placeholder="e.g. Most Popular">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Badge Color</label>
        <select name="badge_color" class="form-select">
            <option value="">None</option>
            @foreach(['primary' => 'Blue', 'success' => 'Green', 'warning' => 'Yellow', 'danger' => 'Red', 'info' => 'Cyan', 'dark' => 'Dark'] as $val => $label)
                <option value="{{ $val }}" {{ $plan?->badge_color === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3 d-flex flex-column justify-content-end gap-2">
        <div class="form-check">
            <input type="hidden" name="has_chat" value="0">
            <input class="form-check-input" type="checkbox" name="has_chat" value="1" id="has_chat_{{ $plan?->id ?? 'new' }}" {{ ($plan?->has_chat ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="has_chat_{{ $plan?->id ?? 'new' }}">Chat Access</label>
        </div>
        <div class="form-check">
            <input type="hidden" name="has_priority_support" value="0">
            <input class="form-check-input" type="checkbox" name="has_priority_support" value="1" id="priority_{{ $plan?->id ?? 'new' }}" {{ $plan?->has_priority_support ? 'checked' : '' }}>
            <label class="form-check-label" for="priority_{{ $plan?->id ?? 'new' }}">Priority Support</label>
        </div>
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $plan?->id ?? 'new' }}" {{ ($plan?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="active_{{ $plan?->id ?? 'new' }}">Active</label>
        </div>
        <div class="form-check">
            <input type="hidden" name="is_featured" value="0">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featured_{{ $plan?->id ?? 'new' }}" {{ $plan?->is_featured ? 'checked' : '' }}>
            <label class="form-check-label" for="featured_{{ $plan?->id ?? 'new' }}">Featured</label>
        </div>
    </div>
</div>

<hr>

<div class="features-container">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0"><strong>Plan Features</strong></label>
        <button type="button" class="btn btn-sm btn-outline-primary add-feature-btn">+ Add Feature</button>
    </div>
    <div class="features-list">
        @if($plan && $plan->features->count())
            @foreach($plan->features as $feature)
                <div class="d-flex gap-2 mb-2 align-items-center">
                    <input type="text" name="features[]" class="form-control form-control-sm" value="{{ $feature->feature }}">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-feature-btn">&times;</button>
                </div>
            @endforeach
        @else
            <div class="d-flex gap-2 mb-2 align-items-center">
                <input type="text" name="features[]" class="form-control form-control-sm" placeholder="Feature description">
                <button type="button" class="btn btn-sm btn-outline-danger remove-feature-btn">&times;</button>
            </div>
        @endif
    </div>
</div>
