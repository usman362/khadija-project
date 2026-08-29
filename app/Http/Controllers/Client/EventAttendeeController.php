<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Rule R60 — Attendee Management, always inside one event.
 *
 * Every action here takes an Event and authorises against it. There is no
 * route that reaches an attendee without naming the event they belong to,
 * which is the correction R60 makes: the dashboard widget this replaces
 * listed every guest a client had ever added, with nothing saying which
 * event any of them was for (Developer Checklist row 223).
 *
 * Route prefix: /client/events/{event}/attendees
 */
class EventAttendeeController extends Controller
{
    /** Add one guest to this event. */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $this->guardGuestCount($event, 1);

        $event->attendees()->create($this->validated($request));

        return back()->with('status', 'Guest added.');
    }

    /** Edit a guest, or change their RSVP. */
    public function update(Request $request, Event $event, EventAttendee $attendee): RedirectResponse
    {
        $this->authorize('update', $event);
        // The attendee id arrives in the URL beside the event id, and the two
        // are only related by this check. Without it an id from someone
        // else's event would be edited through this client's event.
        abort_unless($attendee->event_id === $event->id, 404);

        $attendee->update($this->validated($request, partial: true));

        return back()->with('status', 'Guest updated.');
    }

    public function destroy(Request $request, Event $event, EventAttendee $attendee): RedirectResponse
    {
        $this->authorize('update', $event);
        abort_unless($attendee->event_id === $event->id, 404);

        $attendee->delete();

        return back()->with('status', 'Guest removed.');
    }

    /**
     * Import a list — one guest per line, "Name, email, phone".
     *
     * A paste box rather than a file upload: R54 requires every uploaded file
     * to go through one quarantine-and-scan pipeline that does not exist yet,
     * and a guest list is the last thing that should be the exception to it.
     * Pasted text carries no file to scan.
     */
    public function import(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $data = $request->validate([
            'list' => ['required', 'string', 'max:20000'],
        ]);

        $lines = preg_split('/\r\n|\r|\n/', $data['list']);

        // Counted against the whole paste before any of it is saved, so an
        // import either fits or is refused — a paste of 50 must not silently
        // become 30 with 20 dropped on the floor.
        $this->guardGuestCount($event, count(array_filter($lines, fn ($l) => trim(explode(',', $l)[0] ?? '') !== '')));

        $added = 0;

        foreach ($lines as $line) {
            [$name, $email, $phone] = array_pad(array_map('trim', explode(',', $line, 3)), 3, null);

            if (($name ?? '') === '') {
                continue;   // blank line, or a stray comma
            }

            $event->attendees()->create([
                'name'  => mb_substr($name, 0, 255),
                // A malformed address is dropped rather than rejecting the
                // whole paste: one typo in row 40 should not lose 39 guests.
                'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
                'phone' => $phone ? mb_substr($phone, 0, 30) : null,
            ]);

            $added++;
        }

        return back()->with('status', $added === 1
            ? '1 guest imported.'
            : "{$added} guests imported.");
    }

    /**
     * The professional-access toggle — R60's one client-controlled switch.
     *
     * Per event, default private. When it is on, the booked professional
     * reads the list through the event record; there is no export, which is
     * the workaround R60 closes (a client emailing a PDF leaves no audit
     * trail and no way to withdraw access).
     */
    public function share(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $event->update(['share_attendees' => $request->boolean('share')]);

        return back()->with('status', $event->share_attendees
            ? 'The professional booked on this event can now see the guest list.'
            : 'The guest list is private again.');
    }

    /**
     * What R60's purpose test allows us to keep.
     *
     * Name, contact, RSVP, and dietary/accessibility — each one feeds a real
     * event function. Nothing else is accepted, because the rule's point is
     * that collecting personal data with nowhere to send it is the defect.
     */
    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name'          => [$required, 'string', 'max:255'],
            'email'         => ['nullable', 'email', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'rsvp_status'   => ['sometimes', Rule::in(array_keys(EventAttendee::STATUSES))],
            'dietary'       => ['nullable', 'string', 'max:255'],
            'accessibility' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * Thirty guests per event — Khadijah's sheet, 29 Aug.
     *
     * A total for the event, not a rate: it does not reset tomorrow, so this
     * is a plain count rather than one of the windowed rules. Her note says a
     * large event may need more, which is why the number sits in
     * config/limits.php and not here.
     */
    private function guardGuestCount(Event $event, int $adding): void
    {
        $max = (int) config('limits.rules.client-invitations.max', 0);

        if (! config('limits.enabled', true) || $max <= 0 || auth()->user()?->isAdmin()) {
            return;
        }

        $already = $event->attendees()->count();

        if ($already + $adding <= $max) {
            return;
        }

        $room = max(0, $max - $already);

        throw \Illuminate\Validation\ValidationException::withMessages([
            'list' => $room === 0
                ? "This event already has the maximum of {$max} guests."
                : "That would take this event past {$max} guests. There is room for {$room} more.",
        ]);
    }
}
