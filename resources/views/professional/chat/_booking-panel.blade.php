{{--
    Rule R52 — the booking-context panel, moved here from the Threads page.

    Shown only when the open conversation is tied to a booking. The controller
    passes null otherwise and this file is never included, which is the point:
    Threads drew the whole panel for every conversation and put an em dash in
    each field, which reads as a page that failed to load rather than as a
    conversation that simply has no booking behind it.

    Quick Actions carries two entries. Threads drew four; "Send Payment Link"
    and "Create Invoice" were buttons with no handler and no feature anywhere
    behind them, so they are not here.

    @param array $bookingPanel
--}}
<aside class="cx-book">
    <div class="cx-book-card">
        <div class="cx-book-h">Conversation Info</div>

        @foreach([
            'Project'        => $bookingPanel['project'],
            'Event Date'     => $bookingPanel['date'],
            'Location'       => $bookingPanel['location'],
            'Booking Status' => $bookingPanel['status'],
            'Total Amount'   => $bookingPanel['total'],
        ] as $label => $value)
            @if($value)
                <div class="cx-book-row">
                    <span>{{ $label }}</span>
                    <b>{{ $value }}</b>
                </div>
            @endif
        @endforeach

        <a class="cx-book-link" href="{{ $bookingPanel['bookingUrl'] }}">View booking details →</a>

        {{-- Real now: this posts, and the answer is stored on the
             conversation. It used to be pre-ticked and wired to nothing. --}}
        <form method="POST" action="{{ $bookingPanel['toggleUrl'] }}" class="cx-book-check">
            @csrf
            <input type="hidden" name="include" value="0">
            <label>
                <input type="checkbox" name="include" value="1"
                       @checked($bookingPanel['includeInContract'])
                       onchange="this.form.submit()">
                <span>Include this chat’s agreed points in the final contract.</span>
            </label>
            <noscript><button type="submit" class="cx-book-save">Save</button></noscript>
        </form>
    </div>

    <div class="cx-book-card">
        <div class="cx-book-h">Shared Files</div>
        @forelse($bookingPanel['files'] as $file)
            <div class="cx-file">
                <span class="cx-file-ext">{{ $file['ext'] }}</span>
                <span class="cx-file-name">{{ $file['name'] }}</span>
                <span class="cx-file-meta">{{ $file['size'] }} · {{ $file['date'] }}</span>
            </div>
        @empty
            <p class="cx-book-empty">Nothing shared in this conversation yet.</p>
        @endforelse
    </div>

    <div class="cx-book-card">
        <div class="cx-book-h">Quick Actions</div>
        <a class="cx-qa" href="{{ $bookingPanel['bookingUrl'] }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Share Contract
        </a>
        <a class="cx-qa" href="{{ $bookingPanel['calendarUrl'] }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Schedule Call
        </a>
    </div>
</aside>
