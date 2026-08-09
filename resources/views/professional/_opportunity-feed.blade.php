{{--
    Opportunity Feed — Rule R61, Option B, locked 2026-08-07.

    Replaces the Emergency Gigs card, which was a hardcoded "DJ Needed
    Tonight" with an invented payout, an invented countdown, and an Accept
    Now button wired to nothing.

    Two blocks, in this order and visibly apart: the professional's own
    services first, then work that is RELATED — same category, a different
    service, read off the R45 taxonomy. Relatedness is structural, never a
    Fit Score threshold; the memo's arithmetic is why, and the class comment
    on App\Support\OpportunityFeed carries it.

    The related block is deliberately non-actionable. R61 keeps Bid and
    Respond gated to actually-listed services, and R38's finding 7 says the
    same the other way round: search hides what is ineligible, the feed shows
    related-but-non-actionable. Labelling it matters — a professional who taps
    a percentage and lands on a trade they do not work in stops trusting the
    number on every other card too.

    @param array $feed  ['listed' => Collection, 'related' => Collection, 'hasServices' => bool]
    @param bool  $myServicesOnly
--}}
<div class="pd-card of-card">
    <div class="pd-card-head">
        <span class="pd-card-ico c-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <span class="pd-card-title">Opportunity Feed</span>
        <a href="{{ route('professional.bidding-board.index') }}" class="pd-card-link">
            Bidding Board
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
    </div>

    {{-- The toggle R61 asks for. Default is services-first-then-related;
         narrowing is the professional's choice, not ours. --}}
    <div class="of-toggle">
        <a href="{{ route('professional.dashboard') }}" class="of-chip {{ $myServicesOnly ? '' : 'on' }}">Services first</a>
        <a href="{{ route('professional.dashboard', ['my_services' => 1]) }}" class="of-chip {{ $myServicesOnly ? 'on' : '' }}">My services only</a>
    </div>

    @if(! $feed['hasServices'])
        {{-- Nothing listed yet, so nothing to sort by and nothing to relate
             to. The open board is more use than an empty card, and the reason
             is said rather than left to be guessed at. --}}
        <p class="of-note">
            You haven’t listed your services yet, so this is everything open in your area.
            <a href="{{ route('professional.profile.index') }}">Add your services</a> to see the work that matches first.
        </p>
    @endif

    @forelse($feed['listed'] as $row)
        @include('professional._opportunity-row', ['row' => $row, 'actionable' => true])
    @empty
        @if($feed['hasServices'])
            <p class="of-note">
                Nothing open in your services right now.
                @if($myServicesOnly && $feed['related']->isEmpty())
                    Turn off <b>My services only</b> to see related work too.
                @endif
            </p>
        @endif
    @endforelse

    @if($feed['related']->isNotEmpty() && $feed['hasServices'])
        <div class="of-divider">
            <span>Outside your listed services</span>
        </div>
        <p class="of-note of-note-tight">
            Related work in your area. To bid on these,
            <a href="{{ route('professional.profile.index') }}">add the service</a> to your profile first.
        </p>

        @foreach($feed['related'] as $row)
            @include('professional._opportunity-row', ['row' => $row, 'actionable' => false])
        @endforeach
    @endif

    @if($feed['listed']->isEmpty() && $feed['related']->isEmpty())
        <p class="of-note">
            Nothing open in your area yet. New requests appear here as clients post them.
        </p>
    @endif
</div>

@push('styles')
<style>
    .of-card { display: flex; flex-direction: column; }
    .of-toggle { display: flex; gap: 6px; margin: 2px 0 12px; }
    .of-chip { font-size: 11.5px; font-weight: 700; padding: 5px 11px; border-radius: 999px; text-decoration: none;
               border: 1px solid var(--border-color); color: var(--text-muted); }
    .of-chip.on { background: var(--brand, #2563eb); border-color: var(--brand, #2563eb); color: #fff; }

    .of-row { display: flex; gap: 10px; align-items: flex-start; padding: 10px 0; border-top: 1px solid var(--border-color); }
    .of-row:first-of-type { border-top: 0; }
    .of-row-body { min-width: 0; flex: 1; }
    .of-row-title { font-size: 13.5px; font-weight: 700; }
    .of-row-meta { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .of-row-side { text-align: right; white-space: nowrap; }
    .of-fit { font-size: 13px; font-weight: 800; }
    .of-fit-k { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .03em; }
    .of-bid { display: inline-block; margin-top: 5px; font-size: 11.5px; font-weight: 700; text-decoration: none;
              padding: 4px 10px; border-radius: 7px; background: var(--brand, #2563eb); color: #fff; }
    .of-locked { display: inline-block; margin-top: 5px; font-size: 11px; color: var(--text-muted); }

    .of-divider { display: flex; align-items: center; gap: 10px; margin: 14px 0 2px; }
    .of-divider::before, .of-divider::after { content: ''; flex: 1; height: 1px; background: var(--border-color); }
    .of-divider span { font-size: 10.5px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--text-muted); }

    .of-note { font-size: 12.5px; color: var(--text-muted); line-height: 1.55; margin: 8px 0 0; }
    .of-note-tight { margin-top: 4px; margin-bottom: 4px; }
    .of-note a { font-weight: 700; }
</style>
@endpush
