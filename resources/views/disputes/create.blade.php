@extends($layout)

@section('title', 'File a dispute')

@php
    /*
     * Rule R34 §11's Forms Library, rendered — the client_filing and
     * professional_filing forms, which differ by exactly one field.
     *
     * What this page deliberately does not do:
     *
     *   It does not ask what outcome you want. The standard is conformance to
     *   the agreed terms (§2), not satisfaction, and a form that opens with
     *   "what would you like to happen" invites an answer the process cannot
     *   promise to give.
     *
     *   It does not state a deadline. §12 holds the filing window for
     *   attorney review, and Virginia treats deviating from your own
     *   published process as a standalone violation — so a helpful "file
     *   within 14 days" line would publish a policy nobody has approved.
     *
     *   It does not let the filer set severity. That is intake's call (§3),
     *   and a filer who could tick "Fraud" would route their own case past
     *   the direct-resolution step §2 requires.
     */
@endphp

@push('styles')
    @include('disputes._styles')
@endpush

@section('content')
<div class="dsp-head">
    <div>
        <h1 class="dsp-h1">File a dispute</h1>
        <p class="dsp-sub">
            One booking per case. Tell us what happened — our team compares what was
            delivered against what was agreed in the contract.
        </p>
    </div>
    <a href="{{ route('disputes.index') }}" class="cl-btn">Back</a>
</div>

@if($bookings->isEmpty())
    <div class="dsp-card">
        <div class="dsp-empty">
            You have no confirmed or completed bookings, so there is nothing to dispute yet.
        </div>
    </div>
@else
<form method="POST" action="{{ route('disputes.store') }}">
    @csrf

    <div class="dsp-two">
        <div>
            <div class="dsp-card">
                <p class="dsp-sec">The booking</p>

                <div class="dsp-field">
                    <label class="dsp-label" for="booking_id">Which booking is this about?</label>
                    <select name="booking_id" id="booking_id" class="dsp-select" required>
                        <option value="">Choose a booking…</option>
                        @foreach($bookings as $booking)
                            @php $other = $booking->client_id === auth()->id() ? $booking->supplier : $booking->client; @endphp
                            <option value="{{ $booking->id }}" @selected(old('booking_id') == $booking->id)>
                                {{ $booking->event?->title ?? 'Booking #' . $booking->id }}
                                — {{ $other?->name ?? 'Unknown' }}
                                ({{ $booking->booked_at?->format('M j, Y') ?? $booking->created_at?->format('M j, Y') }})
                            </option>
                        @endforeach
                    </select>
                    <p class="dsp-hint">
                        If your event had several professionals, pick only the one you have a
                        problem with. The others are not affected.
                    </p>
                    @error('booking_id') <p class="dsp-err">{{ $message }}</p> @enderror
                </div>

                <div class="dsp-field">
                    <label class="dsp-label" for="taxonomy">What went wrong?</label>
                    <select name="taxonomy" id="taxonomy" class="dsp-select" required>
                        <option value="">Choose one…</option>
                        @foreach($taxonomy as $key => $label)
                            <option value="{{ $key }}" @selected(old('taxonomy', $chosen ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('taxonomy') <p class="dsp-err">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="dsp-card">
                <p class="dsp-sec">What happened</p>

                <div class="dsp-field">
                    <label class="dsp-label" for="summary">Describe it in your own words</label>
                    <textarea name="summary" id="summary" class="dsp-area" required
                              placeholder="What was agreed, and what actually happened.">{{ old('summary') }}</textarea>
                    <p class="dsp-hint">
                        Dates, times and what was agreed help most. You can add photos, invoices
                        and other files once the case is open.
                    </p>
                    @error('summary') <p class="dsp-err">{{ $message }}</p> @enderror
                </div>

                {{-- Only the professional is asked this. A conformance review
                     compares delivery against the agreed scope, and the
                     professional is the one who can describe the first half
                     of that comparison. --}}
                @if($filing === 'professional')
                    <div class="dsp-field">
                        <label class="dsp-label" for="work_performed">What you delivered</label>
                        <textarea name="work_performed" id="work_performed" class="dsp-area"
                                  placeholder="What you did on the day, and anything the client asked for that was not in the contract.">{{ old('work_performed') }}</textarea>
                    </div>
                @endif

                <div class="dsp-field">
                    <label class="dsp-label">Have you already raised this with the other party?</label>
                    <label style="display:block;font-size:13.5px;margin-bottom:5px;">
                        <input type="radio" name="attempted_direct" value="yes" @checked(old('attempted_direct') === 'yes') required>
                        Yes, we have talked about it
                    </label>
                    <label style="display:block;font-size:13.5px;">
                        <input type="radio" name="attempted_direct" value="no" @checked(old('attempted_direct') === 'no')>
                        No, not yet
                    </label>
                    <p class="dsp-hint">
                        Either answer is fine — it does not stop you filing. Most cases start
                        with the two of you trying to settle it directly.
                    </p>
                    @error('attempted_direct') <p class="dsp-err">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- §1 — this checkbox is an electronic signature under ESIGN and
                 each state's UETA. It is never pre-ticked, and the wording
                 shown here is the wording stored with the case. --}}
            <div class="dsp-card">
                <label class="dsp-cert">
                    <input type="checkbox" name="certify_truthful" value="1" required>
                    <span>
                        I certify that the information and any files I have provided are true and
                        accurate to the best of my knowledge, and that I have not altered or
                        fabricated any of them.
                    </span>
                </label>
                @error('certify_truthful') <p class="dsp-err">{{ $message }}</p> @enderror

                <div style="margin-top:14px;">
                    <button type="submit" class="cl-btn cl-btn-primary">Open the case</button>
                </div>
            </div>
        </div>

        <div>
            <div class="dsp-card">
                <p class="dsp-sec">What happens next</p>
                <ol style="font-size:13px;line-height:1.65;padding-left:18px;margin:0;color:var(--text-muted);">
                    <li><strong style="color:var(--text-primary);">Direct resolution.</strong>
                        You and the other party try to settle it, with us in the middle. Most cases end here.</li>
                    <li><strong style="color:var(--text-primary);">Platform review.</strong>
                        If that does not work, our team compares what was delivered against the
                        agreed contract terms.</li>
                    <li><strong style="color:var(--text-primary);">Decision.</strong>
                        You both get a written decision with the reasoning behind it.</li>
                    <li><strong style="color:var(--text-primary);">Outside escalation.</strong>
                        If the Terms of Service allow it, the one step after that.</li>
                </ol>
            </div>

            <div class="dsp-card">
                <p class="dsp-sec">About the money</p>
                <p style="font-size:13px;line-height:1.65;margin:0;color:var(--text-muted);">
                    Filing pauses the pending release for <em>this booking only</em>. Other
                    professionals on the same event are not affected, and money already paid out
                    is not touched. The deposit is non-refundable, as it is in every other
                    situation — only the balance beyond it is ever in question.
                </p>
            </div>
        </div>
    </div>
</form>
@endif
@endsection
