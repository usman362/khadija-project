<?php

namespace App\Models;

use App\Domain\Auth\Enums\RoleName;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
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
        'primary_role',
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
            'login_locked_at'       => 'datetime',
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

        return $this->initialsAvatarUri();
    }

    /**
     * Neutral avatar for the "no user at all" case — a deleted reviewer, a
     * vendor row with no supplier. Views used to fall back to ui-avatars.com
     * here; there is no name to render, so a plain glyph does the job locally.
     */
    public static function placeholderAvatarUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">'
             . '<rect width="200" height="200" fill="#e2e8f0"/>'
             . '<circle cx="100" cy="78" r="32" fill="#94a3b8"/>'
             . '<path d="M40 176c0-33 27-52 60-52s60 19 60 52z" fill="#94a3b8"/></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Initials avatar as an inline SVG data URI.
     *
     * This used to call out to ui-avatars.com — a third-party request on every
     * avatar of every user who hasn't uploaded one, which is most of them. The
     * same picture is trivially drawn locally, so no request leaves the server
     * and avatars keep working with no network at all.
     */
    public function initialsAvatarUri(): string
    {
        // Skip words that don't start with a letter or digit, or "Bloom & Vine
        // Co." would initial as "B&" — and a bare & is invalid XML inside the
        // SVG below.
        $initials = collect(preg_split('/\s+/', trim((string) $this->name)))
            ->filter(fn ($w) => $w !== '' && preg_match('/^\p{L}|^\p{N}/u', $w))
            ->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->implode('');

        $initials = htmlspecialchars($initials !== '' ? $initials : '?', ENT_XML1 | ENT_QUOTES, 'UTF-8');

        // Stable per-user hue so two people never read as the same avatar.
        $hue = crc32((string) ($this->id ?: $this->name)) % 360;

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">
              <rect width="200" height="200" fill="hsl({$hue} 62% 46%)"/>
              <text x="100" y="100" fill="#fff" font-family="system-ui,-apple-system,Segoe UI,Roboto,sans-serif"
                    font-size="88" font-weight="700" text-anchor="middle" dominant-baseline="central">{$initials}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
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

    /**
     * "Verified" derived badge — all three doc verifications approved.
     * Trade license + liability insurance + workers' comp.
     */
    public function isVerified(): bool
    {
        $profile = $this->profile;
        return $profile && count($profile->verifiedBadges()) === count(\App\Models\UserProfile::BADGES);
    }

    /**
     * "New Vendor" derived badge — account created within the last 30
     * days AND fewer than 3 completed reviews. Helps clients spot
     * fresh talent and gives newcomers a visible boost.
     *
     * Mutually exclusive in the UI with Top Rated (a brand-new account
     * can't satisfy the 5-review minimum anyway).
     */
    public function isNewVendor(): bool
    {
        if (!$this->created_at || $this->created_at->lt(now()->subDays(30))) {
            return false;
        }
        return $this->reviewStats()['count'] < 3;
    }

    /**
     * Single accessor returning every active badge for this pro, in
     * display priority order. Views can iterate this instead of
     * checking each badge method individually.
     *
     * @return array<int, string>  e.g. ['top_rated', 'verified']
     */
    public function activeBadges(): array
    {
        $badges = [];
        if ($this->isTopRated())  { $badges[] = 'top_rated'; }
        if ($this->isVerified())  { $badges[] = 'verified'; }
        if ($this->isNewVendor()) { $badges[] = 'new_vendor'; }
        return $badges;
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

    /**
     * Service categories this professional offers — the real answer to "which
     * pros work in this category", replacing a name LIKE against free-text
     * skills that never matched.
     */
    /**
     * Does this account still want lifecycle email of this kind?
     *
     * The four notify_email_* preferences have been editable in profile
     * settings since the beginning, but nothing has ever read them — because
     * nothing sent email. Switching email on without consulting them would mail
     * every person who had already opted out, which is how a sending domain
     * gets blocked and, more simply, is not what they asked for.
     *
     * Anything the account MUST receive — a password reset, a verification
     * link — does not come through here. Those are not lifecycle mail and are
     * not optional.
     *
     * @param  string  $category  bookings | messages | events | marketing
     */
    public function acceptsEmail(string $category): bool
    {
        if (! config('emails.lifecycle.enabled')) {
            return false;
        }

        $column = 'notify_email_' . $category;

        // Marketing is the one that defaults OFF; nobody is opted in to it by
        // having simply never visited their settings page.
        $default = $category !== 'marketing';

        $value = $this->profile?->{$column};

        return $value === null ? $default : (bool) $value;
    }

    /**
     * Both auth emails go out in the app's own template. Laravel's defaults are
     * unbranded, and an unbranded first email is the one people report as
     * phishing rather than click.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\Auth\VerifyEmailAddress());
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\Auth\ResetPasswordLink($token));
    }

    /**
     * The services this professional offers — level 3.
     *
     * Named "categories" but it has always held level-3 SERVICES: all 31 links
     * on record are kind=service. Level 4 specialties share this pivot, so this
     * excludes them explicitly — otherwise every one of the 29 places that read
     * this relation would silently start listing "Wedding DJ" beside
     * "DJs, Live Bands & Musicians" as though they were the same kind of thing.
     */
    public function serviceCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            /*
             * NULL is not a specialty, and SQL will not say so on its own:
             * `kind != 'service_specialty'` evaluates to NULL for a row with no
             * kind, which excludes it. A category saved without a kind would
             * have vanished from every professional's services — silently, and
             * only for them.
             */
            ->where(fn ($q) => $q
                ->whereNull('categories.kind')
                ->orWhere('categories.kind', '!=', Category::SERVICE_SPECIALTY))
            ->withTimestamps();
    }

    /**
     * Replace the services WITHOUT touching the specialties, and vice versa.
     *
     * Do not call sync() on either relation. Both live in the same pivot and
     * the `where kind` constraint applies to reading it, not to the delete
     * sync() performs first — so `serviceCategories()->sync()` wipes every
     * specialty on the way past, and `specialties()->sync()` wipes every
     * service. Verified: syncing two services then two specialties left the
     * professional with two specialties and no services at all.
     *
     * @param  array<int, int>  $ids
     */
    public function syncServices(array $ids): void
    {
        $this->syncWithinKind($ids, [Category::SERVICE, Category::SERVICE_CATEGORY, Category::EVENT_TYPE]);
    }

    /** @param  array<int, int>  $ids */
    public function syncSpecialties(array $ids): void
    {
        $this->syncWithinKind($ids, [Category::SERVICE_SPECIALTY]);
    }

    /**
     * Detach only what belongs to these kinds, then attach the new set.
     *
     * @param  array<int, int>     $ids
     * @param  array<int, string>  $kinds
     */
    private function syncWithinKind(array $ids, array $kinds): void
    {
        $existing = $this->belongsToMany(Category::class)
            ->whereIn('categories.kind', $kinds)
            ->pluck('categories.id')
            ->all();

        // Only ids of the right kind may be attached here, so a caller cannot
        // put a specialty into the services list by passing the wrong array.
        $wanted = Category::whereIn('id', array_map('intval', $ids))
            ->whereIn('kind', $kinds)
            ->pluck('id')
            ->all();

        $this->categoriesPivot()->detach(array_diff($existing, $wanted));
        $this->categoriesPivot()->syncWithoutDetaching($wanted);
    }

    /** The raw pivot, unfiltered — used only by syncWithinKind(). */
    private function categoriesPivot(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    /**
     * Level 4 — the narrower ways this professional does those services.
     *
     * "Wedding DJ" and "Karaoke DJ" beneath "DJ". Same pivot as the services
     * above, separated by kind. Paid Search Visibility will reference these
     * rows rather than keeping a list of its own (Sir Peter, 2026-08-29).
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->where('categories.kind', Category::SERVICE_SPECIALTY)
            ->withTimestamps();
    }

    /** Professionals this client has explicitly saved (My Professionals). */
    public function savedProfessionals(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_professionals', 'client_id', 'professional_id')
            ->withPivot('note')->withTimestamps();
    }

    /** Packages this client hearted on the Package Service Search. */
    public function savedPackages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'saved_packages', 'client_id', 'package_id')
            ->withTimestamps();
    }

    /** Filter sets this user asked us to keep (see SavedSearch). */
    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    /** Opportunities this professional bookmarked on the Bidding Board. */
    public function savedEvents(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Event::class, 'saved_events')->withTimestamps();
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
            && $this->hasRole(RoleName::PROFESSIONAL->value);
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

        // Fallback for a user with no explicit active_role in session: prefer
        // CLIENT so a dual-role account never lands in the professional portal by
        // accident. A pure professional (no client role) still resolves to supplier.
        if ($this->hasRole(RoleName::CLIENT->value)) {
            return RoleName::CLIENT->value;
        }

        if ($this->hasRole(RoleName::PROFESSIONAL->value)) {
            return RoleName::PROFESSIONAL->value;
        }

        return null;
    }

    public function isClientMode(): bool
    {
        return $this->activeRole() === RoleName::CLIENT->value;
    }

    public function isProfessionalMode(): bool
    {
        return $this->activeRole() === RoleName::PROFESSIONAL->value;
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
     * Scope: everyone except the person doing the looking.
     *
     * An account can hold both roles, so without this a user who is a client
     * and a professional finds themselves in every list of professionals — to
     * hire, to save, to send an offer to. Applied to the listings rather than
     * left to each caller to remember, because forgetting it is silent.
     *
     * No-ops for a guest: the public pages use the same queries, and there is
     * nobody to exclude.
     */
    public function scopeExcludingSelf(Builder $query, ?User $viewer = null): Builder
    {
        $viewer ??= auth()->user();

        return $viewer ? $query->whereKeyNot($viewer->id) : $query;
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
        // Test/launch override — when AI_FEATURES_FREE_FOR_ALL=true is set
        // in .env, every authenticated user gets unlimited access to all
        // AI features regardless of their plan. Useful for client UAT
        // and the soft-launch period before the membership/Stripe layer
        // is wired up. Flip back to false once paid plans go live.
        if (filter_var(env('AI_FEATURES_FREE_FOR_ALL', false), FILTER_VALIDATE_BOOLEAN)) {
            return ['enabled' => true, 'quota' => 0]; // unlimited
        }

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

    /**
     * Phase 4 — count this user's free-beta AI actions used this calendar month
     * (all tools combined). Only rows recorded by the AI action gate with the
     * free-beta prefix are counted, so it never collides with per-plan quotas.
     */
    public function aiFreeBetaUsedThisMonth(): int
    {
        return \App\Models\AiFeatureUsage::where('user_id', $this->id)
            ->where('feature_code', 'like', \App\Domain\AiFeatures\AiAccess::BETA_ACTION_PREFIX . '%')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    /** Free-beta actions this user has left this month (PHP_INT_MAX if uncapped). */
    public function aiFreeBetaRemaining(): int
    {
        $cap = \App\Domain\AiFeatures\AiAccess::freeBetaCap();
        if ($cap <= 0) return PHP_INT_MAX;
        return max(0, $cap - $this->aiFreeBetaUsedThisMonth());
    }

    // ── GigResource IQ™ AI credits ─────────────────────────────────────────

    /** This user's monthly AI-credit allowance. */
    public function aiCreditsGrant(): int
    {
        return \App\Domain\AiFeatures\AiAccess::monthlyCreditGrant($this);
    }

    /** AI credits spent this month (sum of weighted action costs). */
    public function aiCreditsUsedThisMonth(): int
    {
        return (int) \App\Models\AiFeatureUsage::where('user_id', $this->id)
            ->where('feature_code', 'like', \App\Domain\AiFeatures\AiAccess::BETA_ACTION_PREFIX . '%')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('credits');
    }

    /** AI credits remaining this month (PHP_INT_MAX when effectively unlimited). */
    public function aiCreditsRemaining(): int
    {
        $grant = $this->aiCreditsGrant();
        if ($grant >= PHP_INT_MAX) return PHP_INT_MAX;
        return max(0, $grant - $this->aiCreditsUsedThisMonth());
    }
}
