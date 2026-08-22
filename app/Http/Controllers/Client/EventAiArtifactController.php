<?php

namespace App\Http\Controllers\Client;

use App\Domain\AiFeatures\AiAccess;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAiArtifact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Add to my event" — saves an AI-tool result onto one of the client's events.
 * Level-gated: manual-tier users attach manually; semi/maximum tiers get the
 * one-click auto-attach (recorded as mode = auto).
 */
class EventAiArtifactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id'  => ['required', 'integer', 'exists:events,id'],
            'tool_key'  => ['required', 'string', 'max:60'],
            'tool_name' => ['required', 'string', 'max:120'],
            'title'     => ['required', 'string', 'max:200'],
            'payload'   => ['nullable', 'string', 'max:20000'], // JSON string from the tool
        ]);

        $event = Event::findOrFail($data['event_id']);
        abort_unless($this->ownsEvent($request, $event), 403);

        $level = AiAccess::level($request->user(), $data['tool_key']);
        $mode  = in_array($level, ['semi', 'maximum'], true) ? 'auto' : 'manual';

        $payload = null;
        if (! empty($data['payload'])) {
            $decoded = json_decode($data['payload'], true);
            $payload = is_array($decoded) ? $decoded : null;
        }

        EventAiArtifact::create([
            'event_id'  => $event->id,
            'user_id'   => $request->user()->id,
            'tool_key'  => $data['tool_key'],
            'tool_name' => $data['tool_name'],
            'title'     => $data['title'],
            'payload'   => $payload,
            'mode'      => $mode,
        ]);

        return redirect()
            ->route('client.events.show', $event)
            ->with('status', $data['tool_name'] . ' added to "' . $event->title . '".');
    }

    /**
     * Checklist row 194 — pull an existing result into this request.
     *
     * There were two ways to do this and they wrote to two different tables:
     * this method duplicated a row into event_ai_artifacts, while the R30
     * bridge (ToolkitBridge) records a placement in toolkit_attachments. Two
     * tables holding the same idea is one to forget when the rules change, so
     * placement now has a single home -- toolkit_attachments -- and this method
     * feeds it rather than making a second library row.
     *
     * That also draws the line the two event-page sections needed: "Toolkit
     * Results" is what was SAVED on this event (born here, in
     * event_ai_artifacts); "Attached from your toolkit" is what was PULLED IN
     * from elsewhere (toolkit_attachments). A pull is a copy -- the original
     * stays on its own event -- and removing the placement never touches it.
     */
    public function copy(Request $request, Event $event, EventAiArtifact $artifact): RedirectResponse
    {
        abort_unless($this->ownsEvent($request, $event), 403);

        // Only from their own results. Somebody else's budget is not a
        // library to browse.
        abort_unless($artifact->user_id === $request->user()->id, 403);

        $placed = \App\Domain\Toolkit\ToolkitBridge::attach($request->user(), $artifact, $event);

        if (! $placed) {
            return back()->with('status', 'That result is already attached to this request.');
        }

        return back()->with('status', 'Attached to this request. You can remove it here anytime — your saved result stays put.');
    }

    public function destroy(Request $request, EventAiArtifact $artifact): RedirectResponse
    {
        abort_unless($artifact->user_id === $request->user()->id, 403);
        $event = $artifact->event;
        $artifact->delete();

        return back()->with('status', 'Removed from your event.');
    }

    private function ownsEvent(Request $request, Event $event): bool
    {
        $uid = $request->user()->id;

        return (int) $event->client_id === $uid || (int) $event->created_by === $uid;
    }
}
