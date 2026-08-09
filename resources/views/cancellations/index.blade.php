@extends($layout)

@section('title', 'Cancellations')

@push('styles')
    @include('disputes._styles')
@endpush

@section('content')
<div class="dsp-head">
    <div>
        <h1 class="dsp-h1">Cancellations &amp; no-shows</h1>
        <p class="dsp-sub">
            Cancellations you have made, and anything reported on a booking you are part of.
            Each one covers a single booking.
        </p>
    </div>
    <a href="{{ route('cancellations.create') }}" class="cl-btn cl-btn-primary">Report something</a>
</div>

@if(session('status'))
    <div class="dsp-flash">{{ session('status') }}</div>
@endif

<div class="dsp-card">
    @if($requests->isEmpty())
        <div class="dsp-empty">Nothing here — no cancellations or no-shows on your bookings.</div>
    @else
        <table class="dsp-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Booking</th>
                    <th>What happened</th>
                    <th>Raised by</th>
                    <th>Status</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $item)
                    <tr>
                        <td><a href="{{ route('cancellations.show', $item) }}" class="dsp-ref">{{ $item->reference }}</a></td>
                        <td>{{ $item->booking?->event?->title ?? 'Booking #' . $item->booking_id }}</td>
                        <td>{{ $item->kindLabel() }}</td>
                        <td>{{ $item->raised_by === auth()->id() ? 'You' : ($item->raiser?->name ?? '—') }}</td>
                        <td><span class="dsp-badge {{ $item->status === 'withdrawn' ? 'dsp-shut' : 'dsp-open' }}">{{ ucfirst($item->status) }}</span></td>
                        <td class="dsp-when">{{ $item->created_at?->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@if($requests->hasPages())
    <div style="margin-top:14px;">{{ $requests->links() }}</div>
@endif
@endsection
