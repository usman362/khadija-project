@extends($layout)

@section('title', $role === 'client' ? 'Cancel a booking' : 'Report a no-show or cancellation')
{{-- $role comes from the controller; $isClient is derived further down in
     this file, so it does not exist yet at this point. --}}
@section('page-title', ($role ?? '') === 'client' ? 'Cancel a booking' : 'Report a no-show or cancellation')

@php
    /*
     * Checklist row 155 — the two forms, from one template.
     *
     * The client's version shows the refund the policy produces. The
     * professional's shows no figure at all, and that is deliberate: the
     * Cancellation & Refund Policy covers client cancellations and puts
     * professional-side money out of scope with no spec written. A number
     * here would be a refund rule this page invented.
     */
    $isClient = $role === 'client';
@endphp

@push('styles')
    @include('disputes._styles')
    <style>
        .cx-tiers { width:100%; border-collapse:collapse; font-size:13px; }
        .cx-tiers th, .cx-tiers td { text-align:left; padding:8px 10px 8px 0; border-top:1px solid var(--border-color); }
        .cx-tiers th { border-top:0; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted); }
        .cx-quote { display:none; }
        .cx-quote.is-shown { display:block; }
        .cx-fig { display:flex; justify-content:space-between; padding:7px 0; border-top:1px solid var(--border-color); font-size:13.5px; }
        .cx-fig:first-of-type { border-top:0; }
        .cx-fig b { font-weight:800; }
    </style>
@endpush

@section('content')
<div class="dsp-head">
    <div>
        {{-- Title is in the banner; it says which side is reading. --}}
        <p class="dsp-sub">
            @if($isClient)
                One booking at a time. Cancelling one professional does not affect anyone else
                working on the same event.
            @else
                Tell us what happened on the day. This goes to our team — it does not close the
                booking or move any money on its own.
            @endif
        </p>
    </div>
    <a href="{{ route('cancellations.index') }}" class="cl-btn">Back</a>
</div>

@if($bookings->isEmpty())
    <div class="dsp-card">
        <div class="dsp-empty">You have no active bookings to report on.</div>
    </div>
@else
<form method="POST" action="{{ route('cancellations.store') }}">
    @csrf

    <div class="dsp-two">
        <div>
            <div class="dsp-card">
                <p class="dsp-sec">The booking</p>

                <div class="dsp-field">
                    <label class="dsp-label" for="booking_id">Which booking</label>
                    <select name="booking_id" id="booking_id" class="dsp-select" required
                            onchange="document.querySelectorAll('.cx-quote').forEach(q => q.classList.toggle('is-shown', q.dataset.booking === this.value))">
                        <option value="">Choose a booking…</option>
                        @foreach($bookings as $booking)
                            @php $other = $isClient ? $booking->supplier : $booking->client; @endphp
                            <option value="{{ $booking->id }}" @selected(old('booking_id') == $booking->id)>
                                {{ $booking->event?->title ?? 'Booking #' . $booking->id }}
                                — {{ $other?->name ?? 'Unknown' }}
                                @if($booking->event?->starts_at) ({{ $booking->event->starts_at->format('M j, Y') }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('booking_id') <p class="dsp-err">{{ $message }}</p> @enderror
                </div>

                <div class="dsp-field">
                    <label class="dsp-label" for="kind">What happened</label>
                    <select name="kind" id="kind" class="dsp-select" required>
                        @foreach($kinds as $key => $label)
                            <option value="{{ $key }}" @selected(old('kind') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('kind') <p class="dsp-err">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="dsp-card">
                <p class="dsp-sec">{{ $isClient ? 'Why you are cancelling' : 'What happened' }}</p>

                <div class="dsp-field">
                    <label class="dsp-label" for="reason">In your own words</label>
                    <textarea name="reason" id="reason" class="dsp-area" required>{{ old('reason') }}</textarea>
                    @error('reason') <p class="dsp-err">{{ $message }}</p> @enderror
                </div>

                @unless($isClient)
                    {{-- A no-show happens at a time and involves waiting. Both
                         are facts a reviewer needs and neither can be
                         reconstructed later from memory. --}}
                    <div class="dsp-field">
                        <label class="dsp-label" for="occurred_at">When it happened</label>
                        <input type="datetime-local" name="occurred_at" id="occurred_at" class="dsp-input"
                               value="{{ old('occurred_at') }}">
                    </div>
                    <div class="dsp-field">
                        <label class="dsp-label" for="waited_minutes">How long you waited (minutes)</label>
                        <input type="number" name="waited_minutes" id="waited_minutes" class="dsp-input"
                               min="0" max="1440" value="{{ old('waited_minutes') }}">
                        <p class="dsp-hint">Leave blank if it does not apply.</p>
                    </div>
                @endunless

                <div class="dsp-field">
                    <label class="dsp-label" for="detail">Anything else</label>
                    <textarea name="detail" id="detail" class="dsp-area" style="min-height:80px;">{{ old('detail') }}</textarea>
                </div>
            </div>

            <div class="dsp-card">
                <label class="dsp-cert">
                    <input type="checkbox" name="certified" value="1" required>
                    <span>
                        @if($isClient)
                            I understand the deposit is not refundable, and that the refund shown is
                            calculated on the remaining balance only.
                        @else
                            I certify that this account of what happened is true and accurate to the
                            best of my knowledge.
                        @endif
                    </span>
                </label>
                @error('certified') <p class="dsp-err">{{ $message }}</p> @enderror

                <div style="margin-top:14px;">
                    <button type="submit" class="cl-btn cl-btn-primary">
                        {{ $isClient ? 'Cancel this booking' : 'Send the report' }}
                    </button>
                </div>
            </div>
        </div>

        <div>
            @if($isClient)
                {{-- What each booking would actually return, computed from the
                     policy and shown before the client commits — not after. --}}
                @foreach($quotes as $bookingId => $quote)
                    <div class="dsp-card cx-quote" data-booking="{{ $bookingId }}">
                        <p class="dsp-sec">What you would get back</p>
                        <div class="cx-fig"><span>Agreed price</span><b>${{ number_format($quote['agreed'], 2) }}</b></div>
                        <div class="cx-fig"><span>Deposit (not refundable)</span><b>${{ number_format($quote['deposit'], 2) }}</b></div>
                        <div class="cx-fig"><span>Remaining balance</span><b>${{ number_format($quote['balance'], 2) }}</b></div>
                        <div class="cx-fig" style="border-top:2px solid var(--border-color);">
                            <span>Refund to you</span><b>${{ number_format($quote['refund'], 2) }}</b>
                        </div>
                        <p class="dsp-hint">{{ $quote['tier'] }}.</p>
                        @unless($quote['has_terms'])
                            <p class="dsp-hint">
                                This booking has no signed terms yet, so there is no agreed deposit —
                                the figures above use the quoted price.
                            </p>
                        @endunless
                    </div>
                @endforeach

                <div class="dsp-card">
                    <p class="dsp-sec">How the refund is worked out</p>
                    <table class="cx-tiers">
                        <thead><tr><th>When you cancel</th><th>Balance refunded</th></tr></thead>
                        <tbody>
                            @foreach($tiers as $tier)
                                <tr>
                                    <td>{{ $tier['label'] }}</td>
                                    <td>{{ $tier['share'] == 0 ? 'None' : (int) ($tier['share'] * 100) . '%' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="dsp-kv-note dsp-hint">
                        The deposit is never refunded, whenever you cancel. It holds the professional's
                        date, and that date comes off the market the moment it is paid. Refunds go back
                        to the card you paid with.
                    </p>
                </div>
            @else
                <div class="dsp-card">
                    <p class="dsp-sec">What happens next</p>
                    <p style="font-size:13px;line-height:1.65;color:var(--text-muted);margin:0;">
                        Our team reads your report and contacts both of you. Sending it does not
                        cancel the booking, release any money, or hold any money — a report is a
                        record of what happened, not a decision about it.
                    </p>
                    <p class="dsp-hint" style="margin-top:10px;">
                        If there is money in dispute, this can become a formal case, and your report
                        goes with it.
                    </p>
                </div>
            @endif
        </div>
    </div>
</form>
@endif
@endsection
