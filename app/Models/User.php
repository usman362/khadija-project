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
        'cover_image',
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

    /**
     * Public URL for the profile cover banner. Returns null when the user
     * hasn't uploaded one — the view decides what placeholder to render
     * (Freelancer-style default gradient).
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image ? asset('storage/' . $this->cover_image) : null;
    }

    public function createdEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    // ── Reviews ────────────────────────────────────────────────
    /** Reviews written BY this user (as the reviewer). */
    public function reviewsWritten(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /** Reviews received ABOUT this user (as the reviewee). */
    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    /**
     * Public-safe rollup of this user's incoming reviews.
     *
     *   [
     *     'count'     => 3152,   // total visible reviews
     *     'average'   => 4.8,    // rounded to 1 decimal
     *     'histogram' => [5=>2960, 4=>64, 3=>17, 2=>37, 1=>51],
     *   ]
     *
     * Uses a single grouped query so the histogram + count come free.
     * Returns a zeroed struct when there are no reviews — callers never
     * have to null-check.
     */
    public function reviewStats(): array
    {
        $rows = Review::visible()
            ->about($this->id)
            ->selectRaw('rating, COUNT(*) as c')
            ->groupBy('rating')
            ->pluck('c', 'rating')
            ->all();

        $histogram = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($rows as $star => $count) {
            $histogram[(int) $star] = (int) $count;
        }

        $count = array_sum($histogram);
        $sum   = 0;
        foreach ($histogram as $star => $c) {
            $sum += $star * $c;
        }
        $average = $count > 0 ? round($sum / $count, 1) : 0.0;

        return [
            'count'     => $count,
            'average'   => $average,
            'histogram' => $histogram,
        ];
    }

    /**
     * "Top Rated" derived badge — our platform-level equivalent of the
     * brochure's "Best Pick Guaranteed" seal. Awarded automatically to
     * pros with strong ratings, enough sample size, AND all three
     * verification badges stamped.
     */
    public function isTopRated(): bool
    {
        $stats = $this->reviewStats();
        if ($stats['count'] < 5 || $stats['average'] < 4.5) {
            return false;
        }
        $profile = $this->profile;
        return $profile && count($profile->verifiedBadges()) === count(\App\Models\UserProfile::BADGES);
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

    // ── AI Feature Access (plan-gated) ─────────────────────────

    /**
     * Lookup the plan_feature matching a feature code on the user's active subscription.
     * Admins always have access and unlimited quota.
     * Returns: ['enabled' => bool, 'quota' => int (0=unlimited, -1=not found)]
     */
    public function aiFeatureAccess(string $featureCode): array
    {
        if ($this->isAdmin()) {
            return ['enabled' => true, 'quota' => 0]; // unlimited
        }

        $sub = $this->activeSubscription();
        if (!$sub || !$sub->plan) {
            return ['enabled' => false, 'quota' => -1];
        }

        $feature = \App\Models\PlanFeature::where('membership_plan_id', $sub->plan->id)
            ->where('feature_code', $featureCode)
            ->where('is_included', true)
            ->first();

        if (!$feature) {
            return ['enabled' => false, 'quota' => -1];
        }

        return [
            'enabled' => true,
            'quota'   => (int) ($feature->quota_monthly ?? 0),
        ];
    }

    public function canUseAiFeature(string $featureCode): bool
    {
        return $this->aiFeatureAccess($featureCode)['enabled'];
    }

    public function aiFeatureUsageThisMonth(string $featureCode): int
    {
        return \App\Models\AiFeatureUsage::where('user_id', $this->id)
            ->where('feature_code', $featureCode)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function aiFeatureRemaining(string $featureCode): int
    {
        $access = $this->aiFeatureAccess($featureCode);
        if (!$access['enabled']) return 0;
        if ($access['quota'] === 0) return PHP_INT_MAX;
        return max(0, $access['quota'] - $this->aiFeatureUsageThisMonth($featureCode));
    }
}
