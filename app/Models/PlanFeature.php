<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    protected $fillable = [
        'membership_plan_id',
        'feature',
        'feature_code',
        'quota_monthly',
        'is_included',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_included'   => 'boolean',
            'quota_monthly' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }
}
