{{--
    One kind of account's bookings: who, for which event, when.

    Sir Peter, 20 October: "Influencer schedule will be add on my events like
    professional schedule." October's answer was a single table that named each
    kind on the row, which fixed the lie but not the reading: a client looking
    for their influencers still had to scan past their caterers to find them.
    Two tabs, one per kind, is what he asked for.

    The rows here carry no role chip. The tab heading is the chip: every row in
    this table is the same kind of account, and repeating the word once a line
    is noise. The Payment Tracker below mixes both kinds, so it keeps its chips.

    Takes $rows (bookings already filtered to one kind) and $noun.
--}}
@if($rows->isEmpty())
    {{-- No table when there is nothing in it. The old empty state was a cell
         spanning six columns of a table wide enough to scroll sideways, so the
         sentence was centred on the table rather than on the card and ran off
         the right edge of the screen. Six column headings over no rows were
         not telling anybody anything either. --}}
    <div style="padding:48px 18px;text-align:center;">
        @if($noun === 'influencer')
            {{-- Honest about why this is empty. There is no influencer account
                 on the platform yet and no way for a client to reach one, so
                 sending them off to look for one would waste their time. --}}
            <div style="font-weight:700;color:var(--text-primary);font-size:14px;">No influencers booked yet</div>
            <div style="margin-top:6px;font-size:13px;color:var(--text-muted);">Influencers you book will appear here with their event, service and date.</div>
        @else
            <div style="font-weight:700;color:var(--text-primary);font-size:14px;">No professionals booked yet</div>
            <div style="margin-top:6px;font-size:13px;color:var(--text-muted);">
                <a href="{{ route('public.browse') }}" style="color:var(--brand-text);font-weight:600;">Find professionals</a>
                for your events and the ones you book will show up here.
            </div>
        @endif
    </div>
@else
    <div style="overflow-x:auto;">
        <table class="mg-table">
            <thead>
                <tr>
                    <th style="padding-left:18px;">Booked with</th>
                    <th>Event</th>
                    <th>Service</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th style="padding-right:18px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $b)
                    <tr>
                        <td style="padding-left:18px;">
                            <div class="ev-name">{{ $b->supplier?->name ?? 'Account removed' }}</div>
                        </td>
                        <td>@if($b->event)<a href="{{ route('client.events.show', $b->event_id) }}">{{ $b->event->title }}</a>@else—@endif</td>
                        <td>{{ $b->category?->name ?? '—' }}</td>
                        <td>{{ $b->event?->starts_at?->format('M d, Y') ?? 'Not scheduled' }}</td>
                        <td>{{ $b->event?->timeLabel() ?? '—' }}</td>
                        <td style="padding-right:18px;"><span class="mg-status-pill mg-status-{{ $b->status }}">{{ $b->status === 'requested' ? 'Awaiting reply' : ucfirst($b->status) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
