<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicySignature extends Model
{
    protected $fillable = [
        'user_id',
        'policy_type',
        'policy_version',
        'signature_type',
        'signature_data',
        'ip_address',
        'user_agent',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function labels(): array
    {
        return [
            'privacy_policy'      => 'Privacy Policy',
            'ai_usage_agreement'  => 'AI Usage Agreement',
            'terms_of_service'    => 'Terms of Service',
        ];
    }

    public function policyLabel(): string
    {
        return static::labels()[$this->policy_type] ?? ucwords(str_replace('_', ' ', $this->policy_type));
    }
}
