{{--
    Rule R60 — Attendee Management, on the event it belongs to.

    This replaces a dashboard widget that showed an account-wide, ungrouped
    list: a client running two weddings in one month had one flat table with
    nothing saying which guest was for which. Everything here is scoped to
    $event, and there is no route that reaches an attendee without it.

    The fields are the rule's purpose test, made concrete. Name, contact,
    RSVP, dietary and accessibility — each feeds a real event function. R60's
    principle is that collecting personal data with nowhere to send it is the
    defect, so nothing else is asked for.

    @param \App\Models\Event $event
    @param \Illuminate\Support\Collection $attendees
    @param array $attendeeSummary
--}}
@php
    $statusColour = [
        'confirmed'   => 'var(--ok-text)',
        'cancelled'   => 'var(--bad-text)',
        'no_response' => 'var(--text-muted)',
    ];
@endphp

<div class="cl-card">
    <h3 style="font-size:16px;font-weight:600;margin-bottom:4px;">Guest list</h3>
    <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:16px;">
        For <b>{{ $event->title }}</b> only. Each event keeps its own list.
        {{-- R49 step 5 collects an estimate at event creation and R60 leaves
             it alone, so the two numbers are both correct and different. Said
             out loud here, because "60 guests" in the header above next to
             "4 Guests" below otherwise reads as a bug. --}}
        @if($event->guest_count && $event->guest_count !== $attendeeSummary['total'])
            You estimated <b>{{ number_format($event->guest_count) }}</b> guests when you created it;
            this list is who you have named so far.
        @endif
    </p>

    {{-- The summary line, from the same count the dashboard shows. --}}
    <div class="at-sum">
        <div class="at-sum-i"><b>{{ $attendeeSummary['total'] }}</b><span>Guests</span></div>
        <div class="at-sum-i"><b style="color:var(--ok-text);">{{ $attendeeSummary['confirmed'] }}</b><span>Confirmed</span></div>
        <div class="at-sum-i"><b style="color:var(--bad-text);">{{ $attendeeSummary['cancelled'] }}</b><span>Cancelled</span></div>
        <div class="at-sum-i"><b style="color:var(--text-muted);">{{ $attendeeSummary['no_response'] }}</b><span>No Response</span></div>
    </div>

    @if($attendees->isEmpty())
        <p style="font-size:13px;color:var(--text-muted);margin:18px 0;">
            No guests on this event yet. Add them one at a time, or paste a list below.
        </p>
    @else
        <div style="overflow-x:auto;">
            <table class="at-table">
                <thead>
                    <tr><th>Name</th><th>Contact</th><th>RSVP</th><th>Notes</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($attendees as $guest)
                        <tr>
                            <td style="font-weight:600;">{{ $guest->name }}</td>
                            <td style="color:var(--text-muted);font-size:12px;">
                                {{ collect([$guest->email, $guest->phone])->filter()->implode(' · ') ?: '—' }}
                            </td>
                            <td>
                                {{-- The RSVP is the one field that changes often, so it
                                     is editable in place rather than behind an edit screen. --}}
                                <form method="POST" action="{{ route('client.attendees.update', [$event, $guest]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="name" value="{{ $guest->name }}">
                                    <select name="rsvp_status" onchange="this.form.submit()" class="at-rsvp"
                                            style="color:{{ $statusColour[$guest->rsvp_status] }};" aria-label="rsvp_status === $value)>">
                                        @foreach(\App\Models\EventAttendee::STATUSES as $value => $label)
                                            <option value="{{ $value }}" @selected($guest->rsvp_status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted);">
                                {{ collect([$guest->dietary, $guest->accessibility])->filter()->implode(' · ') ?: '—' }}
                            </td>
                            <td style="text-align:right;">
                                <form method="POST" action="{{ route('client.attendees.destroy', [$event, $guest]) }}"
                                      onsubmit="return confirm('Remove {{ $guest->name }} from this guest list?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="at-remove">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="cl-grid cl-grid-2" style="margin-top:20px;">
    <div class="cl-card">
        <h3 style="font-size:15px;font-weight:600;margin-bottom:12px;">Add a guest</h3>
        <form method="POST" action="{{ route('client.attendees.store', $event) }}" class="at-form">
            @csrf
            <input type="text" name="name" placeholder="Full name" required maxlength="255">
            <input type="email" name="email" placeholder="Email (optional)" maxlength="255">
            <input type="tel" name="phone" placeholder="Phone (optional)" maxlength="30">
            <input type="text" name="dietary" placeholder="Dietary needs (optional)" maxlength="255">
            <input type="text" name="accessibility" placeholder="Accessibility needs (optional)" maxlength="255">
            <button type="submit" class="cl-btn cl-btn-primary">Add guest</button>
        </form>
    </div>

    <div class="cl-card">
        <h3 style="font-size:15px;font-weight:600;margin-bottom:6px;">Import a list</h3>
        <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:12px;">
            One guest per line, as <code>Name, email, phone</code>. Email and phone are optional.
        </p>
        <form method="POST" action="{{ route('client.attendees.import', $event) }}" class="at-form">
            @csrf
            <textarea name="list" rows="6" required maxlength="20000"
                      placeholder="Sarah Johnson, sarah@example.com, 555 0134&#10;Michael Brown"></textarea>
            <button type="submit" class="cl-btn cl-btn-primary">Import</button>
        </form>
    </div>
</div>

{{-- R60's one client-controlled switch. --}}
<div class="cl-card" style="margin-top:20px;">
    <h3 style="font-size:15px;font-weight:600;margin-bottom:6px;">Sharing with your professional</h3>
    <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:12px;">
        Your guest list is private. You can let the professional booked on this event see it —
        for seating, headcount or dietary needs — and turn that off again at any time.
        They read it here on GigResource; it is never emailed or exported.
    </p>
    <form method="POST" action="{{ route('client.attendees.share', $event) }}">
        @csrf
        <input type="hidden" name="share" value="0">
        <label style="display:flex;gap:9px;align-items:center;font-size:13.5px;cursor:pointer;">
            <input type="checkbox" name="share" value="1" @checked($event->share_attendees) onchange="this.form.submit()">
            <span>Let the professional booked on this event see the guest list</span>
        </label>
    </form>
</div>

@push('styles')
<style>
    .at-sum { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:10px; }
    .at-sum-i { background:var(--bg-card-hover); border-radius:9px; padding:11px 8px; text-align:center; }
    .at-sum-i b { display:block; font-size:19px; }
    .at-sum-i span { font-size:11px; color:var(--text-muted); }
    .at-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:18px; }
    .at-table th, .at-table td { text-align:left; padding:9px 8px; border-bottom:1px solid var(--border-color); }
    .at-table th { font-size:10.5px; text-transform:uppercase; letter-spacing:.03em; color:var(--text-muted); }
    .at-rsvp { background:none; border:1px solid var(--border-color); border-radius:6px; padding:3px 6px; font-size:12px; font-weight:700; cursor:pointer; }
    .at-remove { background:none; border:none; color:var(--bad-text); font-size:12px; font-weight:700; cursor:pointer; padding:2px 4px; }
    .at-form { display:flex; flex-direction:column; gap:9px; }
    .at-form input, .at-form textarea { width:100%; padding:9px 11px; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-input, transparent); color:var(--text-primary); font-size:13px; font-family:inherit; }
    @media (max-width: 640px) { .at-sum { grid-template-columns:repeat(2, minmax(0,1fr)); } }
</style>
@endpush
