<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'uploaded_by',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Serialized with every attachment, so JSON carries what a screen needs.
     *
     * The API returned the raw row — path, name, byte count — and the browser
     * had no address to open the file with, so a freshly sent attachment
     * rendered as a tile that went nowhere. Appending it here fixes both
     * inboxes and anything else that serializes one, rather than each caller
     * remembering to build a url.
     */
    protected $appends = ['url', 'size_label'];

    public function getUrlAttribute(): string
    {
        return route('attachments.download', $this);
    }

    /** "2.4 MB" — the size as the person who attached it thinks of it. */
    public function getSizeLabelAttribute(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function getUrl(): string
    {
        return route('attachments.download', $this);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getStoragePath(): string
    {
        return $this->file_path;
    }
}
