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
            // Saving yourself is already blocked, but an older self-booking
            // would still surface here as someone you have "worked with".
            ->where('supplier_id', '!=', $user->id)
            ->with([
                'supplier' => fn ($q) => $q
                    ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
                    ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)]),
                'supplier.profile',
                'supplier.serviceCategories:id,name',
                'event:id,title',
            ])
            ->get()
            ->groupBy('supplier_id')
            ->map(function ($rows) {
                // "Worked together" means a booking that went ahead, so a
                // cancelled one is not the last time (unless all were).
                $went = $rows->where('status', '!=', 'cancelled');
                $went = $went->isNotEmpty() ? $went : $rows;

                // By id: two bookings made in the same second tie on
                // created_at, and the older one was being named as the last.
                $latest = $went->sortByDesc('id')->first();

                return [
                    'pro'        => $rows->first()->supplier,
                    'times'      => $rows->count(),
                    'last'       => $went->max('created_at'),
                    'completed'  => $rows->where('status', 'completed')->count(),
                    // What was actually agreed, so cancelled bookings are left out.
                    'spent'      => (float) $rows->whereIn('status', ['confirmed', 'completed'])->sum('price'),
                    'last_event' => $latest?->event?->title,
                ];
            })
            ->filter(fn ($r) => $r['pro'])
            ->sortByDesc('last')
            ->values();

        $savedIds = $user->savedProfessionals()->pluck('users.id');

        $saved = $user->savedProfessionals()
            ->excludingSelf($user)
            ->with(['profile', 'serviceCategories:id,name'])
            ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
            ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
            ->get();

        // The strip across the top. Counted from the same rows as the cards.
        $stats = [
            'worked'   => $workedWith->count(),
            'saved'    => $saved->count(),
            'bookings' => $workedWith->sum('times'),
            'spent'    => $workedWith->sum('spent'),
        ];

        return view('client.saved-professionals.index', [
            'workedWith' => $workedWith,
            'saved'      => $saved,
            'savedIds'   => $savedIds,
            'stats'      => $stats,
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
