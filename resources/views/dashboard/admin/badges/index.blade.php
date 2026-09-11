@extends('layouts.dashboard')
@section('title', 'Badges')
@section('content')
<style>
    .bg-sec { margin-bottom: 26px; }
    .bg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
    .bg-item { border: 1px solid var(--bs-border-color, #e5e7eb); border-radius: 14px; padding: 16px; background: var(--bs-body-bg, #fff); }
    .bg-pair { display: flex; gap: 22px; align-items: flex-start; margin-bottom: 12px; }
    .bg-pair small { display: block; text-align: center; font-size: 11px; color: #6b7280; margin-top: 4px; }
    .bg-item h6 { font-weight: 800; margin: 0 0 4px; }
    .bg-item p { font-size: 13px; color: #6b7280; margin: 0; line-height: 1.5; }
    .bg-meta { font-size: 12px; color: #6b7280; margin-top: 8px; }
    .bg-meta code { font-size: 11.5px; }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="award" class="me-2" style="width:24px;height:24px;"></i> Badges</h4>
        <p class="text-secondary mb-0">
            Every badge on GigResource, shown earned and not yet earned, with what earns it.
            Colours and icons come from settings, so they can change without rebuilding anything.
        </p>
    </div>
</div>

{{-- Client badges (PM-14) --}}
<div class="bg-sec">
    <h5 class="fw-bold mb-1">Client badges</h5>
    <p class="text-secondary small mb-3">Shown on the client's own profile. Awarded automatically.</p>
    <div class="bg-grid">
        @foreach($client as $b)
            <div class="bg-item">
                <div class="bg-pair">
                    <div><x-hex-badge :icon="$b['icon']" :colour="$b['colour'] ?? '#2563eb'" :size="56" /><small>Earned</small></div>
                    <div><x-hex-badge :icon="$b['icon']" :colour="$b['colour'] ?? '#2563eb'" :earned="false" :size="56" /><small>Not yet</small></div>
                </div>
                <h6>{{ $b['name'] }}</h6>
                <p>{{ $b['blurb'] }}</p>
                <div class="bg-meta">Colour <code>{{ $b['colour'] ?? '#2563eb' }}</code></div>
            </div>
        @endforeach
    </div>
</div>

{{-- Professional badges --}}
<div class="bg-sec">
    <h5 class="fw-bold mb-1">Professional badges</h5>
    <p class="text-secondary small mb-3">Shown on Browse and on the professional's profile.</p>
    <div class="bg-grid">
        @foreach($professional as $b)
            <div class="bg-item">
                <div class="bg-pair">
                    <div><x-hex-badge :icon="$b['icon']" :colour="$b['colour']" :size="56" /><small>Earned</small></div>
                    <div><x-hex-badge :icon="$b['icon']" :colour="$b['colour']" :earned="false" :size="56" /><small>Not yet</small></div>
                </div>
                <h6>{{ $b['name'] }}</h6>
                <p>{{ $b['blurb'] }}</p>
                <div class="bg-meta">Colour <code>{{ $b['colour'] }}</code></div>
            </div>
        @endforeach
    </div>
</div>

{{-- Influencer badges --}}
@if(! empty($influencer))
    <div class="bg-sec">
        <h5 class="fw-bold mb-1">Influencer badges</h5>
        <p class="text-secondary small mb-3">
            Shown here as hexagons for review. The influencer portal still draws these in its older style
            until the Influencer badge spec (PM-14, third in line) is decided.
        </p>
        <div class="bg-grid">
            @foreach($influencer as $slug => $b)
                @php $letter = mb_strtoupper(mb_substr($b['label'] ?? $slug, 0, 1)); @endphp
                <div class="bg-item">
                    <div class="bg-pair">
                        <div><x-hex-badge :icon="$letter" :colour="$b['color'] ?? '#6b7280'" :size="56" /><small>Earned</small></div>
                        <div><x-hex-badge :icon="$letter" :colour="$b['color'] ?? '#6b7280'" :earned="false" :size="56" /><small>Not yet</small></div>
                    </div>
                    <h6>{{ $b['label'] ?? $slug }}</h6>
                    <p>{{ $b['desc'] ?? '' }}</p>
                    <div class="bg-meta">Colour <code>{{ $b['color'] ?? '' }}</code> · icon <code>{{ $b['icon'] ?? '' }}</code></div>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
