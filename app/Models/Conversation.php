<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'booking_id',
        'event_id',
        'created_by',
    ];

    // ----- Relationships -----

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('joined_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ----- Scopes -----

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('participants', function (Builder $q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ----- Helpers -----

    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest('created_at')->limit(1);
    }

    public function getUnreadCountFor(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();
    }

    public function markAsReadFor(User $user): void
    {
        $unreadIds = $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->pluck('id');

        if ($unreadIds->isEmpty()) {
            return;
        }

        $rows = $unreadIds->map(fn ($id) => [
            'message_id' => $id,
            'user_id' => $user->id,
            'read_at' => now(),
        ])->all();

        MessageRead::insert($rows);
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    public function addParticipant(User $user): void
    {
        if (! $this->hasParticipant($user)) {
            $this->participants()->attach($user->id, ['joined_at' => now()]);
        }
    }
}
