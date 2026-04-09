<?php

namespace App\Domain\ActivityLog\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Central place to record security-sensitive user actions.
 * Failures are swallowed so logging never breaks the caller's flow.
 */
class ActivityLogger
{
    // Action constants — keep in sync with ActivityLog::actionLabel()
    public const ACTION_LOGIN            = 'login';
    public const ACTION_LOGOUT           = 'logout';
    public const ACTION_LOGIN_FAILED     = 'login_failed';
    public const ACTION_PASSWORD_CHANGED = 'password_changed';
    public const ACTION_PASSWORD_RESET   = 'password_reset';

    /**
     * Record an activity for an authenticated user.
     */
    public static function log(string $action, ?User $user = null, array $metadata = [], ?string $subjectIdentifier = null): void
    {
        try {
            /** @var Request|null $request */
            $request = app()->bound('request') ? app('request') : null;

            ActivityLog::create([
                'user_id'            => $user?->id,
                'action'             => $action,
                'subject_identifier' => $subjectIdentifier ?? $user?->email,
                'ip_address'         => $request?->ip(),
                'user_agent'         => $request?->userAgent(),
                'metadata'           => !empty($metadata) ? $metadata : null,
                'created_at'         => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to write activity log', [
                'action' => $action,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
