<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\ShowsTimesWhereTheReaderIs;

/**
 * Immutable log of agreement/booking state changes. Append-only; no updates or deletes.
 */
class AgreementLog extends Model
{
    use ShowsTimesWhereTheReaderIs;

    public const UPDATED_AT = null;

    protected $table = 'agreement_log';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
    ];

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
