<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A professional's crew member (server, bartender, DJ helper, etc.).
 */
class StaffMember extends Model
{
    protected $fillable = [
        'supplier_id', 'name', 'role', 'phone', 'email', 'hourly_rate', 'status',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
    ];

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
}
