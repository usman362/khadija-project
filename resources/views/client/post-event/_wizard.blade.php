{{-- Post-Event wizard progress bar. Expects $steps (key=>label) and $current (1-indexed). --}}
<div class="pe-wizard">
    @foreach($steps as $key => $label)
        @php $i = $loop->iteration; $state = $i < $current ? 'done' : ($i === $current ? 'active' : ''); @endphp
        <div class="pe-step {{ $state }}">
            <span class="pe-step-dot">@if($i < $current)✓@else{{ $i }}@endif</span>
            <span class="pe-step-label">{{ $label }}</span>
        </div>
        @unless($loop->last)<span class="pe-step-line {{ $i < $current ? 'done' : '' }}"></span>@endunless
    @endforeach
</div>
