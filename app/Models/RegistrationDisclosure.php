<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One user's acceptance of one version of the registration disclosure.
 * See the migration for why it is versioned.
 */
class RegistrationDisclosure extends Model
{
    /** The wording currently shown at sign-up. Bump when the wording changes. */
    public const CURRENT_VERSION = 'location_state_v1';

    protected $fillable = ['user_id', 'version', 'accepted_at', 'ip_address'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record that this user accepted, in UTC.
     *
     * Stamped in UTC rather than app time because the row may be read years
     * later, from another timezone, to answer when somebody agreed.
     */
    public static function record(User $user, ?string $ip, string $version = self::CURRENT_VERSION): self
    {
        return static::updateOrCreate(
            ['user_id' => $user->id, 'version' => $version],
            ['accepted_at' => now()->utc(), 'ip_address' => $ip],
        );
    }
}
