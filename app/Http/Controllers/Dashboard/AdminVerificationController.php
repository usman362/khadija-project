<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admin review queue for professional verification badges.
 *
 * Each UserProfile may have up to 3 badges in flight:
 *   trade_license, liability_insurance, workers_comp.
 *
 * A badge is "pending" when {badge}_doc is populated but
 * {badge}_verified_at is null — those are what admins work through here.
 */
class AdminVerificationController extends Controller
{
    // 'identity' is the client's account verification (PM-14 Verified Client).
    // Its document is on the PRIVATE disk; the others are on the public one.
    private const BADGES = ['trade_license', 'liability_insurance', 'workers_comp', 'identity'];

    private static function diskFor(string $badge): string
    {
        return $badge === 'identity' ? 'local' : 'public';
    }

    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'pending'); // pending | verified | all

        $query = UserProfile::query()->with('user:id,name,email');

        if ($filter === 'pending') {
            $query->where(function ($q) {
                foreach (self::BADGES as $badge) {
                    $q->orWhere(function ($sub) use ($badge) {
                        $sub->whereNotNull("{$badge}_doc")
                            ->whereNull("{$badge}_verified_at");
                    });
                }
            });
        } elseif ($filter === 'verified') {
            $query->where(function ($q) {
                foreach (self::BADGES as $badge) {
                    $q->orWhereNotNull("{$badge}_verified_at");
                }
            });
        } else {
            // 'all' — profiles that uploaded at least one doc
            $query->where(function ($q) {
                foreach (self::BADGES as $badge) {
                    $q->orWhereNotNull("{$badge}_doc");
                }
            });
        }

        $profiles = $query->latest('updated_at')->paginate(20)->withQueryString();

        // Counters for the filter tabs
        $counts = [
            'pending' => UserProfile::where(function ($q) {
                foreach (self::BADGES as $badge) {
                    $q->orWhere(function ($sub) use ($badge) {
                        $sub->whereNotNull("{$badge}_doc")
                            ->whereNull("{$badge}_verified_at");
                    });
                }
            })->count(),
            'verified' => UserProfile::where(function ($q) {
                foreach (self::BADGES as $badge) {
                    $q->orWhereNotNull("{$badge}_verified_at");
                }
            })->count(),
        ];

        return view('dashboard.admin.verifications.index', compact('profiles', 'filter', 'counts'));
    }

    public function approve(Request $request, UserProfile $profile): RedirectResponse
    {
        $validated = $request->validate([
            'badge' => ['required', 'in:' . implode(',', self::BADGES)],
        ]);

        $col = "{$validated['badge']}_verified_at";
        $profile->update([$col => now()]);

        return back()->with('status', ucfirst(str_replace('_', ' ', $validated['badge'])) . ' approved for ' . $profile->user->name . '.');
    }

    /** The client's ID, streamed from the private disk to an administrator. */
    public function identityDocument(UserProfile $profile)
    {
        abort_unless($profile->identity_doc && Storage::disk('local')->exists($profile->identity_doc), 404);

        return Storage::disk('local')->response($profile->identity_doc);
    }

    public function reject(Request $request, UserProfile $profile): RedirectResponse
    {
        $validated = $request->validate([
            'badge' => ['required', 'in:' . implode(',', self::BADGES)],
        ]);

        $badge = $validated['badge'];
        $docCol = "{$badge}_doc";

        if ($profile->$docCol) {
            Storage::disk(self::diskFor($badge))->delete($profile->$docCol);
        }

        $profile->update([
            $docCol => null,
            "{$badge}_number" => null,
            "{$badge}_verified_at" => null,
        ] + ($badge === 'identity' ? [
            // What the client reads on their verification page.
            'identity_submitted_at'  => null,
            'identity_rejected_note' => 'We could not verify that ID. Please send a clear photo of a valid ID in your own name.',
        ] : []));

        return back()->with('status', ucfirst(str_replace('_', ' ', $badge)) . ' rejected, document removed.');
    }
}
