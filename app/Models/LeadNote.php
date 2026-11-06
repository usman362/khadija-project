<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A professional's private note on one lead, and when they mean to chase it.
 *
 * The client whose event this is has no screen that reads this and must not
 * get one. It is where somebody writes "went quiet after the quote" or
 * "wants the cheaper option", and both are only honest while unseen.
 */
class LeadNote extends Model
{
    protected $table = 'professional_lead_notes';

    protected $fillable = ['supplier_id', 'event_id', 'note', 'last_contacted_at', 'next_follow_up_on'];

    protected function casts(): array
    {
        return [
            'last_contacted_at' => 'datetime',
            'next_follow_up_on' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Overdue means they said they would chase it and the day has passed. */
    public function isOverdue(): bool
    {
        return $this->next_follow_up_on !== null && $this->next_follow_up_on->isPast();
    }
}
