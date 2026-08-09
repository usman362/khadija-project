<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Uploads\UploadPipeline;
use App\Http\Controllers\Controller;
use App\Models\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rule R54's moderation queue — the "Manual Review" branch of the decision
 * engine, and R55's removal power.
 *
 * Without somewhere for a held file to go, "Manual Review" is just a status
 * nobody reads and the file waits for ever. This is the smallest thing that
 * makes the decision mean something: the list, release, reject, and — for
 * anything already released — removal.
 *
 * R55 gives GigResource the right to remove an image that violates its
 * privacy, safety or content policy "regardless of consent claimed", so
 * removal is available on approved files too, not only on held ones. The file
 * goes and the row stays: an audit log that deletes itself answers nothing.
 */
class AdminUploadModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['manual_review', 'approved', 'rejected', 'removed'], true)
            ? $request->query('status')
            : 'manual_review';

        return view('dashboard.uploads.index', [
            'status' => $status,
            'files'  => UploadedFile::where('status', $status)
                ->with('uploader:id,name,email')
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'counts' => UploadedFile::selectRaw('status, count(*) as n')
                ->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function release(UploadedFile $file, UploadPipeline $pipeline): RedirectResponse
    {
        abort_unless($file->status === UploadedFile::MANUAL_REVIEW, 422);

        $pipeline->release($file);

        return back()->with('status', 'File released.');
    }

    public function reject(Request $request, UploadedFile $file, UploadPipeline $pipeline): RedirectResponse
    {
        abort_unless($file->status === UploadedFile::MANUAL_REVIEW, 422);

        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']])['reason'];

        $pipeline->reject($file, $reason);

        return back()->with('status', 'File rejected.');
    }

    /** R55 — removal stays available whatever the uploader attested. */
    public function remove(Request $request, UploadedFile $file): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']])['reason'];

        $file->removeBy($request->user(), $reason);

        return back()->with('status', 'File removed. The record of it is kept.');
    }
}
