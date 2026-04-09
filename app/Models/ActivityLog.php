<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only log of security-sensitive user actions.
 * `updated_at` intentionally disabled — records are immutable.
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'subject_identifier',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        // Include trashed so historical logs still show the user (even after purge)
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Human-readable label for the action.
     */
    public function actionLabel(): string
    {
        return match ($this->action) {
            'login'             => 'Logged In',
            'logout'            => 'Logged Out',
            'login_failed'      => 'Failed Login',
            'password_changed'  => 'Password Changed',
            'password_reset'    => 'Password Reset',
            default             => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    public function actionColor(): string
    {
        return match ($this->action) {
            'login'            => 'success',
            'logout'           => 'secondary',
            'login_failed'     => 'danger',
            'password_changed' => 'info',
            'password_reset'   => 'warning',
            default            => 'light',
        };
    }
}
