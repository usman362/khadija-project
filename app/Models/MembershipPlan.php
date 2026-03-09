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

    public function billingLabel(): string
    {
        return match ($this->billing_cycle) {
            'monthly' => '/month',
            'quarterly' => '/quarter',
            'yearly' => '/year',
            'one_time' => ' one-time',
            default => '',
        };
    }

    public function activeSubscribersCount(): int
    {
        return $this->subscriptions()->where('status', 'active')->count();
    }
}
