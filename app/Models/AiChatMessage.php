<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    public const UPDATED_AT = null; // Messages are immutable

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens_used',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at'  => 'datetime',
            'tokens_used' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }
}
