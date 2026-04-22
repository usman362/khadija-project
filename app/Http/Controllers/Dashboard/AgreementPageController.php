<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Agreements\Services\AgreementGeneratorService;
use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\AgreementLog;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgreementPageController extends Controller
{
    /**
     * List all agreements visible to the user.
     */
    public function index(Request $request): View
    {
        $query = Agreement::query()
            ->with(['booking.event:id,title', 'booking.client:id,name', 'booking.supplier:id,name', 'generator:id,name'])
            ->latest();

        if (!$request->user()->isAdmin()) {
            $query->whereHas('booking', function ($q) use ($request) {
                $q->where('client_id', $request->user()->id)
                    ->orWhere('supplier_id', $request->user()->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return view('dashboard.agreements.index', [
            'agreements' => $query->paginate(12)->withQueryString(),
            'selectedStatus' => $request->input('status'),
        ]);
    }

    /**
     * Show a single agreement with full content.
     */
    public function show(Request $request, Agreement $agreement): View
    {
        $agreement->load(['booking.event', 'booking.client', 'booking.supplier', 'generator']);

        // Determine if current user is client or supplier
        $user = $request->user();
        $isClient = $agreement->booking->client_id === $user->id;
        $isSupplier = $agreement->booking->supplier_id === $user->id;
        $isAdmin = $user->isAdmin();

        return view('dashboard.agreements.show', [
            'agreement' => $agreement,
            'isClient' => $isClient,
            'isSupplier' => $isSupplier,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Generate an AI agreement for a booking.
     */
    public function generate(Request $request, Booking $booking): RedirectResponse
    {
        $booking->load(['event', 'client', 'supplier', 'conversation']);

        // Client controls whether the chat transcript is fed to the AI.
        // Default true so existing "Generate" buttons keep working.
        $includeChat = $request->boolean('include_chat', true);

        // Only require a conversation when the client actually wants the AI to
        // reference it — skipping chat means booking-context-only is fine.
        if ($includeChat && (!$booking->conversation || $booking->conversation->messages()->count() === 0)) {
            return back()->with('error', 'Please start a conversation with the other party before generating a chat-aware agreement — or uncheck "Include pro chat" to generate from booking details only.');
        }

        // Check: no fully accepted agreement exists
        $existing = $booking->agreements()->where('status', 'fully_accepted')->first();
        if ($existing) {
            return back()->with('error', 'This booking already has a fully accepted agreement.');
        }

        $service = new AgreementGeneratorService();
        $agreement = $service->generate($booking, $request->user(), $includeChat);

        // Log the generation
        AgreementLog::create([
            'subject_type' => 'agreement',
            'subject_id' => $agreement->id,
            'from_status' => null,
            'to_status' => 'pending_review',
            'changed_by' => $request->user()->id,
            'notes' => 'AI agreement generated (v' . $agreement->version . ')'
                . ($includeChat ? ' — chat included' : ' — booking context only'),
        ]);

        return redirect()->route('app.agreements.show', $agreement)
            ->with('status', 'AI Agreement has been generated! Please review the terms carefully.');
    }

    /**
     * Accept an agreement (client or supplier).
     */
    public function accept(Request $request, Agreement $agreement): RedirectResponse
    {
        $user = $request->user();
        $booking = $agreement->booking;
        $previousStatus = $agreement->status;

        if ($booking->client_id === $user->id) {
            if ($agreement->clientAccepted()) {
                return back()->with('error', 'You have already accepted this agreement.');
            }
            $agreement->acceptByClient();
        } elseif ($booking->supplier_id === $user->id) {
            if ($agreement->supplierAccepted()) {
                return back()->with('error', 'You have already accepted this agreement.');
            }
            $agreement->acceptBySupplier();
        } elseif ($user->isAdmin()) {
            // Admin can force-accept on behalf of both
            $agreement->update([
                'client_accepted_at' => $agreement->client_accepted_at ?? now(),
                'supplier_accepted_at' => $agreement->supplier_accepted_at ?? now(),
                'status' => 'fully_accepted',
            ]);
        } else {
            return back()->with('error', 'You are not authorized to accept this agreement.');
        }

        // Log acceptance
        AgreementLog::create([
            'subject_type' => 'agreement',
            'subject_id' => $agreement->id,
            'from_status' => $previousStatus,
            'to_status' => $agreement->fresh()->status,
            'changed_by' => $user->id,
            'notes' => $user->name . ' accepted the agreement',
        ]);

        // If fully accepted, auto-confirm the booking
        $agreement->refresh();
        if ($agreement->isFullyAccepted() && $booking->status === 'requested') {
            $prevBookingStatus = $booking->status;
            $booking->update(['status' => 'confirmed']);

            AgreementLog::create([
                'subject_type' => 'booking',
                'subject_id' => $booking->id,
                'from_status' => $prevBookingStatus,
                'to_status' => 'confirmed',
                'changed_by' => null,
                'notes' => 'Auto-confirmed: both parties accepted agreement #' . $agreement->id,
            ]);
        }

        return back()->with('status', 'Agreement accepted successfully!' .
            ($agreement->isFullyAccepted() ? ' Both parties have accepted — the booking is now confirmed.' : ''));
    }

    /**
     * Reject an agreement.
     */
    public function reject(Request $request, Agreement $agreement): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $previousStatus = $agreement->status;
        $agreement->reject($request->user()->id, $validated['rejection_reason'] ?? '');

        AgreementLog::create([
            'subject_type' => 'agreement',
            'subject_id' => $agreement->id,
            'from_status' => $previousStatus,
            'to_status' => 'rejected',
            'changed_by' => $request->user()->id,
            'notes' => 'Agreement rejected: ' . ($validated['rejection_reason'] ?? 'No reason provided'),
        ]);

        return back()->with('status', 'Agreement rejected. You can regenerate a new version.');
    }

    /**
     * Regenerate agreement (creates a new version).
     */
    public function regenerate(Request $request, Booking $booking): RedirectResponse
    {
        return $this->generate($request, $booking);
    }
}
