<?php

namespace App\Http\Controllers;

use App\Domain\PointOfContact;
use App\Models\Booking;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * A crew member's photo, to whoever is entitled to see it.
 *
 * Khadijah, 29 October: headshots and phone numbers are "scoped strictly to
 * the client holding the booking". A file under public/storage is readable by
 * anybody who has the address, and addresses leak — through a copied link, a
 * screenshot, a referrer header, a search engine that crawls one page. So
 * these live on the private disk, where the web server serves nothing, and
 * every read comes through here.
 *
 * Three people may look:
 *
 *   the professional whose crew member this is, because they are their own
 *   records;
 *
 *   a client, and only on a booking of THEIRS where this person is the point
 *   of contact. Not a client who once hired this professional, and not a
 *   client on another of their jobs: the photo was given to one client for
 *   one event;
 *
 *   an administrator, who can already see everything.
 *
 * Everybody else gets a 403, including a signed-in professional asking about
 * another company's staff.
 */
class StaffPhotoController extends Controller
{
    public function __invoke(Request $request, StaffMember $staffMember): BinaryFileResponse
    {
        abort_unless($staffMember->photo, 404);
        abort_unless($this->mayLook($request, $staffMember), 403);
        abort_unless(Storage::disk('private')->exists($staffMember->photo), 404);

        /*
         * Served as a file rather than a stream so the browser can cache and
         * range it like any other image, and inline because the whole point
         * is that it appears on a page.
         */
        return response()->file(
            Storage::disk('private')->path($staffMember->photo),
            ['Content-Disposition' => 'inline'],
        );
    }

    private function mayLook(Request $request, StaffMember $staff): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || (int) $staff->supplier_id === (int) $user->id) {
            return true;
        }

        /*
         * A client, on a booking of their own where this person is the one
         * they have been told to look for, and whose access has not run out.
         *
         * Sir Peter, 29 October: the client's access to contact details ends
         * some days after the job, unless a dispute is open. Enforced here
         * and not only on the page, because he asked for exactly that: "on
         * the backend, including image URLs".
         */
        return Booking::where('client_id', $user->id)
            ->where('point_of_contact_id', $staff->id)
            ->with('event:id,starts_at,ends_at')
            ->get()
            ->contains(fn (Booking $b) => ! PointOfContact::contactWindowClosed($b));
    }
}
