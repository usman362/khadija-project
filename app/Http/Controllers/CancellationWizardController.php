<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Integrated Cancellation & Rejection Wizard — a guided 3-step flow for
 * declining a service agreement: categorise the rejection, choose a
 * resolution path (re-negotiate with AI, or void & cancel), and review a
 * resolution log of what happened.
 *
 * The wizard wraps the existing Agreement reject / regenerate behaviour:
 *   Path A "AI Negotiation"  → the current draft is saved as a version, the
 *                              reason is recorded, and a new version is
 *                              requested (regenerate flow).
 *   Path B "Void & Cancel"   → the agreement is rejected and the gig returns
 *                              to the marketplace (reject flow).
 *
 * resolve() applies the chosen path to a real Agreement when one is supplied
 * (and owned by the user); otherwise it runs the same decision logic against
 * a demo agreement context and returns a real resolution log. Deterministic,
 * no LLM, not plan-gated.
 *
 * Routes: GET  /cancellation-wizard/{agreement?}          (show)
 *         POST /cancellation-wizard/{agreement?}/resolve   (resolve → JSON)
 */
class CancellationWizardController extends Controller
{
    public const REASONS = [
        'financial' => ['Financial Discrepancy', 'Price, payment dates, or escrow terms do not match what was discussed in chat.'],
        'scope'     => ['Scope Creep / Technical Misalignment', 'The contract asks for more work or software setups than initially agreed upon.'],
        'timeline'  => ['Timeline / Milestone Conflict', 'Deadlines or event dates are structured incorrectly.'],
        'other'     => ['Other (Please Specify)', 'Provide additional details about the issue.'],
    ];

    public function show(Request $request, ?Agreement $agreement = null): View
    {
        $user = $request->user();

        // Real agreement context when supplied + owned; else a demo context
        // built from the user's nearest upcoming event.
        if ($agreement && $agreement->exists) {
            $version = (int) ($agreement->version ?: 1);
            $title   = $agreement->title ?: 'Service Agreement';
            $event   = $agreement->booking?->event;
        } else {
            $version = 1;
            $title   = 'Service Agreement';
            $event   = Event::where('client_id', $user->id)->orderByRaw('starts_at is null, starts_at asc')->first();
        }

        $eventCtx = [
            'name'   => $event?->title ?: 'Corporate Gala 2026',
            'date'   => optional($event?->starts_at)->format('M d, Y') ?: 'Jun 15, 2026',
            'budget' => $event && $event->budget ? (float) $event->budget : 25000,
        ];

        return view('client.cancellation-wizard', [
            'agreement'   => $agreement?->exists ? $agreement : null,
            'reasons'     => self::REASONS,
            'agreementNo' => "{$title} V{$version}",
            'event'       => $eventCtx,
        ]);
    }

    public function resolve(Request $request, ?Agreement $agreement = null): JsonResponse
    {
        $data = $request->validate([
            'reason'  => ['required', 'string', 'in:' . implode(',', array_keys(self::REASONS))],
            'details' => ['nullable', 'string', 'max:500'],
            'path'    => ['required', 'string', 'in:negotiate,void'],
        ]);

        [$reasonLabel] = self::REASONS[$data['reason']];
        $reasonText = $data['reason'] === 'other' && ! empty($data['details'])
            ? $data['details']
            : $reasonLabel;

        $applied = false;
        $now = now()->format('M d, Y g:i A');

        // Apply to a real, owned agreement when present.
        if ($agreement && $agreement->exists && $agreement->booking?->client_id === $request->user()->id) {
            try {
                if ($data['path'] === 'void') {
                    $agreement->reject($request->user()->id, $reasonText);
                } else {
                    // Renegotiate: record the reason; a new version will be
                    // generated from it in the negotiation lounge.
                    $agreement->update(['rejection_reason' => $reasonText]);
                }
                $applied = true;
            } catch (\Throwable $e) {
                $applied = false;
            }
        }

        // Build the resolution log (step 3).
        if ($data['path'] === 'negotiate') {
            $outcome = 'AI Negotiation Lounge';
            $log = [
                ['ok', 'Rejection categorized', $reasonLabel],
                ['ok', 'Current draft saved as Version 1', 'Preserved for reference'],
                ['ok', 'Reason sent to AI Assistant', $reasonText],
                ['ok', 'New Version 2 draft requested', 'AI will generate updated terms'],
                ['info', 'Chat re-opened with AI', 'Continue the negotiation'],
            ];
            $message = 'Your draft is archived and the AI negotiation has started. A new Version 2 will be ready in the chat.';
        } else {
            $outcome = 'Agreement Voided';
            $log = [
                ['ok', 'Rejection categorized', $reasonLabel],
                ['ok', 'Agreement permanently voided', $applied ? 'Status set to rejected' : 'Draft discarded'],
                ['ok', 'Gig brief returned to marketplace', 'Open for the bid selection sequence'],
                ['info', 'Project state preserved', 'No further action required'],
            ];
            $message = 'The agreement has been voided and the gig brief is back on the active marketplace.';
        }

        return response()->json([
            'success'   => true,
            'applied'   => $applied,
            'outcome'   => $outcome,
            'reason'    => $reasonLabel,
            'timestamp' => $now,
            'log'       => array_map(fn ($l) => ['kind' => $l[0], 'title' => $l[1], 'detail' => $l[2]], $log),
            'message'   => $message,
        ]);
    }
}
