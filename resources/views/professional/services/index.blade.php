@extends('layouts.professional')

@section('title', 'My Services & Pricing')

{{-- What this professional charges, one row per service they offer.

     Choosing WHICH services is on the profile and stays there, so this page
     never shows a service they have not picked and the two cannot disagree
     about the list. --}}

@push('styles')
<style>
    .sp-head { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 18px; }
    .sp-head h1 { font-size: 26px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .sp-head p { font-size: 13px; color: var(--text-secondary); margin: 4px 0 0; line-height: 1.6; max-width: 620px; }
    .sp-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 18px; }
    .sp-row { display: grid; grid-template-columns: minmax(0, 2fr) 120px 130px 110px 120px; gap: 12px; align-items: end;
              padding: 14px 0; border-bottom: 1px solid var(--border-color); }
    .sp-row:last-of-type { border-bottom: none; }
    .sp-name { font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
    .sp-parent { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .sp-lab { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
              color: var(--text-muted); margin-bottom: 4px; }
    .sp-in { width: 100%; font-family: inherit; font-size: 13px; padding: 8px 10px; border-radius: 9px;
             border: 1px solid var(--border-color); background: var(--bg-input, var(--bg-card)); color: var(--text-primary); }
    .sp-foot { display: flex; align-items: center; gap: 14px; margin-top: 18px; }
    .sp-save { background: var(--brand-strong, #2563eb); color: #fff; border: none; border-radius: 10px;
               padding: 11px 20px; font-size: 13.5px; font-weight: 700; cursor: pointer; }
    .sp-note { font-size: 12.5px; color: var(--text-muted); }
    .sp-empty { font-size: 13px; color: var(--text-muted); line-height: 1.6; }
    .sp-empty a { color: var(--brand-text); font-weight: 700; }
    @media (max-width: 900px) { .sp-row { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="sp-head">
    <div>
        <h1>My Services &amp; Pricing</h1>
        <p>
            What you charge for each service you offer. These prices are what Create a Package
            fills in for you, and what a quick answer to a request is worked out from.
            Leave any of them blank and that service simply has no price yet.
        </p>
    </div>
</div>

<div class="sp-card">
    @if($services->isEmpty())
        <p class="sp-empty">
            You have not chosen any services yet, so there is nothing to price.
            Pick them on your <a href="{{ route('professional.profile.index') }}">profile</a> and they appear here.
        </p>
    @else
        <form method="POST" action="{{ route('professional.services.update') }}">
            @csrf
            @method('PATCH')

            @foreach($services as $service)
                <div class="sp-row">
                    <div>
                        <div class="sp-name">{{ $service->name }}</div>
                        @if($service->parent)
                            <div class="sp-parent">{{ $service->parent->name }}</div>
                        @endif
                    </div>
                    <div>
                        <label class="sp-lab" for="price-{{ $service->id }}">Price</label>
                        <input class="sp-in" type="number" step="0.01" min="0" inputmode="decimal"
                               id="price-{{ $service->id }}"
                               name="rows[{{ $service->id }}][price]"
                               value="{{ $service->pivot->price !== null ? rtrim(rtrim(number_format((float) $service->pivot->price, 2, '.', ''), '0'), '.') : '' }}"
                               placeholder="e.g. 1200">
                    </div>
                    <div>
                        <label class="sp-lab" for="unit-{{ $service->id }}">Read as</label>
                        <select class="sp-in" id="unit-{{ $service->id }}" name="rows[{{ $service->id }}][price_unit]">
                            @foreach($units as $key => $label)
                                <option value="{{ $key }}" @selected(($service->pivot->price_unit ?? 'from') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sp-lab" for="hours-{{ $service->id }}">Includes</label>
                        <input class="sp-in" type="number" min="1" max="24"
                               id="hours-{{ $service->id }}"
                               name="rows[{{ $service->id }}][duration_hours]"
                               value="{{ $service->pivot->duration_hours }}" placeholder="hours">
                    </div>
                    <div>
                        <label class="sp-lab" for="extra-{{ $service->id }}">Extra hour</label>
                        <input class="sp-in" type="number" step="0.01" min="0" inputmode="decimal"
                               id="extra-{{ $service->id }}"
                               name="rows[{{ $service->id }}][extra_hour_price]"
                               value="{{ $service->pivot->extra_hour_price !== null ? rtrim(rtrim(number_format((float) $service->pivot->extra_hour_price, 2, '.', ''), '0'), '.') : '' }}"
                               placeholder="optional">
                    </div>
                </div>
            @endforeach

            <div class="sp-foot">
                <button type="submit" class="sp-save">Save pricing</button>
                <span class="sp-note">
                    {{ $priced }} of {{ $services->count() }} {{ $services->count() === 1 ? 'service has' : 'services have' }} a price.
                </span>
            </div>
        </form>
    @endif
</div>
@endsection
