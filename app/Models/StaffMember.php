<?php

namespace App\Models;

use App\Support\GigResourceId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A professional's crew member (server, bartender, DJ helper, etc.).
 *
 * Not an account. They cannot sign in, they see nothing of the client's
 * event, and they are not party to any agreement — Sir Peter settled that on
 * 29 October: named team members, no logins, "with the flexibility to add
 * accounts later". The public_id below is that flexibility: a reference that
 * exists from the moment the person is added, so an account can be attached
 * to it later without the rest being rebuilt.
 */
class StaffMember extends Model
{
    /**
     * The prefix on a crew member's reference.
     *
     * Its own, not one of GigResourceId::PREFIXES, because those are
     * registration roles on the users table and this is not a user. Same
     * shape, so it reads like everything else on the platform.
     */
    public const PREFIX = 'STF';

    protected $fillable = [
        'supplier_id', 'name', 'photo', 'role', 'is_supervisor',
        'phone', 'email', 'hourly_rate', 'status',
    ];

    protected $casts = [
        'hourly_rate'   => 'decimal:2',
        'is_supervisor' => 'boolean',
    ];

    /**
     * Nobody is a supervisor until somebody says so.
     *
     * Stated here as well as in the column, because a row fresh from create()
     * has not read the column's default back: in memory the answer was null,
     * and null is not false to anything that asks.
     */
    protected $attributes = [
        'is_supervisor' => false,
    ];

    protected static function booted(): void
    {
        /*
         * Every crew member has a reference from the day they are added,
         * rather than one granted later to whoever happens to need it. A
         * reference that only some rows have is one more thing to check
         * before it can be used for anything.
         */
        static::creating(function (self $staff) {
            $staff->public_id ??= GigResourceId::compose(self::PREFIX);
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'staff_id');
    }

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->take(2)
            ->map(fn ($p) => Str::substr($p, 0, 1))->implode('');
    }

    /**
     * Where their photo is read from, or null when there is none.
     *
     * Null rather than a placeholder, so a screen can decide between a face
     * and initials for itself. A placeholder returned from here would make
     * "has a photo" unanswerable everywhere it matters.
     *
     * A ROUTE, not a storage path. Khadijah, 29 October: headshots and phone
     * numbers are "scoped strictly to the client holding the booking". A file
     * under public/storage is readable by anybody who has the address, and
     * addresses leak — so the file lives on the private disk and
     * StaffPhotoController decides, per request, whether the person asking is
     * allowed to see this particular crew member's face.
     */
    public function photoUrl(): ?string
    {
        return $this->photo ? route('staff.photo', $this) : null;
    }
}
