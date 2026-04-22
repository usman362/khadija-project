<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiChatConversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'total_tokens',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'conversation_id')->orderBy('created_at');
    }

    /**
     * Build a concise title from the first user message.
     */
    public function autoTitle(string $firstUserMessage): string
    {
        $title = trim(strtok($firstUserMessage, "\n"));
        return \Illuminate\Support\Str::limit($title, 60, '…') ?: 'New Conversation';
    }
}
