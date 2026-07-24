<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Client → "My Professionals" (Saved Vendors).
 *
 * The retention surface the docs flag as NEEDED: where a client keeps the pros
 * they've hired so they can re-book or re-invite them, separate from Search
 * (which discovers NEW pros). Two groups:
 *   • Worked with — derived from the client's bookings (real history).
 *   • Saved — pros the client explicitly pinned (saved_professionals).
 */
class ClientSavedProfessionalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Pros this client has hired: distinct suppliers across their bookings,
        // with how many times and the most recent engagement.
        $workedWith = Booking::where('created_by', $user->id)
            ->whereNotNull('supplier_id')
            ->with(['supplier.profile', 'supplier' => fn ($q) => $q->withAvg(
                ['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating'
            )])
            ->get()
            ->groupBy('supplier_id')
            ->map(fn ($rows) => [
                'pro'       => $rows->first()->supplier,
                'times'     => $rows->count(),
                'last'      => $rows->max('created_at'),
                'completed' => $rows->where('status', 'completed')->count(),
            ])
            ->filter(fn ($r) => $r['pro'])
            ->sortByDesc('last')
            ->values();

        $savedIds = $user->savedProfessionals()->pluck('users.id');

        $saved = $user->savedProfessionals()
            ->with('profile')
            ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
            ->get();

        return view('client.saved-professionals.index', [
            'workedWith' => $workedWith,
            'saved'      => $saved,
            'savedIds'   => $savedIds,
        ]);
    }

    /** Pin a professional to My Professionals. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'professional_id' => ['required', 'exists:users,id'],
            'note'            => ['nullable', 'string', 'max:200'],
        ]);

        $pro = User::findOrFail($data['professional_id']);
        // Only actual suppliers can be saved, and never yourself.
        abort_if($pro->id === $request->user()->id, 422);

        $request->user()->savedProfessionals()->syncWithoutDetaching([
            $pro->id => ['note' => $data['note'] ?? null],
        ]);

        return back()->with('status', $pro->name . ' saved to My Professionals.');
    }

    /** Remove a saved professional. */
    public function destroy(Request $request, User $professional): RedirectResponse
    {
        $request->user()->savedProfessionals()->detach($professional->id);

        return back()->with('status', 'Removed from My Professionals.');
    }
}
