<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A toolkit result the client placed into a request or an agreement (R30).
 *
 * Not the saved result itself -- that stays in event_ai_artifacts. This is the
 * placement, which is why removing one leaves the original untouched.
 */
class ToolkitAttachment extends Model
{
    public const COPY   = 'copy';
    public const LINKED = 'linked';

    protected $fillable = [
        'attachable_type', 'attachable_id', 'source_artifact_id', 'added_by',
        'tool_key', 'tool_name', 'title', 'payload',
        'link_mode', 'source_fingerprint', 'needs_review',
    ];

    protected $casts = [
        'payload'      => 'array',
        'needs_review' => 'boolean',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(EventAiArtifact::class, 'source_artifact_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * The fingerprint of a payload.
     *
     * Order-insensitive at the top level: re-saving a tool result that
     * produced the same figures in a different order is not a change the
     * client needs to be asked about.
     */
    public static function fingerprint(?array $payload): string
    {
        $normalised = $payload ?? [];
        self::ksortRecursive($normalised);

        return hash('sha256', json_encode($normalised));
    }

    private static function ksortRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::ksortRecursive($value);
            }
        }
    }

    public function isLinked(): bool
    {
        return $this->link_mode === self::LINKED;
    }

    /** Has the source moved since this was linked? */
    public function sourceHasMoved(): bool
    {
        if (! $this->isLinked() || ! $this->source) {
            return false;
        }

        return self::fingerprint($this->source->payload) !== $this->source_fingerprint;
    }
}
