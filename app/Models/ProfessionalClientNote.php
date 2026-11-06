<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one professional has written about one of their clients.
 *
 * Private to the professional. The client has no screen that reads this and
 * must not get one: it is the place somebody records "pays late" or "prefers
 * the back entrance", and both of those are only honest while unseen.
 */
class ProfessionalClientNote extends Model
{
    protected $fillable = ['supplier_id', 'client_id', 'tags', 'note', 'requirements',
                           'wants_insurance', 'invoice_terms'];

    protected function casts(): array
    {
        return ['tags' => 'array', 'wants_insurance' => 'boolean'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
