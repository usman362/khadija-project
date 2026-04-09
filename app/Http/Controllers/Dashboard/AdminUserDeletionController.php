<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminUserDeletionController extends Controller
{
    /**
     * List all users with pending deletion requests (within grace period).
     */
    public function index(Request $request): View
    {
        $query = User::query()
            ->whereNotNull('deletion_requested_at')
            ->whereNotNull('deletion_scheduled_at')
            ->orderBy('deletion_scheduled_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();

        $stats = [
            'pending'  => User::pendingDeletion()->count(),
            'expired'  => User::expiredDeletionRequests()->count(),
        ];

        return view('dashboard.admin.deletion-requests.index', compact('users', 'stats'));
    }

    /**
     * Cancel a user's pending deletion on their behalf.
     */
    public function cancel(Request $request, User $user): RedirectResponse
    {
        if (!$user->hasPendingDeletion()) {
            return back()->with('error', 'This user does not have a pending deletion request.');
        }

        $user->update([
            'deletion_requested_at' => null,
            'deletion_scheduled_at' => null,
            'deletion_reason'       => null,
        ]);

        Log::info('Account deletion cancelled by admin', [
            'user_id'  => $user->id,
            'admin_id' => $request->user()->id,
        ]);

        return back()->with('status', "Deletion request for {$user->name} has been cancelled.");
    }
}
