<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'duration_days',
        'max_events',
        'max_bookings',
        'has_chat',
        'has_priority_support',
        'is_active',
        'is_featured',
        'sort_order',
        'badge_text',
        'badge_color',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'has_chat' => 'boolean',
            'has_priority_support' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class)->orderBy('sort_order');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // ── Helpers ─────────────────────────────────────────────

    public function isFree(): bool
    {
        return $this->price <= 0;
    }

    public function formattedPrice(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        return '$' . number_format($this->price, 2);
    }

    /**
     * Short price-suffix label (shown next to the number on pricing cards).
     *
     * After the 6/12/18-month refactor we no longer support monthly/yearly
     * recurring — only contract terms. Legacy rows are still mapped so
     * historical subscriptions render sensibly.
     */
    public function billingLabel(): string
    {
        return match ($this->billing_cycle) {
            '6_month' => ' / 6 mo',
            '12_month' => ' / 12 mo',
            '18_month' => ' / 18 mo',
            // Legacy fallbacks (pre-refactor data)
            'monthly' => '/month',
            'quarterly' => '/quarter',
            'yearly' => '/year',
            'one_time' => ' one-time',
            default => '',
        };
    }

    /**
     * Full human-readable contract term (used in admin tables + signup flow).
     */
    public function contractTermLabel(): string
    {
        return match ($this->billing_cycle) {
            '6_month' => '6-month contract',
            '12_month' => '12-month contract',
            '18_month' => '18-month contract',
            'monthly' => 'Monthly (legacy)',
            'quarterly' => 'Quarterly (legacy)',
            'yearly' => 'Yearly (legacy)',
            'one_time' => 'One-time',
            default => ucfirst(str_replace('_', ' ', (string) $this->billing_cycle)),
        };
    }

    /**
     * Number of days a given billing cycle corresponds to. Used by
     * PaymentService to compute UserSubscription::expires_at.
     */
    public function cycleDurationDays(): int
    {
        return match ($this->billing_cycle) {
            '6_month' => 180,
            '12_month' => 365,
            '18_month' => 540,
            'monthly' => 30,
            'quarterly' => 90,
            'yearly' => 365,
            default => (int) ($this->duration_days ?? 0),
        };
    }

    public function activeSubscribersCount(): int
    {
        return $this->subscriptions()->where('status', 'active')->count();
    }
}
