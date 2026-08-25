<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One file on a bidding request. See the migration for why `event_id` is
 * nullable and `draft_key` exists.
 */
class RequestAttachment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'event_id',
        'draft_key',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /** "2.4 MB" — the size as the person who picked the file thinks of it. */
    public function humanSize(): string
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

    /** A short label for the tile when there is no image to show. */
    public function kind(): string
    {
        return match (true) {
            $this->isImage() => 'Image',
            $this->isPdf()   => 'PDF',
            str_contains((string) $this->mime_type, 'word')          => 'Word',
            str_contains((string) $this->mime_type, 'sheet'),
            str_contains((string) $this->mime_type, 'excel')         => 'Excel',
            default => strtoupper(pathinfo((string) $this->file_name, PATHINFO_EXTENSION)) ?: 'File',
        };
    }
}
