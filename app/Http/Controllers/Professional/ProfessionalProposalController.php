<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\AgreementLog;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionalProposalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Stats
        $stats = [
            'all' => Booking::where('supplier_id', $user->id)->count(),
            'pending' => Booking::where('supplier_id', $user->id)->where('status', 'requested')->count(),
            'accepted' => Booking::where('supplier_id', $user->id)->where('status', 'confirmed')->count(),
            'in_progress' => Booking::where('supplier_id', $user->id)->where('status', 'confirmed')
                ->whereHas('event', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()))->count(),
            'completed' => Booking::where('supplier_id', $user->id)->where('status', 'completed')->count(),
            'cancelled' => Booking::where('supplier_id', $user->id)->where('status', 'cancelled')->count(),
        ];

        // Build query with filters
        $query = Booking::where('supplier_id', $user->id)
            ->with(['event:id,title,starts_at,ends_at,category_id', 'event.category:id,name', 'supplier:id,name', 'client:id,name'])
            ->latest();

        // Status tab filter
        $tab = $request->string('tab')->toString() ?: 'all';
        switch ($tab) {
            case 'pending':
                $query->where('status', 'requested');
                break;
            case 'accepted':
                $query->where('status', 'confirmed');
                break;
            case 'in_progress':
                $query->where('status', 'confirmed')
                    ->whereHas('event', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()));
                break;
            case 'completed':
                $query->where('status', 'completed');
                break;
            case 'cancelled':
                $query->where('status', 'cancelled');
                break;
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->whereHas('event', fn ($eq) => $eq->where('title', 'like', "%{$search}%"));
                $q->orWhereHas('client', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $bookings = $query->paginate(12)->withQueryString();

        return view('professional.proposals.index', compact('stats', 'bookings', 'tab'));
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        $validated = $request->validate([
            'status' => ['required', 'in:requested,confirmed,cancelled,completed'],
        ]);

        $previousStatus = $booking->status;
        $booking->update(['status' => $validated['status']]);

        if ($previousStatus !== $validated['status']) {
            AgreementLog::create([
                'subject_type' => 'booking',
                'subject_id' => $booking->id,
                'from_status' => $previousStatus,
                'to_status' => $validated['status'],
                'changed_by' => $request->user()->id,
            ]);
        }

        return back()->with('status', 'Booking status updated.');
    }
}
