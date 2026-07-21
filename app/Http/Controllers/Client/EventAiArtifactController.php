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
