<?php

namespace App\Models;

use App\Domain\Auth\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'referred_by_influencer_id',
        'referral_attributed_at',
        'deletion_requested_at',
        'deletion_scheduled_at',
        'deletion_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'deletion_requested_at' => 'datetime',
            'deletion_scheduled_at' => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function influencer(): HasOne
    {
        return $this->hasOne(Influencer::class);
    }

    public function referredByInfluencer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Influencer::class, 'referred_by_influencer_id');
    }

    public function getOrCreateProfile(): UserProfile
    {
        return $this->profile ?? $this->profile()->create(['user_id' => $this->id]);
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Generate initials avatar
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&size=200&background=6366f1&color=fff&bold=true";
    }

    public function createdEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function clientEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'client_id');
    }

    public function supplierEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'supplier_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('joined_at');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription(): ?UserSubscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('starts_at')
            ->first();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::ADMIN->value);
    }

    // ── Dual-Role (Client ↔ Professional) Helpers ──────────────

    /**
     * Does this user have both client AND supplier roles assigned?
     */
    public function hasBothRoles(): bool
    {
        return $this->hasRole(RoleName::CLIENT->value)
            && $this->hasRole(RoleName::SUPPLIER->value);
    }

    /**
     * The role the user is currently "acting as" — client or supplier.
     * Priority: session value → first matching role → null.
     */
    public function activeRole(): ?string
    {
        $session = session('active_role');

        if ($session && $this->hasRole($session)) {
            return $session;
        }

        if ($this->hasRole(RoleName::SUPPLIER->value)) {
            return RoleName::SUPPLIER->value;
        }

        if ($this->hasRole(RoleName::CLIENT->value)) {
            return RoleName::CLIENT->value;
        }

        return null;
    }

    public function isClientMode(): bool
    {
        return $this->activeRole() === RoleName::CLIENT->value;
    }

    public function isProfessionalMode(): bool
    {
        return $this->activeRole() === RoleName::SUPPLIER->value;
    }

    // ── Account Deletion Helpers ───────────────────────────────────

    /**
     * Has the user submitted a deletion request that is still within the grace period?
     */
    public function hasPendingDeletion(): bool
    {
        return $this->deletion_requested_at !== null
            && $this->deletion_scheduled_at !== null
            && $this->deletion_scheduled_at->isFuture();
    }

    /**
     * How many days remain before the account is permanently purged.
     */
    public function daysUntilDeletion(): ?int
    {
        if (!$this->hasPendingDeletion()) {
            return null;
        }
        return (int) ceil(now()->diffInHours($this->deletion_scheduled_at, false) / 24);
    }

    /**
     * Scope: users whose grace period has expired and are ready for hard deletion.
     */
    public function scopeExpiredDeletionRequests(Builder $query): Builder
    {
        return $query->whereNotNull('deletion_scheduled_at')
            ->where('deletion_scheduled_at', '<=', now());
    }

    /**
     * Scope: users currently in the pending-deletion grace period.
     */
    public function scopePendingDeletion(Builder $query): Builder
    {
        return $query->whereNotNull('deletion_requested_at')
            ->whereNotNull('deletion_scheduled_at')
            ->where('deletion_scheduled_at', '>', now());
    }
}
