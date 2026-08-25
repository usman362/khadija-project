@extends('layouts.client')

@section('title', 'Book ' . $package->title)
@section('page-title', 'Book this package')
@section('page-subtitle', 'Fixed scope, fixed price — confirm the date and send it.')

@push('styles')
<style>
    .pb-grid { display: grid; grid-template-columns: minmax(0,1fr) 340px; gap: 18px; align-items: start; }
    @media (max-width: 900px) { .pb-grid { grid-template-columns: 1fr; } }
    .pb-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 18px 20px; }
    .pb-card + .pb-card { margin-top: 14px; }
    .pb-h { font-size: 14px; font-weight: 800; color: var(--text-primary); margin: 0 0 12px; }
    .pb-field { margin-bottom: 14px; }
    .pb-field label { display: block; font-size: 12px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
    .pb-field .hint { font-size: 11.5px; color: var(--text-muted); font-weight: 500; margin-top: 4px; }
    .pb-input { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 9px;
                background: var(--bg-input, #fff); color: var(--text-primary); font-size: 13px; font-family: inherit; }
    .pb-input:focus { outline: none; border-color: var(--brand-text); }
    .pb-err { font-size: 11.5px; color: var(--bad-text); font-weight: 600; margin-top: 4px; }
    .pb-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 620px) { .pb-row { grid-template-columns: 1fr; } }
    .pb-check { display: flex; gap: 9px; align-items: flex-start; font-size: 12.5px; color: var(--text-primary); }
    .pb-check input { margin-top: 2px; }
    .pb-submit { width: 100%; padding: 12px 16px; border: 0; border-radius: 10px; background: var(--brand-text, #f97316);
                 color: #fff; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .pb-summary-title { font-size: 15px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; }
    .pb-summary-pro { font-size: 12px; color: var(--text-muted); margin-bottom: 14px; }
    .pb-line { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; padding: 7px 0; border-top: 1px solid var(--border-color); }
    .pb-line:first-of-type { border-top: 0; }
    .pb-line .l { color: var(--text-muted); }
    .pb-line .v { color: var(--text-primary); font-weight: 700; text-align: right; }
    .pb-total { display: flex; justify-content: space-between; align-items: baseline; margin-top: 12px; padding-top: 12px; border-top: 2px solid var(--border-color); }
    .pb-total .l { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .pb-total .v { font-size: 22px; font-weight: 900; color: var(--text-primary); }
    .pb-incl { list-style: none; padding: 0; margin: 10px 0 0; }
    .pb-incl li { position: relative; padding-left: 20px; font-size: 12.5px; color: var(--text-primary); margin-bottom: 6px; line-height: 1.45; }
    .pb-incl li::before { content: '✓'; position: absolute; left: 0; color: var(--ok-text, #10b981); font-weight: 800; }
    .pb-note { background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25); border-radius: 9px;
               padding: 10px 12px; font-size: 12px; color: var(--text-primary); line-height: 1.5; margin-top: 12px; }
    .pb-note strong { font-weight: 800; }
</style>
@endpush

@section('content')
<div class="pb-grid">
    <form method="POST" action="{{ route('client.packages.book.store', $package) }}" class="pb-main">
        @csrf

        <div class="pb-card">
            <h2 class="pb-h">Your event</h2>

            <div class="pb-field">
                <label for="event_title">What is this for?</label>
                <input type="text" id="event_title" name="event_title" class="pb-input"
                       value="{{ old('event_title') }}" placeholder="e.g. Sarah &amp; Tom's wedding reception" required>
                <div class="hint">This is the name you will see it under in My Events.</div>
                @error('event_title')<div class="pb-err">{{ $message }}</div>@enderror
            </div>

            <div class="pb-row">
                <div class="pb-field">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" class="pb-input"
                           min="{{ now()->toDateString() }}"
                           value="{{ old('date', $chosen) }}" required>
                    @error('date')<div class="pb-err">{{ $message }}</div>@enderror
                </div>
                <div class="pb-field">
                    <label for="guests">Guests <span style="font-weight:500;color:var(--text-muted)">(optional)</span></label>
                    <input type="number" id="guests" name="guests" class="pb-input" min="1"
                           value="{{ old('guests') }}" placeholder="{{ $package->guests_max ?: '' }}">
                    @if ($package->guests)
                        <div class="hint">This package covers {{ $package->guests }}.</div>
                    @endif
                    @error('guests')<div class="pb-err">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="pb-field">
                <label for="location">Where <span style="font-weight:500;color:var(--text-muted)">(optional)</span></label>
                <input type="text" id="location" name="location" class="pb-input"
                       value="{{ old('location') }}" placeholder="Venue or city">
                @error('location')<div class="pb-err">{{ $message }}</div>@enderror
            </div>

            <div class="pb-field" style="margin-bottom:0">
                <label for="notes">Anything they should know <span style="font-weight:500;color:var(--text-muted)">(optional)</span></label>
                <textarea id="notes" name="notes" class="pb-input" rows="3"
                          placeholder="Arrival time, parking, a song you want played — anything that does not change the price.">{{ old('notes') }}</textarea>
                @error('notes')<div class="pb-err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="pb-card">
            <h2 class="pb-h">Before you send</h2>

            {{-- A booked date on GigResource is a fact. An empty one is not a
                 claim the professional is free — say only what we know. --}}
            @if ($chosenBusy)
                <div class="pb-note">
                    <strong>Heads up.</strong> {{ $pro?->name }} already has something booked on GigResource on
                    {{ \Illuminate\Support\Carbon::parse($chosen)->format('M j, Y') }}. You can still send this —
                    they will tell you if the date does not work.
                </div>
            @endif

            <label class="pb-check" for="agree">
                <input type="checkbox" id="agree" name="agree" value="1" {{ old('agree') ? 'checked' : '' }} required>
                <span>I have read what is included and I am booking this package at the price shown.</span>
            </label>
            @error('agree')<div class="pb-err">{{ $message }}</div>@enderror

            <div class="pb-note">
                Sending this does not charge you. {{ $pro?->name }} has to accept the date first —
                you will get a message either way, and the amount below is what you will owe once they do.
            </div>

            <button type="submit" class="pb-submit" style="margin-top:14px">
                Send booking request
            </button>
        </div>
    </form>

    <aside>
        <div class="pb-card">
            <p class="pb-summary-title">{{ $package->title }}</p>
            <p class="pb-summary-pro">
                by {{ $pro?->name }}@if($pro?->profile?->city) · {{ $pro->profile->city }}, {{ $pro->profile->state }}@endif
            </p>

            @if ($package->category)
                <div class="pb-line"><span class="l">Category</span><span class="v">{{ $package->category->name }}</span></div>
            @endif
            @if ($package->duration)
                <div class="pb-line"><span class="l">Duration</span><span class="v">{{ $package->duration }}</span></div>
            @endif
            @php
                // `team` is cast to array on the model — printing it raw threw.
                $team = is_array($package->team) ? implode(', ', $package->team) : $package->team;
            @endphp
            @if ($team)
                <div class="pb-line"><span class="l">Team</span><span class="v">{{ $team }}</span></div>
            @endif
            @if ($package->coverage)
                <div class="pb-line"><span class="l">Coverage</span><span class="v">{{ $package->coverage }}</span></div>
            @endif

            <div class="pb-total">
                <span class="l">Package price</span>
                <span class="v">${{ number_format((float) $package->price, 2) }}</span>
            </div>
            @if ($package->price_unit)
                <div style="font-size:11.5px;color:var(--text-muted);text-align:right;margin-top:2px">{{ $package->price_unit }}</div>
            @endif

            {{-- No tiers, no add-ons, no fee line. None of those has been
                 decided, and a number on this screen is a number the client
                 will hold us to. --}}
            <div style="font-size:11.5px;color:var(--text-muted);margin-top:10px;line-height:1.5">
                This is the professional's own listed price for everything below. Nothing is added at this step.
            </div>

            @php $includes = is_array($package->includes) ? $package->includes : array_filter(array_map('trim', explode("\n", (string) $package->includes))); @endphp
            @if (count($includes))
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border-color)">
                    <p style="font-size:12px;font-weight:800;color:var(--text-primary);margin:0">What's included</p>
                    <ul class="pb-incl">
                        @foreach ($includes as $inc)
                            <li>{{ $inc }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <a href="{{ route('public.package', $package) }}"
               style="display:block;text-align:center;margin-top:14px;font-size:12px;font-weight:700;color:var(--brand-text);text-decoration:none">
                ← Back to the package
            </a>
        </div>
    </aside>
</div>
@endsection
