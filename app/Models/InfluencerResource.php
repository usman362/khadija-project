<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InfluencerResource extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'body', 'type', 'category', 'level',
        'lessons', 'duration_minutes', 'downloads', 'badge', 'url', 'is_featured', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'lessons' => 'integer', 'duration_minutes' => 'integer', 'downloads' => 'integer',
            'is_featured' => 'boolean', 'published_at' => 'datetime',
        ];
    }

    public function scopeType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }
}
