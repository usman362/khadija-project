@extends($layout)

@section('title', $request->reference)

@push('styles')
    @include('disputes._styles')
@endpush

@section('content')
<div class="dsp-head">
    <div>
        <h1 class="dsp-h1">
            <span class="dsp-ref">{{ $request->reference }}</span>
            <span class="dsp-badge {{ $request->status === 'withdrawn' ? 'dsp-shut' : 'dsp-open' }}"
                  style="margin-left:8px;vertical-align:middle;">{{ ucfirst($request->status) }}</span>
        </h1>
        <p class="dsp-sub">
            {{ $request->kindLabel() }} · {{ $request->booking?->event?->title ?? 'Booking #' . $request->booking_id }}
        </p>
    </div>
    <a href="{{ route('cancellations.index') }}" class="cl-btn">All cancellations</a>
</div>

@if(session('status'))
    <div class="dsp-flash">{{ session('status') }}</div>
@endif

<div class="dsp-two">
    <div>
        <div class="dsp-card">
            <p class="dsp-sec">What was reported</p>
            <p style="font-size:13.5px;line-height:1.65;white-space:pre-line;margin:0;">{{ $request->reason }}</p>

            @if($request->detail)
                <p style="font-size:13.5px;line-height:1.65;white-space:pre-line;margin:12px 0 0;">{{ $request->detail }}</p>
            @endif

            <p class="dsp-hint" style="margin-top:12px;">
                Raised by {{ $request->raised_by === auth()->id() ? 'you' : ($request->raiser?->name ?? 'the other party') }}
                on {{ $request->created_at?->format('M j, Y') }}.
            </p>
        </div>

        @if($request->occurred_at || $request->waited_minutes !== null)
            <div class="dsp-card">
                <p class="dsp-sec">On the day</p>
                <dl style="margin:0;">
                    @if($request->occurred_at)
                        <div class="dsp-row"><dt>When</dt><dd>{{ $request->occurred_at->format('M j, Y · g:i A') }}</dd></div>
                    @endif
                    @if($request->waited_minutes !== null)
                        <div class="dsp-row"><dt>Waited</dt><dd>{{ $request->waited_minutes }} minutes</dd></div>
                    @endif
                </dl>
            </div>
        @endif

        @if($request->resolution_note)
            <div class="dsp-card">
                <p class="dsp-sec">Outcome</p>
                <p style="font-size:13.5px;line-height:1.65;white-space:pre-line;margin:0;">{{ $request->resolution_note }}</p>
            </div>
        @endif
    </div>

    <div>
        @if($request->hasQuote())
            {{-- The figures as they stood when the request was made. Not
                 recomputed: a later change to the event date would rewrite
                 the number the client was actually shown. --}}
            <div class="dsp-card">
                <p class="dsp-sec">The refund, as quoted</p>
                <dl style="margin:0;">
                    <div class="dsp-row"><dt>Agreed price</dt><dd>${{ number_format($request->quoted_agreed, 2) }}</dd></div>
                    <div class="dsp-row"><dt>Deposit</dt><dd>${{ number_format($request->quoted_deposit, 2) }}</dd></div>
                    <div class="dsp-row"><dt>Remaining balance</dt><dd>${{ number_format($request->quoted_balance, 2) }}</dd></div>
                    <div class="dsp-row"><dt>Refund</dt><dd>${{ number_format($request->quoted_refund, 2) }}</dd></div>
                </dl>
                <p class="dsp-hint" style="margin-top:10px;">
                    {{ $request->policyTierLabel() }}. The deposit is not refundable, so it is not part
                    of this figure.
                </p>
            </div>
        @endif

        <div class="dsp-card">
            <p class="dsp-sec">This report</p>
            <dl style="margin:0;">
                <div class="dsp-row"><dt>Reference</dt><dd class="dsp-ref">{{ $request->reference }}</dd></div>
                <div class="dsp-row"><dt>Booking</dt><dd>#{{ $request->booking_id }}</dd></div>
                <div class="dsp-row"><dt>Status</dt><dd>{{ ucfirst($request->status) }}</dd></div>
            </dl>

            @if($request->certification_text)
                <p class="dsp-hint" style="margin-top:10px;">
                    Signed: “{{ $request->certification_text }}”
                </p>
            @endif
        </div>

        @if($request->disputeCase)
            <div class="dsp-card">
                <p class="dsp-sec">Related case</p>
                <a href="{{ route('disputes.show', $request->disputeCase) }}" class="dsp-ref">
                    {{ $request->disputeCase->reference }}
                </a>
                <p class="dsp-hint" style="margin-top:8px;">This report is part of an open case.</p>
            </div>
        @endif

        @if($request->status === 'submitted' && $request->raised_by === auth()->id())
            <div class="dsp-card">
                <p class="dsp-sec">Changed your mind?</p>
                <form method="POST" action="{{ route('cancellations.withdraw', $request) }}">
                    @csrf
                    <button type="submit" class="cl-btn">Withdraw this report</button>
                </form>
                <p class="dsp-hint" style="margin-top:8px;">
                    Only while nobody has acted on it. The record stays, marked withdrawn.
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
