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

    Laid out as cards across, to Khadijah's reference screen. It was a list of
    rows with a fit percentage on the right, which told somebody how well a
    job suited them without telling them why. Three cards fit the column, and
    anything past three is a link to the board rather than a longer card.

    @param array $feed  ['listed' => Collection, 'related' => Collection, 'hasServices' => bool]
    @param bool  $myServicesOnly
--}}
@php
    $ofShow = 3;
@endphp
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

    @if($feed['listed']->isNotEmpty())
        <div class="of-grid">
            @foreach($feed['listed']->take($ofShow) as $row)
                @include('professional._opportunity-row', ['row' => $row, 'actionable' => true])
            @endforeach
        </div>
        @if($feed['listed']->count() > $ofShow)
            <a class="of-more" href="{{ route('professional.bidding-board.index') }}">
                {{ $feed['listed']->count() - $ofShow }} more in your services on the board
            </a>
        @endif
    @elseif($feed['hasServices'])
        <p class="of-note">
            Nothing open in your services right now.
            @if($myServicesOnly && $feed['related']->isEmpty())
                Turn off <b>My services only</b> to see related work too.
            @endif
        </p>
    @endif

    @if($feed['related']->isNotEmpty() && $feed['hasServices'])
        <div class="of-divider">
            <span>Outside your listed services</span>
        </div>
        <p class="of-note of-note-tight">
            Related work in your area. To bid on these,
            <a href="{{ route('professional.profile.index') }}">add the service</a> to your profile first.
        </p>

        <div class="of-grid">
            @foreach($feed['related']->take($ofShow) as $row)
                @include('professional._opportunity-row', ['row' => $row, 'actionable' => false])
            @endforeach
        </div>
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
    .of-chip.on { background: var(--accent-blue, #2563eb); border-color: var(--accent-blue, #2563eb); color: #fff; }

    /* Three across, as the reference has them. minmax(0,1fr) rather than 1fr:
       a long event title in a grid track that is really minmax(auto,1fr)
       widens the track instead of ellipsing, and takes the page with it. */
    .of-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }

    .of-card-item { display: flex; flex-direction: column; min-width: 0;
        border: 1px solid var(--pd-line, rgba(37,99,235,0.14)); border-radius: 12px; padding: 11px 11px 10px;
        background: var(--bg-card); }
    .of-card-item.is-related { border-style: dashed; opacity: .92; }

    .of-ci-title { margin: 0 0 7px; font-size: 12.5px; font-weight: 800; color: var(--text-primary); line-height: 1.3;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .of-ci-line { display: flex; align-items: flex-start; gap: 6px; font-size: 11px; color: var(--text-secondary, #475569);
        margin-bottom: 4px; min-width: 0; }
    .of-ci-line svg { width: 12px; height: 12px; flex-shrink: 0; margin-top: 1px; color: var(--text-muted); }
    .of-ci-line span { min-width: 0; overflow-wrap: anywhere; }

    .of-ci-chips { display: flex; flex-wrap: wrap; gap: 4px; margin: 7px 0 2px; }
    .of-ci-chip { font-size: 9.5px; font-weight: 700; padding: 2px 7px; border-radius: 6px; max-width: 100%;
        background: rgba(37,99,235,0.10); color: var(--info-text, #1d4ed8);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .of-ci-checks { list-style: none; margin: 8px 0 0; padding: 0; display: flex; flex-direction: column; gap: 4px; }
    .of-ci-checks li { display: flex; align-items: flex-start; gap: 6px; font-size: 10.5px; line-height: 1.35;
        color: var(--text-secondary, #475569); min-width: 0; }
    .of-ci-checks svg { width: 12px; height: 12px; flex-shrink: 0; margin-top: 1px; }
    .of-ci-checks .s-yes svg     { color: var(--ok-text, #047857); }
    .of-ci-checks .s-no svg      { color: var(--text-muted); }
    .of-ci-checks .s-new svg     { color: var(--accent-text, #6d28d9); }
    .of-ci-checks .s-unknown svg { color: var(--warn-text, #b45309); }
    .of-ci-checks .s-no span, .of-ci-checks .s-unknown span { color: var(--text-muted); }

    .of-ci-foot { display: flex; align-items: center; justify-content: space-between; gap: 8px;
        margin-top: auto; padding-top: 10px; min-width: 0; }
    .of-ci-fit { font-size: 10.5px; font-weight: 800; color: var(--text-muted); white-space: nowrap; }
    .of-ci-go { font-size: 11px; font-weight: 700; padding: 6px 11px; border-radius: 8px; text-decoration: none;
        background: var(--accent-blue, #2563eb); color: #fff; white-space: nowrap; }
    .of-ci-add { font-size: 11px; font-weight: 700; text-decoration: none; color: var(--info-text, #1d4ed8); white-space: nowrap; }
    .of-ci-shut { font-size: 10.5px; color: var(--text-muted); white-space: nowrap; }

    /* Link TEXT takes --info-text, which is a light blue on the dark theme.
       --accent-blue is the same #2563eb in both, which is right behind white
       on a button and 3.4 to 1 as text on a dark card. */
    .of-more { display: inline-block; margin-top: 10px; font-size: 11.5px; font-weight: 700;
        color: var(--info-text, #1d4ed8); text-decoration: none; }

    .of-divider { display: flex; align-items: center; gap: 10px; margin: 14px 0 2px; }
    .of-divider::before, .of-divider::after { content: ''; flex: 1; height: 1px; background: var(--border-color); }
    .of-divider span { font-size: 10.5px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--text-muted); }

    .of-note { font-size: 12.5px; color: var(--text-muted); line-height: 1.55; margin: 8px 0 0; }
    .of-note-tight { margin-top: 4px; margin-bottom: 4px; }
    .of-note a { font-weight: 700; }

    /* Three narrow cards stop being readable long before the page does. */
    @media (max-width: 1500px) { .of-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 1200px) { .of-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 820px)  { .of-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 560px)  { .of-grid { grid-template-columns: minmax(0, 1fr); } }
</style>
@endpush
