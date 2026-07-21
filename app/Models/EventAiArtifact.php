<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An AI-tool result a client saved onto one of their events ("Add to my event").
 */
class EventAiArtifact extends Model
{
    protected $fillable = [
        'event_id', 'user_id', 'tool_key', 'tool_name', 'title', 'payload', 'mode',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Emoji/icon for the source tool (falls back to a spark). */
    public function icon(): string
    {
        return [
            'budget-allocator'    => '💰',
            'checklist-generator' => '✅',
            'timeline-builder'    => '🗓️',
            'theme-advisor'       => '🎨',
            'guest-capacity'      => '👥',
            'vendor-matchmaking'  => '🤝',
            'event-planner'       => '🧭',
        ][$this->tool_key] ?? '✨';
    }
}
