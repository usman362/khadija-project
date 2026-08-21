<?php

namespace App\Http\Controllers\Client;

use App\Domain\Toolkit\ToolkitBridge;
use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\Event;
use App\Models\EventAiArtifact;
use App\Models\ToolkitAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Plan with Toolkit" (R30) — take something the toolkit already worked out
 * and put it into a request or a professional's agreement.
 *
 * The screen narrows: tool → saved result → destination → how it is added.
 * Each step is a link carrying the ones before it, so a client can go back a
 * step without losing the others and nothing is held in a session that a
 * refresh would quietly drop.
 *
 * Nothing is ever added without the client pressing the button. That is R30's
 * first principle and the reason there is no "apply automatically" anywhere in
 * here, including for linked data whose source has changed.
 */
class ToolkitBridgeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Linked placements whose source moved are found on arrival rather
        // than by a job -- this is the only screen that acts on them.
        ToolkitBridge::markMovedSources($user);

        $tools = ToolkitBridge::toolsFor($user);

        $toolKey  = $request->string('tool')->toString() ?: null;
        $selected = $tools->firstWhere('key', $toolKey);

        // A tool that cannot be a data source is not a selection, however it
        // arrived in the URL.
        if ($selected && ! $selected['usable']) {
            $selected = null;
            $toolKey  = null;
        }

        $saved = $toolKey
            ? ToolkitBridge::savedResultsFor($user, $toolKey)
            : collect();

        $artifact = null;
        if ($request->filled('result')) {
            $artifact = $saved->firstWhere('id', (int) $request->input('result'));
        }

        $destinations = ToolkitBridge::destinationsFor($user);

        [$destination, $placed] = $this->resolveDestination($request, $destinations);

        return view('client.toolkit.plan', [
            'layout'       => 'layouts.client',
            // The portal layouts title themselves from _seo_meta, not from
            // @section('title') -- without this the browser tab reads as the
            // marketing homepage.
            'seoTitle'     => 'Plan with Toolkit',
            'tier'         => ToolkitBridge::tierOf($user),
            'launchOpen'   => ToolkitBridge::everythingUnlocked($user),
            'tools'        => $tools,
            'selectedTool' => $selected,
            'saved'        => $saved,
            'artifact'     => $artifact,
            'destinations' => $destinations,
            'destination'  => $destination,
            'placed'       => $placed,
            'pending'      => ToolkitAttachment::where('added_by', $user->id)
                ->where('needs_review', true)
                ->with('source')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'result'      => ['required', 'integer', 'exists:event_ai_artifacts,id'],
            'destination' => ['required', 'string', 'regex:/^(request|agreement):[0-9]+$/'],
            'link_mode'   => ['required', 'in:copy,linked'],
        ]);

        $user = $request->user();

        $artifact = EventAiArtifact::findOrFail($data['result']);
        abort_unless($artifact->user_id === $user->id, 403);

        [$kind, $id] = explode(':', $data['destination']);

        $row = collect($kind === 'request'
            ? ToolkitBridge::openRequests($user)
            : ToolkitBridge::agreementsFor($user))
            ->firstWhere('id', (int) $id);

        // Not theirs, or not a destination we offered.
        abort_unless($row !== null, 403);

        if (! $row['eligible']) {
            return back()->with('error', $row['reason']);
        }

        $attachment = ToolkitBridge::attach($user, $artifact, $row['model'], $data['link_mode']);

        if (! $attachment) {
            return back()->with('status', 'That result is already on “' . $row['label'] . '”.');
        }

        $how = $attachment->isLinked()
            ? 'It stays linked, so changes to the original will be shown to you before they are applied.'
            : 'It was added as a copy, so later changes to the original will not alter it.';

        return back()->with('status', $artifact->tool_name . ' added to “' . $row['label'] . '”. ' . $how);
    }

    public function destroy(Request $request, ToolkitAttachment $attachment): RedirectResponse
    {
        abort_unless($attachment->added_by === $request->user()->id, 403);

        $attachment->delete();

        return back()->with('status', 'Removed. Your saved tool result is untouched — it is still in the toolkit.');
    }

    /** Take the source's newer version into a linked placement. */
    public function apply(Request $request, ToolkitAttachment $attachment): RedirectResponse
    {
        abort_unless($attachment->added_by === $request->user()->id, 403);

        if (! ToolkitBridge::applyUpdate($attachment)) {
            // Signed, closed, or gone. What is placed stays placed.
            return back()->with('error',
                'This one cannot be updated here — the request or agreement it sits in has since been closed or accepted. '
                . 'Changing it now goes through the agreement change-and-approval process.');
        }

        return back()->with('status', 'Updated to the current version of ' . $attachment->tool_name . '.');
    }

    /** Keep what is placed and stop following the source. */
    public function keep(Request $request, ToolkitAttachment $attachment): RedirectResponse
    {
        abort_unless($attachment->added_by === $request->user()->id, 403);

        ToolkitBridge::keepCurrent($attachment);

        return back()->with('status', 'Kept as it is. It no longer follows the original.');
    }

    /**
     * Which destination the client is looking at, and what is already on it.
     *
     * Showing what is already there is the "preview placement" step: a client
     * about to add a second budget should see the first one sitting there.
     */
    private function resolveDestination(Request $request, array $destinations): array
    {
        $raw = $request->string('to')->toString();

        if (! preg_match('/^(request|agreement):([0-9]+)$/', $raw, $m)) {
            return [null, collect()];
        }

        [, $kind, $id] = $m;

        $row = collect($destinations[$kind === 'request' ? 'requests' : 'agreements'])
            ->firstWhere('id', (int) $id);

        if (! $row) {
            return [null, collect()];
        }

        return [$row, ToolkitBridge::attachmentsOn($row['model'])];
    }
}
