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
                Tell us what happened on the day. This goes to our team. It does not close the
                booking or move any money on its own.
            @endif
        </p>
    </div>
    <a href="{{ route('cancellations.index') }}" class="cl-btn">Back</a>
</div>

{{-- Empty only when there is nothing at all to cancel. A client with an
     event and no bookings could not reach this form, which is precisely the
     case event cancellation exists for. --}}
@if($bookings->isEmpty() && $events->isEmpty())
    <div class="dsp-card">
        <div class="dsp-empty">
            {{ $isClient
                ? 'You have no open events or bookings to cancel.'
                : 'You have no active bookings to report on.' }}
        </div>
    </div>
@else
<form method="POST" action="{{ route('cancellations.store') }}">
    @csrf

    <div class="dsp-two">
        <div>
            <div class="dsp-card">
                <p class="dsp-sec">The booking</p>

                {{-- Which of the two forms this is.
                     A booking cancellation and an event cancellation ask for
                     different things — one has a professional and a refund
                     behind it, the other has neither — so the page shows the
                     one being filled in rather than both at once. --}}
                @if($isClient && $events->isNotEmpty())
                    <div class="dsp-field">
                        <label class="dsp-label">What are you cancelling</label>
                        <div class="cx-what">
                            @if($bookings->isNotEmpty())
                                <label><input type="radio" name="kind" value="{{ \App\Models\CancellationRequest::CLIENT_CANCELS }}"
                                              @checked(old('kind', \App\Models\CancellationRequest::CLIENT_CANCELS) === \App\Models\CancellationRequest::CLIENT_CANCELS)>
                                    <span><b>A booking</b><small>A professional is already engaged for this.</small></span></label>
                            @endif
                            <label><input type="radio" name="kind" value="{{ \App\Models\CancellationRequest::CLIENT_CANCELS_EVENT }}"
                                          @checked(old('kind', $bookings->isEmpty() ? \App\Models\CancellationRequest::CLIENT_CANCELS_EVENT : null) === \App\Models\CancellationRequest::CLIENT_CANCELS_EVENT)>
                                <span><b>A whole event</b><small>Takes the request down. An administrator approves it first.</small></span></label>
                        </div>
                    </div>

                    <div class="dsp-field" data-cx-for="{{ \App\Models\CancellationRequest::CLIENT_CANCELS_EVENT }}">
                        <label class="dsp-label" for="event_id">Which event</label>
                        <select name="event_id" id="event_id" class="dsp-select">
                            <option value="">Choose an event…</option>
                            @foreach($events as $ev)
                                <option value="{{ $ev->id }}" @selected(old('event_id') == $ev->id)>
                                    {{ $ev->title }}
                                    @if($ev->starts_at) ({{ $ev->starts_at->format('M j, Y') }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('event_id') <p class="dsp-err">{{ $message }}</p> @enderror
                        <p class="dsp-hint">Your event stays live, and professionals can still reply, until an administrator approves this.</p>
                    </div>
                @endif

                @if($bookings->isNotEmpty())
                <div class="dsp-field" data-cx-for="{{ \App\Models\CancellationRequest::CLIENT_CANCELS }}">
                    <label class="dsp-label" for="booking_id">Which booking</label>
                    <select name="booking_id" id="booking_id" class="dsp-select"
                            onchange="document.querySelectorAll('.cx-quote').forEach(q => q.classList.toggle('is-shown', q.dataset.booking === this.value))">
                        <option value="">Choose a booking…</option>
                        @foreach($bookings as $booking)
                            @php $other = $isClient ? $booking->supplier : $booking->client; @endphp
                            <option value="{{ $booking->id }}" @selected(old('booking_id') == $booking->id)>
                                {{ $booking->event?->title ?? 'Booking #' . $booking->id }}
                               : {{ $other?->name ?? 'Unknown' }}
                                @if($booking->event?->starts_at) ({{ $booking->event->starts_at->format('M j, Y') }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('booking_id') <p class="dsp-err">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- The professional picks from a list of things that happened.
                     For the client the radio above already IS the kind, and two
                     controls posting the same field name would send whichever
                     came last. --}}
                @if(! $isClient || $events->isEmpty())
                    <div class="dsp-field">
                        <label class="dsp-label" for="kind">What happened</label>
                        <select name="kind" id="kind" class="dsp-select" required>
                            @foreach($kinds as $key => $label)
                                @if($isClient && $key === \App\Models\CancellationRequest::CLIENT_CANCELS_EVENT)
                                    @continue   {{-- nothing to cancel: they have no open events --}}
                                @endif
                                <option value="{{ $key }}" @selected(old('kind') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('kind') <p class="dsp-err">{{ $message }}</p> @enderror
                    </div>
                @endif
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

                        {{-- OA-149: the notice ladder describes time BEFORE an
                             event. A booking cancelled months after its date was
                             told "Less than 14 days before the event", which is
                             not short notice — it is no notice, about a date that
                             has gone. The figure is unchanged; what it says about
                             itself is not. --}}
                        @if($quote['after_event'] ?? false)
                            <p class="dsp-hint">
                                This event has already taken place, so the notice
                                bands below do not apply. Someone from GigResource
                                will review this one before anything is refunded.
                            </p>
                        @endif

                        @unless($quote['has_terms'])
                            <p class="dsp-hint">
                                This booking has no signed terms yet, so there is no agreed deposit:
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
                        cancel the booking, release any money, or hold any money: a report is a
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

@push('styles')
<style>
    .cx-what { display: grid; gap: 8px; }
    .cx-what label { display: flex; gap: 9px; align-items: flex-start; border: 1.5px solid var(--border-color);
        border-radius: 10px; padding: 10px 12px; cursor: pointer; }
    .cx-what label:has(input:checked) { border-color: var(--brand, #f97316); background: rgba(249,115,22,.06); }
    .cx-what input { margin-top: 3px; }
    .cx-what b { display: block; font-size: 13px; color: var(--text-primary); }
    .cx-what small { display: block; font-size: 11.5px; color: var(--text-muted); line-height: 1.4; margin-top: 1px; }
</style>
@endpush

@push('scripts')
<script>
/* Show the half of the form that belongs to what they are cancelling.
   Both halves are in the page so neither needs fetching, and the one that is
   hidden is disabled as well — a hidden field still posts. */
(function () {
    var picks = document.querySelectorAll('input[name="kind"][type="radio"]');
    if (! picks.length) return;

    function sync() {
        var chosen = document.querySelector('input[name="kind"]:checked');
        if (! chosen) return;

        document.querySelectorAll('[data-cx-for]').forEach(function (block) {
            var mine = block.dataset.cxFor === chosen.value;

            block.hidden = ! mine;
            block.querySelectorAll('select, input').forEach(function (field) {
                field.disabled = ! mine;
                // Required only while it is the question being asked.
                if (mine) { field.setAttribute('required', 'required'); }
                else { field.removeAttribute('required'); }
            });
        });
    }

    picks.forEach(function (p) { p.addEventListener('change', sync); });
    sync();
})();
</script>
@endpush

@endsection
