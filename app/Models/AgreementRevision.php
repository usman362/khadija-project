<?php

namespace App\Models;

use App\Models\Concerns\ShowsTimesWhereTheReaderIs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One version of an agreement, kept for good.
 *
 * Sir Peter: "Keep previous versions, including signatures, approvals,
 * timestamps, and a record of what changed. Do not overwrite historical
 * agreements."
 *
 * So a row is written whenever a version is superseded, carrying the terms as
 * they stood, the fields that moved, and who had signed or approved by then.
 * Nothing here is ever updated: what somebody agreed to, and when, is a
 * record rather than a working copy.
 */
class AgreementRevision extends Model
{
    use ShowsTimesWhereTheReaderIs;

    protected $fillable = [
        'finalization_id', 'version', 'terms', 'changed', 'created_by', 'summary',
        'client_signed_at', 'supplier_signed_at', 'client_approved', 'supplier_approved',
    ];

    protected $casts = [
        'terms'             => 'array',
        'changed'           => 'array',
        'client_signed_at'  => 'datetime',
        'supplier_signed_at' => 'datetime',
        'client_approved'   => 'boolean',
        'supplier_approved' => 'boolean',
    ];

    public function finalization(): BelongsTo
    {
        return $this->belongsTo(Finalization::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** How many fields moved, for a one-line summary of the version. */
    public function changeCount(): int
    {
        return count($this->changed ?? []);
    }
}
