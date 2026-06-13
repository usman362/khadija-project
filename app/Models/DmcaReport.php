<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmcaReport extends Model
{
    protected $fillable = [
        'reporter_name',
        'reporter_email',
        'content_url',
        'original_work',
        'statement',
        'reported_user_id',
        'handled_by',
        'status',
        'actioned_at',
    ];

    protected function casts(): array
    {
        return [
            'actioned_at' => 'datetime',
        ];
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
