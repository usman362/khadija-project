<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'website',
        'social_links',
        'company_name',
        'company_website',
        'industry',
        'event_preferences',
        'headline',
        'hourly_rate',
        'availability',
        'skills',
        'experience_years',
        'portfolio',
        'certifications',
        'languages',
        'notify_email_bookings',
        'notify_email_messages',
        'notify_email_events',
        'notify_email_marketing',
        'notify_push',
        'notify_sms',
        // Verification badges
        'trade_license_number',
        'trade_license_doc',
        'trade_license_verified_at',
        'liability_insurance_number',
        'liability_insurance_doc',
        'liability_insurance_verified_at',
        'workers_comp_number',
        'workers_comp_doc',
        'workers_comp_verified_at',
        // Address verification (§7.3–7.5)
        'address_status',
        'address_verification_attempts',
        'address_flagged_home',
        'address_verified_at',
        'address_locked_at',
        'address_verification_meta',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'social_links' => 'array',
            'event_preferences' => 'array',
            'skills' => 'array',
            'portfolio' => 'array',
            'certifications' => 'array',
            'languages' => 'array',
            'hourly_rate' => 'decimal:2',
            'notify_email_bookings' => 'boolean',
            'notify_email_messages' => 'boolean',
            'notify_email_events' => 'boolean',
            'notify_email_marketing' => 'boolean',
            'notify_push' => 'boolean',
            'notify_sms' => 'boolean',
            'trade_license_verified_at' => 'datetime',
            'liability_insurance_verified_at' => 'datetime',
            'workers_comp_verified_at' => 'datetime',
            'address_flagged_home' => 'boolean',
            'address_verification_attempts' => 'integer',
            'address_verified_at' => 'datetime',
            'address_locked_at' => 'datetime',
            'address_verification_meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Verification Badge Helpers ─────────────────────────
    // Each badge has 3 states derived from {doc, verified_at}:
    //   'none'     → not uploaded yet
    //   'pending'  → doc uploaded, awaiting admin review
    //   'verified' → admin approved (show green badge to clients)

    public const BADGES = [
        'trade_license' => 'Trade License',
        'liability_insurance' => 'General Liability Insurance',
        'workers_comp' => "Workers' Compensation",
    ];

    public function badgeStatus(string $badge): string
    {
        $docCol = "{$badge}_doc";
        $verifiedCol = "{$badge}_verified_at";

        if ($this->$verifiedCol) {
            return 'verified';
        }
        if ($this->$docCol) {
            return 'pending';
        }
        return 'none';
    }

    public function verifiedBadges(): array
    {
        return array_filter(array_keys(self::BADGES), fn($b) => $this->badgeStatus($b) === 'verified');
    }

    public function hasAnyVerifiedBadge(): bool
    {
        return count($this->verifiedBadges()) > 0;
    }

    public function hasPendingVerification(): bool
    {
        foreach (array_keys(self::BADGES) as $badge) {
            if ($this->badgeStatus($badge) === 'pending') {
                return true;
            }
        }
        return false;
    }

    /** Uploaded portfolio image items (with generated sizes), featured first. */
    public function portfolioImageItems(): \Illuminate\Support\Collection
    {
        $imgs = collect(is_array($this->portfolio) ? $this->portfolio : [])
            ->filter(fn ($i) => is_array($i) && ($i['type'] ?? null) === 'image')
            ->values();

        return $imgs->filter(fn ($i) => $i['featured'] ?? false)
            ->merge($imgs->reject(fn ($i) => $i['featured'] ?? false))
            ->values();
    }

    /** Hero-size portfolio image URLs for search cards (featured cover first). */
    public function portfolioHeroUrls(int $limit = 4): array
    {
        return $this->portfolioImageItems()
            ->map(fn ($i) => \Illuminate\Support\Facades\Storage::url($i['hero'] ?? $i['square'] ?? ''))
            ->filter()
            ->take($limit)
            ->values()
            ->all();
    }
}
