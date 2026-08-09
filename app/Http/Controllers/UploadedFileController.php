<?php

namespace App\Http\Controllers;

use App\Models\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rule R54's access-control and download-protection stages.
 *
 * A private file is reachable only here, and only after this checks who is
 * asking. Verification documents used to be written to the public disk, which
 * put a professional's trade licence and insurance certificate on a URL that
 * needed no sign-in — the file's own path was the only thing standing between
 * it and anyone who guessed it.
 */
class UploadedFileController extends Controller
{
    public function show(Request $request, UploadedFile $file): StreamedResponse
    {
        // Nothing that has not been decided on is served, whoever is asking.
        // A quarantined or rejected file has not passed the pipeline, and a
        // removed one was taken down deliberately (R55).
        abort_unless($file->isReleasable(), 404);

        abort_unless($this->mayRead($request->user(), $file), 403);

        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /**
     * Who may read a file.
     *
     * The uploader and an admin, always. Beyond that it is per purpose,
     * because "who may see this" is a different question for a licence than
     * for a client's event photographs — and answering it generously by
     * default is how the public-disk problem happened in the first place.
     */
    private function mayRead(?\App\Models\User $user, UploadedFile $file): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isAdmin() || $file->user_id === $user->id) {
            return true;
        }

        return match ($file->purpose) {
            // Evidence for GigResource's own verification decision. Nobody
            // else needs the document; the badge on the profile is the part
            // the public is entitled to.
            'verification' => false,

            // A client's private occasion. Sharing is the client's call, per
            // event, and R60 already owns that decision for the guest list —
            // this stays with the uploader until the same question is
            // answered for media.
            'event_media' => false,

            default => false,
        };
    }
}
