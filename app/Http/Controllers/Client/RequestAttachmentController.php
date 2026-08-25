<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\RequestAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Uploading, previewing and removing files on a request.
 *
 * Files go to the PRIVATE disk and are served through this controller, never
 * by a public URL. A floor plan or a guest list is not something to leave on a
 * guessable path — everything here is read back only by the client who owns
 * the request and, once it is published, the professionals who can bid on it.
 */
class RequestAttachmentController extends Controller
{
    /** 10 MB, matching the message attachments the app already accepts. */
    public const MAX_SIZE_KB = 10240;

    public const MAX_FILES = 10;

    /**
     * Deliberately narrow. Briefs, floor plans and reference documents is what
     * the step asks for; anything executable has no business here.
     */
    public const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain', 'text/csv',
    ];

    /** The files on this wizard session, whether or not the event exists yet. */
    public static function forDraft(int $userId, string $draftKey, ?int $eventId = null)
    {
        return RequestAttachment::where('user_id', $userId)
            ->where(function ($q) use ($draftKey, $eventId) {
                $q->where('draft_key', $draftKey);
                if ($eventId) {
                    $q->orWhere('event_id', $eventId);
                }
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * Hand every file held against this wizard session to the event it was
     * always meant for. Called when the request is saved as a draft and again
     * when it is published — both are idempotent.
     */
    public static function adopt(int $userId, string $draftKey, Event $event): void
    {
        RequestAttachment::where('user_id', $userId)
            ->where('draft_key', $draftKey)
            ->update(['event_id' => $event->id, 'draft_key' => null]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'file'      => ['required', 'file', 'max:'.self::MAX_SIZE_KB],
            'draft_key' => ['required', 'string', 'max:64'],
        ], [
            'file.max' => 'That file is over 10 MB. Please attach a smaller one.',
        ]);

        $draftKey = $request->string('draft_key')->toString();
        $file     = $request->file('file');

        /*
         * The browser's own type is not trusted — getMimeType() reads the
         * file. A .jpg that is really a script must not get in on its name.
         */
        $mime = $file->getMimeType();

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            return response()->json([
                'message' => 'That kind of file cannot be attached. Images, PDFs, Word, Excel, CSV and plain text are accepted.',
            ], 422);
        }

        $existing = RequestAttachment::where('user_id', $user->id)
            ->where(function ($q) use ($draftKey) {
                $q->where('draft_key', $draftKey);
                if (preg_match('/^event-(\d+)$/', $draftKey, $m)) {
                    $q->orWhere('event_id', (int) $m[1]);
                }
            })
            ->count();

        if ($existing >= self::MAX_FILES) {
            return response()->json([
                'message' => 'You can attach up to '.self::MAX_FILES.' files. Remove one to add another.',
            ], 422);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');

        // The stored name is a uuid: the client's own filename is kept in the
        // database and given back on download, but never used as a path.
        $path = $file->storeAs(
            'request-files/'.$user->id,
            Str::uuid().'.'.$extension,
            'private'
        );

        /*
         * Uploaded from the event page rather than the wizard, the key names
         * the event itself — there is no draft to adopt from later, so the
         * file is stamped now. Ownership is re-checked here; the key arrives
         * from the browser and is not evidence of anything on its own.
         */
        $eventId = null;

        if (preg_match('/^event-(\d+)$/', $draftKey, $m)) {
            $event = Event::where('client_id', $user->id)->find((int) $m[1]);

            if (! $event) {
                return response()->json(['message' => 'That request could not be found.'], 404);
            }
            if ($event->is_published) {
                return response()->json([
                    'message' => 'This request is already published. Message the professionals if a document has changed.',
                ], 422);
            }

            $eventId  = $event->id;
            $draftKey = null;
        }

        $attachment = RequestAttachment::create([
            'user_id'   => $user->id,
            'event_id'  => $eventId,
            'draft_key' => $draftKey,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $mime,
        ]);

        return response()->json(['file' => $this->present($attachment)], 201);
    }

    public function destroy(Request $request, RequestAttachment $attachment): JsonResponse
    {
        abort_unless($attachment->user_id === $request->user()->id, 403);

        // Once the request is published the file is part of what professionals
        // bid against, so it cannot be pulled out from under them silently.
        if ($attachment->event_id && $attachment->event?->is_published) {
            return response()->json([
                'message' => 'This request is already published. Message the professionals if a document has changed.',
            ], 422);
        }

        Storage::disk('private')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['removed' => true]);
    }

    /**
     * Serve the file itself.
     *
     * `?inline=1` renders it in the browser (that is the preview); otherwise it
     * downloads. Either way the check is the same one.
     */
    public function show(Request $request, RequestAttachment $attachment): StreamedResponse
    {
        abort_unless($this->mayRead($request, $attachment), 403);
        abort_unless(Storage::disk('private')->exists($attachment->file_path), 404);

        // Only ever previewed inline for types a browser renders safely on its
        // own. Anything else downloads, so nothing is executed in our origin.
        $inline = $request->boolean('inline')
            && ($attachment->isImage() || $attachment->isPdf());

        return Storage::disk('private')->response(
            $attachment->file_path,
            $attachment->file_name,
            [
                'Content-Type'        => $attachment->mime_type,
                'Content-Disposition' => ($inline ? 'inline' : 'attachment')
                    .'; filename="'.addslashes($attachment->file_name).'"',
                // A brief, floor plan or guest list is not for anyone's cache
                // but the person who asked for it.
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Who may read a file.
     *
     * The owner always. An admin always. A professional only once the request
     * is published — they are bidding against these documents, so they have to
     * be able to open them, and not one moment before it goes live.
     */
    private function mayRead(Request $request, RequestAttachment $attachment): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }
        if ($attachment->user_id === $user->id || $user->isAdmin()) {
            return true;
        }

        $event = $attachment->event;

        return $event && $event->is_published && $user->isProfessionalMode();
    }

    /** The shape the wizard's JavaScript renders a tile from. */
    private function present(RequestAttachment $a): array
    {
        return [
            'id'        => $a->id,
            'name'      => $a->file_name,
            'size'      => $a->humanSize(),
            'kind'      => $a->kind(),
            'is_image'  => $a->isImage(),
            'url'       => route('client.request-files.show', $a),
            'preview'   => route('client.request-files.show', [$a, 'inline' => 1]),
            'remove'    => route('client.request-files.destroy', $a),
        ];
    }
}
