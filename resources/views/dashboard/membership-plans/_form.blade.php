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
        <label class="form-label">Contract Term <span class="text-danger">*</span></label>
        <select name="billing_cycle" class="form-select" required>
            @foreach(['6_month' => '6 Months', '12_month' => '12 Months', '18_month' => '18 Months'] as $val => $label)
                <option value="{{ $val }}" {{ ($plan?->billing_cycle ?? '12_month') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-muted">Flat contract — no monthly rebills.</small>
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
        <div>
            <label class="form-label mb-0"><strong>Plan Features</strong></label>
            <div class="small text-secondary">Add a description for each row. Optionally link it to a programmatic <code>feature_code</code> and set a monthly quota (0 = unlimited).</div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary add-feature-btn">+ Add Feature</button>
    </div>

    @php
        $aiFeatureCodes = \App\Domain\AiFeatures\AiFeatureCode::all();
    @endphp

    <div class="features-list">
        @if($plan && $plan->features->count())
            @foreach($plan->features as $feature)
                <div class="row g-2 mb-2 align-items-center feature-row">
                    <div class="col-md-5">
                        <input type="text" name="features[]" class="form-control form-control-sm" value="{{ $feature->feature }}" placeholder="Feature description">
                    </div>
                    <div class="col-md-4">
                        <select name="feature_codes[]" class="form-select form-select-sm">
                            <option value="">— No gate —</option>
                            @foreach($aiFeatureCodes as $code)
                                <option value="{{ $code }}" @selected($feature->feature_code === $code)>{{ \App\Domain\AiFeatures\AiFeatureCode::label($code) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="feature_quotas[]" class="form-control form-control-sm" value="{{ $feature->quota_monthly }}" placeholder="Quota/mo" min="0" max="999999" title="Monthly quota (0 = unlimited, empty = not applicable)">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-feature-btn w-100">&times;</button>
                    </div>
                </div>
            @endforeach
        @else
            <div class="row g-2 mb-2 align-items-center feature-row">
                <div class="col-md-5">
                    <input type="text" name="features[]" class="form-control form-control-sm" placeholder="Feature description">
                </div>
                <div class="col-md-4">
                    <select name="feature_codes[]" class="form-select form-select-sm">
                        <option value="">— No gate —</option>
                        @foreach($aiFeatureCodes as $code)
                            <option value="{{ $code }}">{{ \App\Domain\AiFeatures\AiFeatureCode::label($code) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="feature_quotas[]" class="form-control form-control-sm" placeholder="Quota/mo" min="0" max="999999">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-feature-btn w-100">&times;</button>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    const container = document.querySelector('.features-container');
    if (!container) return;
    const list = container.querySelector('.features-list');
    const addBtn = container.querySelector('.add-feature-btn');

    addBtn?.addEventListener('click', () => {
        const first = list.querySelector('.feature-row');
        if (!first) return;
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(i => { i.value = ''; });
        clone.querySelectorAll('select').forEach(s => { s.selectedIndex = 0; });
        list.appendChild(clone);
    });

    list.addEventListener('click', e => {
        const btn = e.target.closest('.remove-feature-btn');
        if (!btn) return;
        if (list.querySelectorAll('.feature-row').length > 1) {
            btn.closest('.feature-row').remove();
        } else {
            btn.closest('.feature-row').querySelectorAll('input').forEach(i => i.value = '');
            btn.closest('.feature-row').querySelector('select').selectedIndex = 0;
        }
    });
})();
</script>
@endpush
