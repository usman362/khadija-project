@extends($layout)

@section('title', 'Disputes')

@php
    use App\Domain\Disputes\DisputeStates;

    /*
     * Rule R34 Phase 2 — a party's own cases.
     *
     * Both parties get the same list. Nothing here says who was at fault,
     * because §7 is clear that filing is not a finding, and a list that put
     * "disputed" next to a professional's name would be a public score
     * moving on an unproven allegation.
     */
    $badge = fn ($state) => match ($state) {
        DisputeStates::DECIDED, DisputeStates::CLOSED       => 'dsp-done',
        DisputeStates::FORMAL_INVESTIGATION,
        DisputeStates::OUTSIDE_ESCALATION                   => 'dsp-review',
        DisputeStates::WITHDRAWN, DisputeStates::EXPIRED    => 'dsp-shut',
        default                                             => 'dsp-open',
    };
@endphp

@push('styles')
    @include('disputes._styles')
@endpush

@section('content')
<div class="dsp-head">
    <div>
        <h1 class="dsp-h1">Disputes</h1>
        <p class="dsp-sub">
            Cases you are part of. Each one covers a single booking — if you have a problem
            with two professionals on the same event, that is two separate cases.
        </p>
    </div>
    <a href="{{ route('disputes.create') }}" class="cl-btn cl-btn-primary">File a dispute</a>
</div>

@if(session('status'))
    <div class="dsp-flash">{{ session('status') }}</div>
@endif

<div class="dsp-card">
    @if($cases->isEmpty())
        <div class="dsp-empty">
            You have no disputes.<br>
            Most problems are settled by talking to the other side first — that is also the
            first step here.
        </div>
    @else
        <table class="dsp-table">
            <thead>
                <tr>
                    <th>Case</th>
                    <th>Booking</th>
                    <th>Other party</th>
                    <th>What it is about</th>
                    <th>Status</th>
                    <th>Opened</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cases as $case)
                    @php $other = $case->client_id === auth()->id() ? $case->professional : $case->client; @endphp
                    <tr>
                        <td>
                            <a href="{{ route('disputes.show', $case) }}" class="dsp-ref">{{ $case->reference }}</a>
                        </td>
                        <td>{{ $case->booking?->event?->title ?? 'Booking #' . $case->booking_id }}</td>
                        <td>{{ $other?->name ?? '—' }}</td>
                        <td>{{ $case->taxonomyLabel() }}</td>
                        <td><span class="dsp-badge {{ $badge($case->state) }}">{{ $case->stateLabel() }}</span></td>
                        <td class="dsp-when">{{ $case->created_at?->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@if($cases->hasPages())
    <div style="margin-top:14px;">{{ $cases->links() }}</div>
@endif
@endsection
