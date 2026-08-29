@extends($layout)

@section('title', $submission->reference)
@section('page-title', $submission->reference)

@push('styles')
    @include('disputes._styles')
@endpush

@section('content')
<div class="dsp-head">
    <div>
        {{-- The reference is the page title and sits in the banner; the
             status badge stays here, because a badge is not a title. --}}
        <div class="dsp-badges">
            <span class="dsp-badge {{ $submission->status === 'withdrawn' ? 'dsp-shut' : ($submission->isAccepted() ? 'dsp-done' : 'dsp-open') }}"
                  style="margin-left:8px;vertical-align:middle;">
                {{ $submission->needsApproval() ? ucfirst($submission->approval_status ?? 'pending') : ucfirst($submission->status) }}
            </span>
        </div>
        <p class="dsp-sub">{{ $submission->title() }}</p>
    </div>
    <a href="{{ route('forms.index') }}" class="cl-btn">All forms</a>
</div>

@if(session('status'))
    <div class="dsp-flash">{{ session('status') }}</div>
@endif

<div class="dsp-two">
    <div>
        <div class="dsp-card">
            <p class="dsp-sec">What was sent</p>
            <dl style="margin:0;">
                @foreach($submission->answers() as $answer)
                    <div class="dsp-row">
                        <dt>{{ $answer['label'] }}</dt>
                        <dd style="max-width:60%;white-space:pre-line;text-align:right;">{{ $answer['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="dsp-hint" style="margin-top:12px;">
                Sent by {{ $submission->submitted_by === auth()->id() ? 'you' : ($submission->submitter?->name ?? 'someone') }}
                on {{ $submission->created_at?->format('M j, Y') }}.
            </p>
        </div>

        @if($submission->approval_note)
            <div class="dsp-card">
                <p class="dsp-sec">Their answer</p>
                <p style="font-size:13.5px;line-height:1.65;white-space:pre-line;margin:0;">{{ $submission->approval_note }}</p>
            </div>
        @endif
    </div>

    <div>
        @if($submission->certification_text)
            <div class="dsp-card">
                <p class="dsp-sec">Signed</p>
                <p style="font-size:12.5px;line-height:1.6;margin:0;color:var(--text-muted);">
                    “{{ $submission->certification_text }}”
                </p>
            </div>
        @endif

        {{-- The Change Order's dual approval. Only the other party answers,
             and only once — a change to a signed agreement is not a change
             until the person it affects says so. --}}
        @if($submission->needsApproval() && $submission->approval_status === 'pending' && $submission->counterparty_id === auth()->id())
            <div class="dsp-card">
                <p class="dsp-sec">Your answer</p>
                <form method="POST" action="{{ route('forms.respond', $submission) }}">
                    @csrf
                    <div class="dsp-field">
                        <label class="dsp-label" for="note">Anything to add</label>
                        <textarea name="note" id="note" class="dsp-area" style="min-height:70px;"></textarea>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" name="decision" value="accepted" class="cl-btn cl-btn-primary">Accept the change</button>
                        <button type="submit" name="decision" value="declined" class="cl-btn">Decline</button>
                    </div>
                </form>
                <p class="dsp-hint" style="margin-top:10px;">
                    If you decline, the original agreement stands exactly as it is.
                </p>
            </div>
        @endif

        @if($submission->status === 'submitted' && $submission->submitted_by === auth()->id())
            <div class="dsp-card">
                <p class="dsp-sec">Changed your mind?</p>
                <form method="POST" action="{{ route('forms.withdraw', $submission) }}">
                    @csrf
                    <button type="submit" class="cl-btn">Withdraw</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
