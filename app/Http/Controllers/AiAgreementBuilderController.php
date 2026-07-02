<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Agreement Builder — the guided, AI-assisted agreement lifecycle that sits
 * between a client and a professional after a hire decision:
 *
 *   Phase 1 — Discovery & AI Evidence Collection   (this controller)
 *   Phase 2 — Collaboration & Negotiation          (next)
 *   Phase 3 — Execution & Finalization             (next)
 *
 * Phase 1 shows what the AI gathered from the existing booking conversation,
 * proposal and attachments — with per-source confidence and a missing-info
 * detector — then lets either party generate the first draft.
 *
 * NOTE: the AI-evidence model is not built yet, so the payload below is a
 * representative sample. It is a single structured array so it can be swapped
 * for a real evidence-extraction service later without touching the view.
 */
class AiAgreementBuilderController extends Controller
{
    public function phase1(Request $request, ?string $booking = null): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        return view('ai-agreement.build.phase1', array_merge(
            $this->sampleEvidence($booking),
            ['aiLayout' => $aiLayout]
        ));
    }

    public function draft(Request $request, ?string $booking = null): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        return view('ai-agreement.build.draft', array_merge(
            $this->sampleDraft($booking),
            ['aiLayout' => $aiLayout]
        ));
    }

    public function phase2(Request $request, ?string $booking = null): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        return view('ai-agreement.build.phase2', array_merge(
            $this->sampleNegotiation($booking),
            ['aiLayout' => $aiLayout]
        ));
    }

    public function phase3(Request $request, ?string $booking = null): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        return view('ai-agreement.build.phase3', array_merge(
            $this->sampleExecution($booking),
            ['aiLayout' => $aiLayout]
        ));
    }

    private function sampleEvidence(?string $booking): array
    {
        return [
            'ref'    => $booking ? 'AG-' . strtoupper($booking) : 'AG-2025-0613',
            'steps'  => [
                ['Hire Decision', 'done'],
                ['AI Evidence Collection', 'active'],
                ['Draft Generation', 'todo'],
                ['Review & Confirm', 'todo'],
                ['Send for Approval', 'todo'],
            ],
            'hire' => [
                'event'    => 'Luxury Wedding Reception',
                'date'     => 'June 15, 2025',
                'client'   => 'Sarah Johnson',
                'pro'      => 'Elite Events Co.',
                'pro_role' => 'Floral & Décor · Photography',
                'rating'   => '4.9',
                'reviews'  => 128,
                'amount'   => 7500,
            ],

            // Evidence the AI extracted, grouped by source type.
            'evidence' => [
                [
                    'key' => 'chat', 'title' => 'Chat & Messages', 'meta' => '24 messages analyzed', 'confidence' => 96,
                    'items' => ['Total price discussed: $7,500', 'Floral, décor & photography requested', 'Blush uplighting — special request', 'Guest count confirmed: 150'],
                ],
                [
                    'key' => 'proposal', 'title' => 'Proposal Details', 'meta' => 'Accepted proposal #PR-1058', 'confidence' => 99,
                    'items' => ['9 line-item services', 'Ceremony + reception coverage', 'Delivery, setup, breakdown & pickup'],
                ],
                [
                    'key' => 'files', 'title' => 'Attachments & Files', 'meta' => '4 files attached', 'confidence' => 84,
                    'items' => ['Inspiration Board.pdf', 'Venue Layout.pdf', 'Guest List.xlsx', 'Timeline & Schedule.pdf'],
                ],
                [
                    'key' => 'timeline', 'title' => 'Project Timeline', 'meta' => 'Extracted from chat + files', 'confidence' => 90,
                    'items' => ['Setup: 8:00 AM', 'Event: 5:00 PM – 11:00 PM', 'Breakdown: 11:30 PM'],
                ],
                [
                    'key' => 'finance', 'title' => 'Financial Terms', 'meta' => 'From proposal + messages', 'confidence' => 94,
                    'items' => ['Total: $7,500', 'Deposit: 30% ($2,250)', 'Balance due before the event'],
                ],
                [
                    'key' => 'services', 'title' => 'Services & Deliverables', 'meta' => 'Cross-checked across sources', 'confidence' => 92,
                    'items' => ['Floral arrangements — 10 tables + arch', 'Reception décor & styling', 'Photography + videography'],
                ],
            ],

            // Per-source confidence for the sidebar.
            'sources' => [
                ['Chat Messages', 96], ['Proposal Document', 99], ['PDF Documents', 84], ['Email Thread', 72], ['Images', 65],
            ],

            // Gaps the AI flagged — these become questions in Phase 2.
            'missing' => [
                'Cancellation / refund policy not explicitly confirmed',
                'Exact breakdown & teardown time needs sign-off',
                'Travel / parking reimbursement unclear',
            ],

            'overall_confidence' => 91,
        ];
    }

    /**
     * The generated draft. Each section is tagged 'ai' (green — the AI filled
     * it from Phase-1 evidence) or 'required' (amber — the user must complete
     * it). This is the colour-coding Peter asked for: green = AI-filled,
     * non-green = needs user input.
     */
    private function sampleDraft(?string $booking): array
    {
        return [
            'ref'        => $booking ? 'AG-' . strtoupper($booking) : 'AG-2025-0613',
            'event'      => 'Luxury Wedding Reception',
            'confidence' => 94,

            'sections' => [
                // ── AI-GENERATED (green) ──────────────────────────────
                ['type' => 'ai', 'title' => 'Event Details', 'conf' => 98, 'fields' => [
                    ['Event Name', 'Luxury Wedding Reception'], ['Event Type', 'Wedding Ceremony & Reception'],
                    ['Date', 'June 15, 2025'], ['Time', '5:00 PM – 11:00 PM'],
                    ['Venue', 'The Grand Garden Estate, Chicago, IL'], ['Guest Count', '150 guests'],
                ]],
                ['type' => 'ai', 'title' => 'Project Timeline', 'conf' => 95, 'fields' => [
                    ['Setup', '8:00 AM'], ['Event', '5:00 PM – 11:00 PM'], ['Breakdown', 'by 11:30 PM'],
                ]],
                ['type' => 'ai', 'title' => 'Services & Deliverables', 'conf' => 96, 'list' => [
                    'Floral & décor — 10 tables + ceremony arch', 'Reception décor & styling',
                    'Photography + videography', 'Delivery, setup, breakdown & pickup',
                ]],
                ['type' => 'ai', 'title' => 'Financial Summary', 'conf' => 99, 'fields' => [
                    ['Services Subtotal', '$7,100'], ['Taxes & Fees', '$400'],
                    ['Total Project Cost', '$7,500'], ['Deposit (30%)', '$2,250'],
                ]],

                // ── REQUIRED (amber) — user fills ─────────────────────
                ['type' => 'required', 'title' => 'Client Information', 'inputs' => [
                    ['Full Legal Name', 'text', 'Sarah Johnson'], ['Email', 'email', ''],
                    ['Phone', 'tel', ''], ['Billing Address', 'text', ''],
                ]],
                ['type' => 'required', 'title' => 'Service Preferences', 'inputs' => [
                    ['Color Palette', 'text', 'Blush & gold'], ['Special Requests', 'textarea', ''],
                ]],
                ['type' => 'required', 'title' => 'Budget & Payment Confirmation', 'inputs' => [
                    ['Confirm Total', 'text', '$7,500'], ['Preferred Payment Method', 'select', ['Credit Card', 'Bank Transfer', 'Check']],
                ]],
                ['type' => 'required', 'title' => 'Venue & Access Details', 'inputs' => [
                    ['Load-in Instructions', 'textarea', ''], ['Parking / Valet', 'text', ''],
                ]],
                ['type' => 'required', 'title' => 'Cancellation Policy', 'inputs' => [
                    ['Choose Policy', 'select', ['Standard — 50% refund 30+ days prior', 'Flexible — full refund 14+ days', 'Strict — deposit non-refundable']],
                ]],
                ['type' => 'required', 'title' => 'Authorization & Signature', 'inputs' => [
                    ['Type your full name to authorize', 'text', ''],
                ]],
            ],
        ];
    }

    private function sampleNegotiation(?string $booking): array
    {
        return [
            'ref'    => $booking ? 'AG-' . strtoupper($booking) : 'AG-2025-0613',
            'event'  => 'Luxury Wedding Reception',
            'client' => 'Sarah Johnson',
            'pro'    => 'Elite Events Co.',
            'version' => 'v2.0',
            'progress' => 80,
            'status'  => 'In Negotiation · 4 of 5 clauses agreed',

            // The negotiation cycle (sub-stepper within Phase 2).
            'cycle' => [
                ['Professional Review', 'done'],
                ['Edit & Refine Terms', 'done'],
                ['Version Update', 'done'],
                ['Client Review', 'active'],
                ['Agreed', 'todo'],
            ],

            // Agreement clauses with negotiation state.
            'clauses' => [
                ['title' => 'Scope of Services', 'status' => 'agreed', 'body' => 'Floral & décor for 10 tables, ceremony arch, sweetheart + entry tables, plus full-day photography & videography of ceremony and reception.'],
                ['title' => 'Payment Terms', 'status' => 'agreed', 'body' => 'Total $7,500. 30% deposit ($2,250) to secure the date; remaining balance due 7 days before the event.'],
                ['title' => 'Event Timeline', 'status' => 'edited', 'body' => 'Setup 7:00 AM · Event 5:00 PM – 11:00 PM · Breakdown by 11:30 PM.', 'change' => 'Setup time changed 8:00 AM → 7:00 AM at client request.'],
                ['title' => 'Cancellation Policy', 'status' => 'ai-suggested', 'body' => 'AI suggests: 50% refund if cancelled 30+ days prior; deposit non-refundable within 30 days. (Flagged as missing in Phase 1.)'],
                ['title' => 'Liability & Insurance', 'status' => 'disputed', 'body' => 'Vendor carries general liability insurance covering the engagement.', 'change' => 'Client asked to specify coverage amount — awaiting response.'],
            ],

            // Version history with change highlights.
            'versions' => [
                ['v' => 'v1.0', 'by' => 'AI Draft', 'note' => 'Generated from Phase 1 evidence', 'time' => '2 days ago'],
                ['v' => 'v1.1', 'by' => 'Elite Events Co.', 'note' => 'Refined payment & deposit terms', 'time' => '1 day ago'],
                ['v' => 'v1.2', 'by' => 'Sarah Johnson', 'note' => 'Requested earlier setup time', 'time' => '5 hrs ago'],
                ['v' => 'v2.0', 'by' => 'Elite Events Co.', 'note' => 'Updated timeline — under client review', 'time' => 'now', 'current' => true],
            ],

            // Negotiation comment thread.
            'comments' => [
                ['who' => 'Sarah Johnson', 'side' => 'client', 'msg' => 'Can we move setup to 7:00 AM? The venue opens early.', 'time' => '5h'],
                ['who' => 'Elite Events Co.', 'side' => 'pro', 'msg' => 'Agreed — updated the timeline to a 7:00 AM setup.', 'time' => '4h'],
                ['who' => 'Sarah Johnson', 'side' => 'client', 'msg' => 'Great. Could you also specify the liability coverage amount?', 'time' => '2h'],
            ],

            // AI negotiation assistant suggestions.
            'ai_suggestions' => [
                'Adopt the suggested cancellation policy to close the Phase 1 gap.',
                'Specify liability coverage of $1M to resolve the open dispute.',
                'Lock the 4 agreed clauses to speed final sign-off.',
            ],
        ];
    }

    private function sampleExecution(?string $booking): array
    {
        return [
            'ref'        => $booking ? 'AG-' . strtoupper($booking) : 'AG-2025-0613',
            'event'      => 'Luxury Wedding Reception',
            'amount'     => 7500,
            'effective'  => 'May 25, 2025',
            'event_date' => 'June 15, 2025',

            'steps' => [
                ['Finalize Agreement', 'done'],
                ['Electronic Signatures', 'done'],
                ['Activate Agreement', 'done'],
                ['Deliver Copies', 'done'],
                ['Secure Archive', 'active'],
            ],

            'agreement_status' => 'ACTIVE',

            // Final readiness — every clause agreed before signing.
            'readiness' => [
                'Scope of Services', 'Payment Terms', 'Event Timeline',
                'Cancellation Policy', 'Liability & Insurance',
            ],

            // E-signatures from both parties.
            'signatures' => [
                ['party' => 'Client', 'name' => 'Sarah Johnson', 'signed' => true, 'time' => 'May 25, 2025 · 2:14 PM', 'ip' => '74.12.x.x'],
                ['party' => 'Professional (Vendor)', 'name' => 'Elite Events Co.', 'signed' => true, 'time' => 'May 25, 2025 · 3:02 PM', 'ip' => '98.20.x.x'],
            ],

            // Secure archive / compliance artifacts.
            'archive' => [
                ['Final Signed PDF', 'Generated & encrypted'],
                ['Immutable Record', 'SHA-256 hash secured'],
                ['Audit Log', 'Every action timestamped'],
                ['Both Parties Notified', 'Copies delivered by email'],
            ],

            'package' => ['12-page legal agreement', 'Two digital signatures', 'Full audit trail', 'Payment schedule'],
        ];
    }
}
