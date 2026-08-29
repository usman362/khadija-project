<?php

namespace App\Http\Controllers;

use App\Models\MessageAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentController extends Controller
{
    private const MAX_SIZE_KB = 10240; // 10 MB

    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'video/mp4', 'video/webm',
    ];

    /**
     * Upload a file attachment (not yet linked to a message).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:' . self::MAX_SIZE_KB,
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $file = $request->file('file');

        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
            return response()->json([
                'message' => 'File type not allowed.',
                'allowed' => self::ALLOWED_MIMES,
            ], 422);
        }

        $conversationId = $request->input('conversation_id');
        $uuid = Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $path = $file->storeAs(
            "chat-attachments/{$conversationId}",
            "{$uuid}.{$extension}",
            'private'
        );

        /*
         * No message yet — the file is picked before the message is sent, and
         * `attachment_ids` on send is what joins them.
         *
         * This used to insert `0` and null it on the next line, a workaround
         * for a NOT NULL foreign key. MySQL never reached the next line: 0 is
         * not a message, the constraint rejected the INSERT, and every upload
         * 500'd. The column is nullable now, so the honest value goes in.
         */
        $attachment = MessageAttachment::create([
            'message_id'  => null,
            'uploaded_by' => $request->user()->id,
            'file_path'   => $path,
            'file_name'   => $file->getClientOriginalName(),
            'file_size'   => $file->getSize(),
            'mime_type'   => $file->getMimeType(),
        ]);

        return response()->json([
            'id' => $attachment->id,
            'file_name' => $attachment->file_name,
            'file_size' => $attachment->file_size,
            'mime_type' => $attachment->mime_type,
            'is_image' => $attachment->isImage(),
        ], 201);
    }

    /**
     * Download an attachment with authorization check.
     */
    public function download(Request $request, MessageAttachment $attachment): StreamedResponse
    {
        $user = $request->user();

        /*
         * An attachment with no message is one the uploader has picked but not
         * sent. It belongs to them and nobody else — this used to 404 on it,
         * which is every attachment for as long as it sits in the composer.
         */
        if (! $attachment->message_id) {
            abort_unless($attachment->uploaded_by === $user->id || $user->isAdmin(), 403);
        } else {
            $message = $attachment->message;

            abort_unless($message && $message->conversation, 404);
            abort_unless(
                $user->isAdmin() || $message->conversation->hasParticipant($user),
                403,
            );
        }

        abort_unless(Storage::disk('private')->exists($attachment->file_path), 404);

        /*
         * An image opens; everything else saves.
         *
         * This always sent Content-Disposition: attachment, so clicking a photo
         * someone sent you started a download instead of showing it — and a
         * thumbnail in the thread had nothing it could point at. Images and PDFs
         * are things people want to LOOK at, so they are served inline; ?download=1
         * still forces the save for when they want the file itself.
         */
        $inline = ($attachment->isImage() || $attachment->isPdf())
            && ! $request->boolean('download');

        return $inline
            ? Storage::disk('private')->response(
                $attachment->file_path,
                $attachment->file_name,
                ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
            )
            : Storage::disk('private')->download(
                $attachment->file_path,
                $attachment->file_name
            );
    }
}
