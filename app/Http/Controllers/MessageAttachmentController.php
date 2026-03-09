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

        $attachment = MessageAttachment::create([
            'message_id' => 0, // Temporary — will be linked when message is sent
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        // Set message_id to null after create (workaround for non-nullable FK)
        // The attachment will be linked to a message when the message is sent
        $attachment->update(['message_id' => null]);

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
        $message = $attachment->message;

        if (! $message || ! $message->conversation) {
            abort(404);
        }

        $user = $request->user();
        if (! $user->isAdmin() && ! $message->conversation->hasParticipant($user)) {
            abort(403);
        }

        return Storage::disk('private')->download(
            $attachment->file_path,
            $attachment->file_name
        );
    }
}
