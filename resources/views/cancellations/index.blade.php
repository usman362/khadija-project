@extends($layout)

@section('title', 'Cancellations')
@section('page-title', 'Cancellations & no-shows')
@section('page-subtitle', 'Cancellations you have made, and anything reported on a booking you are part of. Each one covers a single booking.')

@push('styles')
    @include('disputes._styles')
@endpush

@section('content')
<div class="dsp-head">
    <div>
        {{-- Title and subtitle are in the banner at the top of the page, in both portals. --}}
    </div>
    {{-- "Report something" said nothing, to either side. The create page has
         always known the difference — its own title reads "Cancel a booking"
         for a client and "Report a no-show or cancellation" for a
         professional — so the button that opens it says the same.

         NOT "Cancel the event": this cancels ONE booking. A client with three
         professionals can cancel one and keep the other two, and a button
         promising to cancel the event would be promising something the page
         does not do. Raised with Sir Peter. --}}
    <a href="{{ route('cancellations.create') }}" class="cl-btn cl-btn-primary">
        {{ ($role ?? 'client') === 'professional' ? 'Report a no-show' : 'Cancel a booking' }}
    </a>
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
