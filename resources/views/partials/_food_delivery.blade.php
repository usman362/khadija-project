{{--
    How is the food getting there?

    Asked on requests that include a catering or bar service, and nowhere else.
    Sir Peter is considering an affiliate arrangement with a delivery service;
    the only honest way to size that is to ask the clients who would use it.

    $mode  string|null  the answer already given, if any
    $live  bool         watch the service picker on this page and show/hide as
                        services are ticked. False where the services were
                        chosen on an earlier step and cannot change here.
--}}
@php
    $fd    = \App\Domain\Requests\FoodDelivery::class;
    $live  = $live ?? false;
    $mode  = $mode ?? null;
    $shown = $shown ?? true;
@endphp

@once
@push('styles')
<style>
    .fd-block { border: 1.5px solid var(--border-color, #e5e7eb); border-radius: 12px; padding: 15px 16px; background: var(--bg-card, #fff); margin-top: 16px; }
    .fd-block[hidden] { display: none !important; }
    .fd-block > h4 { margin: 0 0 3px; font-size: 14px; font-weight: 800; color: var(--text-primary, #111827); }
    .fd-block > p.fd-lede { margin: 0 0 12px; font-size: 12.5px; color: var(--text-muted, #6b7280); line-height: 1.5; }
    .fd-opts { display: grid; gap: 8px; }
    .fd-opt { display: flex; align-items: flex-start; gap: 10px; border: 1.5px solid var(--border-color, #e5e7eb); border-radius: 10px; padding: 11px 13px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--text-secondary, #374151); background: var(--bg-card, #fff); }
    .fd-opt:hover { border-color: #f97316; }
    .fd-opt input { margin: 2px 0 0; accent-color: #f97316; flex-shrink: 0; }
    .fd-opt.sel { border-color: #f97316; background: rgba(249,115,22,.07); color: #ea580c; }
    .fd-opt .fd-note { display: block; font-weight: 500; font-size: 11.5px; color: var(--text-muted, #6b7280); margin-top: 2px; }
</style>
@endpush
@endonce

<div class="fd-block" data-food-delivery @if(! $shown) hidden @endif>
    <h4>How should the food get there?</h4>
    <p class="fd-lede">Professionals price delivery differently from collection, so saying now keeps the quotes comparable.</p>

    <div class="fd-opts">
        @foreach($fd::CHOICES as $value => $label)
            <label class="fd-opt {{ $mode === $value ? 'sel' : '' }}">
                <input type="radio" name="delivery_mode" value="{{ $value }}" @checked($mode === $value)>
                <span>
                    {{ $label }}
                    @if($value === $fd::COURIER_WANTED)
                        <span class="fd-note">We do not offer this yet. Choosing it tells us you want it.</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>

    @error('delivery_mode')<p class="bw-error" style="color:#dc2626;font-size:12px;margin:8px 0 0;">{{ $message }}</p>@enderror
</div>

@push('scripts')
<script>
// Ticking a radio marks its card, the same as every other choice on the form.
document.addEventListener('change', function (e) {
    if (e.target.name !== 'delivery_mode') return;
    document.querySelectorAll('[data-food-delivery] .fd-opt').forEach(function (o) { o.classList.remove('sel'); });
    e.target.closest('.fd-opt').classList.add('sel');
});
</script>
@endpush

@if($live)
@push('scripts')
<script>
/*
 * Show the question the moment a food service is ticked.
 *
 * Keyed on the service CATEGORY each service sits under (data-parent, already
 * rendered by the service picker), not on the service name — "Food Truck
 * Booking" has no word in it that a name check would catch, and a name check
 * would ask about delivery for "Food Photography".
 */
(function () {
    var FOOD = @json(\App\Domain\Requests\FoodDelivery::foodCategoryIds());
    var block = document.querySelector('[data-food-delivery]');
    if (! block || ! FOOD.length) return;

    function anyFoodPicked() {
        return Array.prototype.some.call(
            document.querySelectorAll('.svc-item input:checked'),
            function (input) {
                var parent = parseInt(input.closest('.svc-item').dataset.parent, 10);
                return FOOD.indexOf(parent) !== -1;
            }
        );
    }

    function sync() {
        var show = anyFoodPicked();
        block.hidden = ! show;

        // A hidden question must not submit an answer. Somebody who picks
        // catering, answers, then swaps to a photographer would otherwise file
        // a delivery preference for a photo shoot.
        if (! show) {
            block.querySelectorAll('input[type=radio]').forEach(function (r) {
                r.checked = false;
                r.closest('.fd-opt').classList.remove('sel');
            });
        }
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches('.svc-item input')) sync();
    });

    sync();
})();
</script>
@endpush
@endif
