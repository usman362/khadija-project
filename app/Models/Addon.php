<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An optional extra a professional sells on top of a service.
 *
 * Separate from the services themselves because an extra is not a service a
 * client can book on its own: nobody hires somebody for an extra hour of
 * lighting with no lighting under it.
 */
class Addon extends Model
{
    protected $table = 'professional_addons';

    protected $fillable = ['user_id', 'name', 'price', 'price_unit', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
