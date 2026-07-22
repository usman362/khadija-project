@extends('layouts.client')
@section('title', 'Post an Event — Event Information')
@include('client.post-event._styles')

@section('content')
<div class="pe-wrap">
    @include('client.post-event._wizard')

    <div class="pe-container pe-main">
        <h1 class="pe-h1">Your Event</h1>
        <p class="pe-sub">Tell us about your event so we understand exactly what you need.</p>

        <form method="POST" action="{{ route('client.post-event.store-info') }}">
            @csrf
            <div class="pe-grid">
                {{-- Main form --}}
                <div>
                    <div class="pe-card">
                        <div class="pe-field">
                            <label class="pe-label">Event Type <span class="pe-req">*</span></label>
                            <x-event-type-picker name="event_type" :selected="old('event_type', $summary['event_type'] ?? null)" />
                        </div>

                        <div class="pe-row">
                            <div class="pe-field">
                                <label class="pe-label">Start Time <span class="pe-req">*</span></label>
                                <input type="time" name="start_time" class="pe-input">
                            </div>
                            <div class="pe-field">
                                <label class="pe-label">End Time <span class="pe-req">*</span></label>
                                <input type="time" name="end_time" class="pe-input">
                            </div>
                        </div>

                        <div class="pe-field">
                            <label class="pe-label">Venue Address <span class="pe-req">*</span></label>
                            <input type="text" name="venue" class="pe-input" placeholder="Enter Venue Address" value="{{ $summary['venue'] ?? '' }}">
                        </div>

                        <div class="pe-row">
                            <div class="pe-field">
                                <label class="pe-label">Guest Count <span class="pe-req">*</span></label>
                                <input type="number" name="guests" class="pe-input" min="1" placeholder="Number of Guests" value="{{ $summary['guests'] ?? '' }}">
                            </div>
                            <div class="pe-field">
                                <label class="pe-label">Estimated Budget <span class="pe-req">*</span></label>
                                <select name="budget" class="pe-select">
                                    <option value="">Select Budget Range</option>
                                    <option>$2,000 – $5,000</option>
                                    <option>$5,000 – $8,000</option>
                                    <option>$8,000 – $10,000</option>
                                    <option>$10,000 – $20,000</option>
                                    <option>$20,000+</option>
                                </select>
                            </div>
                        </div>

                        <div class="pe-field">
                            <label class="pe-label">Color Palette (Optional)</label>
                            <div style="display:flex; gap:8px; align-items:center;">
                                @foreach(['#111827','#f472b6','#fbbf24','#34d399','#60a5fa','#a78bfa'] as $c)
                                    <span style="width:26px;height:26px;border-radius:50%;background:{{ $c }};border:2px solid #fff;box-shadow:0 0 0 1px var(--pe-line);cursor:pointer;"></span>
                                @endforeach
                            </div>
                        </div>

                        <div class="pe-field" style="margin-bottom:4px;">
                            <label class="pe-label">Style, preferences, or any special details</label>
                            <textarea name="notes" class="pe-textarea" placeholder="Tell us about your vision, style preferences, or any special requirements…">{{ $summary['notes'] ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="pe-actions" style="justify-content:flex-end;">
                        <button type="submit" class="pe-btn">Next: Build Your Event
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Right rail --}}
                <aside class="pe-rail">
                    <div class="pe-rail-card pe-rail-why">
                        <h4>✨ Why we need this?</h4>
                        <p class="pe-muted" style="margin:-6px 0 10px;">The more details you give, GigResource IQ™ can find the perfect packages.</p>
                        @foreach(['Find the right packages faster','Get accurate pricing estimates','Ensure availability','Personalized recommendations'] as $why)
                            <div class="pe-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ $why }}
                            </div>
                        @endforeach
                    </div>
                    @include('client.post-event._rail')
                </aside>
            </div>
        </form>
    </div>
</div>
@endsection
